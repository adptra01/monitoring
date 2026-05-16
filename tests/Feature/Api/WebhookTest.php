<?php

namespace Tests\Feature\Api;

use App\Events\LicenseCreated;
use App\Jobs\DispatchWebhookJob;
use App\Models\License;
use App\Models\Product;
use App\Models\User;
use App\Models\WebhookEndpoint;
use App\Services\WebhookService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Spatie\Permission\Models\Role;
use Tests\Concerns\SignsApiRequests;
use Tests\TestCase;

class WebhookTest extends TestCase
{
    use RefreshDatabase;
    use SignsApiRequests;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpHmac();
    }

    public function test_webhook_endpoint_can_be_created(): void
    {
        $endpoint = WebhookEndpoint::factory()->create([
            'url' => 'https://example.com/webhooks',
            'events' => ['license.created', 'license.revoked'],
        ]);

        $this->assertDatabaseHas('webhook_endpoints', [
            'id' => $endpoint->id,
            'url' => 'https://example.com/webhooks',
            'is_active' => true,
        ]);

        $this->assertEquals(['license.created', 'license.revoked'], $endpoint->events);
        $this->assertStringStartsWith('whsec_', $endpoint->secret);
    }

    public function test_webhook_endpoint_has_event(): void
    {
        $endpoint = WebhookEndpoint::factory()->create([
            'events' => ['license.created'],
        ]);

        $this->assertTrue($endpoint->hasEvent('license.created'));
        $this->assertFalse($endpoint->hasEvent('license.revoked'));
    }

    public function test_generate_secret_has_correct_format(): void
    {
        $secret = WebhookEndpoint::generateSecret();

        $this->assertStringStartsWith('whsec_', $secret);
        $this->assertEquals(66, strlen($secret));
    }

    public function test_webhook_service_dispatches_jobs_for_matching_endpoints(): void
    {
        Bus::fake();

        WebhookEndpoint::factory()->create([
            'events' => ['license.created'],
            'is_active' => true,
        ]);

        WebhookEndpoint::factory()->create([
            'events' => ['license.revoked'],
            'is_active' => true,
        ]);

        app(WebhookService::class)->dispatch('license.created', ['event' => 'license.created']);

        Bus::assertDispatched(DispatchWebhookJob::class, 1);
    }

    public function test_webhook_service_skips_inactive_endpoints(): void
    {
        Bus::fake();

        WebhookEndpoint::factory()->inactive()->create([
            'events' => ['license.created'],
        ]);

        app(WebhookService::class)->dispatch('license.created', ['event' => 'license.created']);

        Bus::assertNotDispatched(DispatchWebhookJob::class);
    }

    public function test_webhook_service_skips_non_matching_events(): void
    {
        Bus::fake();

        WebhookEndpoint::factory()->create([
            'events' => ['license.created'],
            'is_active' => true,
        ]);

        app(WebhookService::class)->dispatch('license.suspended', ['event' => 'license.suspended']);

        Bus::assertNotDispatched(DispatchWebhookJob::class);
    }

    public function test_webhook_job_sends_signed_request(): void
    {
        Http::fake();

        $endpoint = WebhookEndpoint::factory()->create([
            'url' => 'https://example.com/webhooks',
            'secret' => 'whsec_test_secret',
        ]);

        $job = new DispatchWebhookJob($endpoint, [
            'event' => 'license.created',
            'license_key' => 'ABCD-1234',
        ]);

        $job->handle();

        Http::assertSent(function ($request) {
            return $request->url() === 'https://example.com/webhooks'
                && $request->hasHeader('X-Webhook-Signature')
                && $request->hasHeader('X-Webhook-Timestamp')
                && $request['event'] === 'license.created'
                && $request['license_key'] === 'ABCD-1234';
        });
    }

    public function test_event_dispatches_webhook_job(): void
    {
        Bus::fake();

        WebhookEndpoint::factory()->create([
            'events' => ['license.created'],
            'is_active' => true,
        ]);

        $product = Product::factory()->create();
        $license = License::factory()->create(['product_id' => $product->id]);

        Event::dispatch(new LicenseCreated($license));

        Bus::assertDispatched(DispatchWebhookJob::class, function ($job) use ($license) {
            return $job->payload['event'] === 'license.created'
                && $job->payload['license_key'] === $license->key;
        });
    }

    public function test_webhook_list_page_can_be_rendered(): void
    {
        Role::create(['name' => 'admin']);
        $user = User::factory()->create()->assignRole('admin');

        WebhookEndpoint::factory()->count(3)->create();

        $this->actingAs($user)
            ->withSession(['auth.password_confirmed_at' => time()])
            ->get(route('webhooks.index'))
            ->assertOk()
            ->assertSee('Webhooks');
    }

    public function test_webhook_create_page_can_be_rendered(): void
    {
        Role::create(['name' => 'admin']);
        $user = User::factory()->create()->assignRole('admin');

        $this->actingAs($user)
            ->withSession(['auth.password_confirmed_at' => time()])
            ->get(route('webhooks.create'))
            ->assertOk()
            ->assertSee('Create Webhook Endpoint');
    }

    public function test_webhook_edit_page_can_be_rendered(): void
    {
        Role::create(['name' => 'admin']);
        $user = User::factory()->create()->assignRole('admin');
        $endpoint = WebhookEndpoint::factory()->create();

        $this->actingAs($user)
            ->withSession(['auth.password_confirmed_at' => time()])
            ->get(route('webhooks.edit', ['webhook_endpoint' => $endpoint->id]))
            ->assertOk()
            ->assertSee('Edit Webhook Endpoint');
    }
}
