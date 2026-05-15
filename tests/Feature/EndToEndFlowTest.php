<?php

namespace Tests\Feature;

use App\Models\Device;
use App\Models\License;
use App\Models\Product;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Volt\Volt;
use Tests\TestCase;

class EndToEndFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_complete_crud_and_activation_flow(): void
    {
        // 1. Setup Admin User
        $admin = User::factory()->create(['is_admin' => true]);
        $this->actingAs($admin);

        // 2. Product CRUD Flow
        Volt::test('pages.products.create')
            ->set('name', 'E2E Product Flow')
            ->set('slug', 'e2e-product-flow')
            ->set('description', 'Test Description')
            ->call('save')
            ->assertRedirect(route('products.index'));

        $product = Product::where('slug', 'e2e-product-flow')->first();
        $this->assertNotNull($product);

        Volt::test('pages.products.[product].edit', ['product' => $product->slug])
            ->set('name', 'E2E Product Flow Updated')
            ->call('save')
            ->assertRedirect(route('products.index'));

        $this->assertEquals('E2E Product Flow Updated', $product->fresh()->name);

        // 3. Plan CRUD Flow
        Volt::test('pages.plans.create')
            ->set('product_id', $product->id)
            ->set('name', 'E2E Premium Plan')
            ->set('slug', 'e2e-premium-plan')
            ->set('monthly_price', 50000)
            ->set('max_devices', 3)
            ->call('save')
            ->assertRedirect(route('plans.index'));

        $plan = SubscriptionPlan::where('slug', 'e2e-premium-plan')->first();
        $this->assertNotNull($plan);

        // 4. License CRUD Flow
        Volt::test('pages.licenses.create')
            ->set('product_id', $product->id)
            ->set('plan_id', $plan->id)
            ->set('user_id', $admin->id)
            ->set('max_devices', 3)
            ->call('save')
            ->assertRedirect(route('licenses.index'));

        $license = License::where('user_id', $admin->id)->first();
        $this->assertNotNull($license);
        $this->assertNotNull($license->license_key);

        // 5. API Validation & Activation Flow
        $deviceFingerprint = 'device-'.Str::random(10);

        // Simulate Device Activation via API
        $response = $this->postJson('/api/v1/validate', [
            'license_key' => $license->license_key,
            'device' => [
                'fingerprint' => $deviceFingerprint,
                'name' => 'E2E Test Device',
                'platform' => 'Windows',
                'os_version' => '11',
            ],
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('data.valid', true);

        // Verify device was registered
        $device = Device::where('fingerprint', $deviceFingerprint)->first();
        $this->assertNotNull($device);
        $this->assertEquals($license->id, $device->license_id);

        // 6. Cleanup / Deletion Flow
        // Delete License
        Volt::test('pages.licenses.index')
            ->call('confirmDelete', $license->id)
            ->call('delete');
        $this->assertNull(License::find($license->id));

        // Delete Plan
        Volt::test('pages.plans.index')
            ->call('confirmDelete', $plan->id)
            ->call('delete');
        $this->assertNull(SubscriptionPlan::find($plan->id));

        // Delete Product
        Volt::test('pages.products.index')
            ->call('confirmDelete', $product->id)
            ->call('delete');
        $this->assertNull(Product::find($product->id));
    }
}
