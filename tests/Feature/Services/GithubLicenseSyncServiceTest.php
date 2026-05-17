<?php

namespace Tests\Feature\Services;

use App\Enums\LicenseStatus;
use App\Jobs\SyncLicenseToGithubJob;
use App\Models\License;
use App\Models\Product;
use App\Models\User;
use App\Observers\LicenseObserver;
use App\Services\GithubLicenseSyncService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class GithubLicenseSyncServiceTest extends TestCase
{
    use LazilyRefreshDatabase;

    private GithubLicenseSyncService $service;

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.github.token' => 'test-token']);
        config(['services.github.sync_owner' => 'devWebs01']);
        config(['services.github.sync_repo' => 'license-sync-data']);
        config(['services.github.sync_path' => 'licenses']);

        $this->service = new GithubLicenseSyncService;
    }

    public function test_sync_pushes_json_to_github(): void
    {
        $license = License::factory()->create([
            'key' => 'TEST-ABCD-EFGH-1234',
            'status' => LicenseStatus::Active,
            'expires_at' => now()->addMonths(6),
            'max_devices' => 3,
        ]);

        $hash = sha1($license->key);
        $url = "https://api.github.com/repos/devWebs01/license-sync-data/contents/licenses/{$hash}.json";

        Http::fake([
            $url => Http::response(['sha' => 'abc123']),
            "{$url}*" => Http::response(['content' => ['sha' => 'def456']]),
        ]);

        $result = $this->service->sync($license);

        $this->assertTrue($result);
    }

    public function test_sync_creates_new_file_when_not_exists(): void
    {
        $license = License::factory()->create([
            'key' => 'NEW-KEY-1234-5678',
            'status' => LicenseStatus::Active,
        ]);

        $hash = sha1($license->key);
        $url = "https://api.github.com/repos/devWebs01/license-sync-data/contents/licenses/{$hash}.json";

        Http::fake([
            $url => Http::response(null, 404),
            "{$url}*" => Http::response(['content' => ['sha' => 'new123']]),
        ]);

        $result = $this->service->sync($license);

        $this->assertTrue($result);
    }

    public function test_sync_returns_false_when_not_configured(): void
    {
        config(['services.github.token' => '']);
        config(['services.github.sync_owner' => '']);

        $service = new GithubLicenseSyncService;
        $license = License::factory()->create();

        $result = $service->sync($license);

        $this->assertFalse($result);
    }

    public function test_sync_job_is_dispatched_on_license_creation(): void
    {
        Queue::fake();

        License::factory()->create();

        Queue::assertPushed(SyncLicenseToGithubJob::class);
    }

    public function test_sync_job_is_dispatched_on_status_change(): void
    {
        $license = License::factory()->create();

        Queue::fake();

        $license->update(['status' => LicenseStatus::Suspended]);

        Queue::assertPushed(SyncLicenseToGithubJob::class);
    }

    public function test_sync_job_is_not_dispatched_on_unrelated_change(): void
    {
        $license = License::factory()->create();

        Queue::fake();

        $license->update(['customer_name' => 'Updated Name']);

        Queue::assertNotPushed(SyncLicenseToGithubJob::class);
    }

    public function test_sync_job_handles_sync_service(): void
    {
        $license = License::factory()->create([
            'key' => 'JOB-TEST-KEY-1234',
            'status' => LicenseStatus::Active,
        ]);

        $hash = sha1($license->key);
        $url = "https://api.github.com/repos/devWebs01/license-sync-data/contents/licenses/{$hash}.json";

        Http::fake([
            $url => Http::response(['sha' => 'abc123']),
            "{$url}*" => Http::response(['content' => ['sha' => 'jobtest123']]),
        ]);

        $job = new SyncLicenseToGithubJob($license);
        $job->handle($this->service);

        $this->expectNotToPerformAssertions();
    }
}
