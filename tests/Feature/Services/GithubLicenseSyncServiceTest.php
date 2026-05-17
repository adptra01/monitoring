<?php

namespace Tests\Feature\Services;

use App\Enums\LicenseStatus;
use App\Jobs\SyncLicenseToGithubJob;
use App\Models\License;
use App\Services\GithubLicenseSyncService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class GithubLicenseSyncServiceTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Http::fake();
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

        Queue::assertPushed(SyncLicenseToGithubJob::class, 1);
    }

    public function test_sync_job_is_not_dispatched_on_unrelated_change(): void
    {
        $license = License::factory()->create();

        Queue::fake();

        $license->update(['customer_name' => 'Updated Name']);

        Queue::assertNotPushed(SyncLicenseToGithubJob::class);
    }

    public function test_sync_job_is_dispatched_on_expires_at_change(): void
    {
        $license = License::factory()->create();

        Queue::fake();

        $license->update(['expires_at' => now()->addYears(2)]);

        Queue::assertPushed(SyncLicenseToGithubJob::class, 1);
    }

    public function test_sync_job_has_unique_id(): void
    {
        $license = License::factory()->create();

        $job = new SyncLicenseToGithubJob($license);

        $this->assertSame('github-sync-'.$license->id, $job->uniqueId());
    }

    public function test_sync_with_configured_service(): void
    {
        config(['services.github.token' => 'test-token']);
        config(['services.github.sync_owner' => 'devWebs01']);
        config(['services.github.sync_repo' => 'license-sync-data']);
        config(['services.github.sync_path' => 'licenses']);

        $license = License::factory()->create([
            'key' => 'TEST-SYNC-KEY-1234',
            'status' => LicenseStatus::Active,
            'expires_at' => now()->addMonths(6),
            'max_devices' => 3,
        ]);

        $service = new GithubLicenseSyncService;
        $result = $service->sync($license);

        $this->assertTrue($result);
    }
}
