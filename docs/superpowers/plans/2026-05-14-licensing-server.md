# Monitoring Licensing Server - Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build a centralized licensing server for Laravel applications with device binding, activation approval flow, and admin panel.

**Architecture:** Laravel 13 app with Fortify + Livewire + Volt + Folio for admin. Public API for client license validation (no auth, rate-limited). Service layer for business logic. 7-day offline cache grace period.

**Tech Stack:** Laravel 13, PHP 8.3, Fortify, Livewire, Volt, Folio, Spatie Permission

---

## File Structure Overview

```
app/
├── Enums/
│   ├── LicenseStatus.php           # active, suspended, revoked, expired
│   ├── ActivationRequestStatus.php  # pending, approved, rejected
│   ├── SubscriptionStatus.php       # active, cancelled, expired
│   └── AuditAction.php              # license.*, device.*, activation.*, subscription.*
├── Models/
│   ├── Product.php                  # name, slug, description, is_active
│   ├── SubscriptionPlan.php         # product_id, name, duration_days, price
│   ├── License.php                  # product_id, customer info, key, status, dates, max_devices
│   ├── Device.php                   # license_id, device_id, device_name, ip, timestamps, is_active
│   ├── ActivationRequest.php        # license_id, old/new device, status, timestamps, handled_by
│   ├── Subscription.php             # license_id, plan_id, status, dates, renewed_at
│   └── AuditLog.php                 # user_id, license_id, action, payload JSON, ip, created_at
├── Http/
│   ├── Middleware/
│   │   └── CheckAdminMiddleware.php # gate for /admin/* routes
│   └── Controllers/
│       └── Api/
│           ├── ValidateLicenseController.php  # invokable
│           ├── ActivateLicenseController.php  # invokable
│           └── CheckUpdateController.php       # invokable
├── Http/Requests/
│   ├── ValidateLicenseRequest.php
│   ├── ActivateLicenseRequest.php
│   └── CheckUpdateRequest.php
├── Services/
│   ├── LicenseKeyService.php        # generate(), validateFormat()
│   └── LicenseService.php           # validate(), activate(), checkUpdate()
├── Providers/
│   └── FolioServiceProvider.php     # Folio config with admin middleware
└── Console/Commands/
    ├── LicensesCheckExpired.php
    └── LicensesNotifyExpiring.php

database/migrations/
├── add_is_admin_to_users_table.php
├── create_products_table.php
├── create_subscription_plans_table.php
├── create_licenses_table.php
├── create_devices_table.php
├── create_activation_requests_table.php
├── create_subscriptions_table.php
└── create_audit_logs_table.php

database/factories/
├── ProductFactory.php
├── SubscriptionPlanFactory.php
├── LicenseFactory.php
├── DeviceFactory.php
├── ActivationRequestFactory.php
└── SubscriptionFactory.php

resources/views/
├── layouts/
│   └── admin.blade.php
└── pages/
    └── admin/
        ├── index.blade.php          # Dashboard
        ├── products/
        │   ├── index.blade.php
        │   ├── create.blade.php
        │   └── [product]/edit.blade.php
        ├── plans/
        │   ├── index.blade.php
        │   ├── create.blade.php
        │   └── [plan]/edit.blade.php
        ├── licenses/
        │   ├── index.blade.php
        │   ├── create.blade.php
        │   └── [license]/edit.blade.php
        ├── devices/index.blade.php
        ├── activation-requests/index.blade.php
        └── audit-logs/index.blade.php

routes/
└── api.php                          # POST /api/license/validate, activate, check-update

routes/console.php                   # scheduler for licenses:check-expired, licenses:notify-expiring

tests/
├── Unit/Services/
│   ├── LicenseKeyServiceTest.php
│   └── LicenseServiceTest.php
└── Feature/Api/
    ├── LicenseValidationTest.php
    ├── LicenseActivationTest.php
    └── LicenseCheckUpdateTest.php
```

---

## Task 1: Database Foundation (Migrations)

**Files:**
- Create: `database/migrations/2026_05_14_000001_add_is_admin_to_users_table.php`
- Create: `database/migrations/2026_05_14_000002_create_products_table.php`
- Create: `database/migrations/2026_05_14_000003_create_subscription_plans_table.php`
- Create: `database/migrations/2026_05_14_000004_create_licenses_table.php`
- Create: `database/migrations/2026_05_14_000005_create_devices_table.php`
- Create: `database/migrations/2026_05_14_000006_create_activation_requests_table.php`
- Create: `database/migrations/2026_05_14_000007_create_subscriptions_table.php`
- Create: `database/migrations/2026_05_14_000008_create_audit_logs_table.php`

- [ ] **Step 1: Create migration - add is_admin to users**

Run: `php artisan make:migration add_is_admin_to_users_table`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_admin')->default(false)->after('password');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('is_admin');
        });
    }
};
```

- [ ] **Step 2: Create migration - products table**

Run: `php artisan make:migration create_products_table`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
```

- [ ] **Step 3: Create migration - subscription_plans table**

Run: `php artisan make:migration create_subscription_plans_table`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscription_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->integer('duration_days');
            $table->decimal('price', 12, 2);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('product_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_plans');
    }
};
```

- [ ] **Step 4: Create migration - licenses table**

Run: `php artisan make:migration create_licenses_table`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('licenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('customer_name');
            $table->string('customer_email');
            $table->string('license_key', 36)->unique();
            $table->string('status', 20)->default('active');
            $table->integer('max_devices')->default(1);
            $table->date('started_at');
            $table->date('expired_at');
            $table->dateTime('activated_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('license_key');
            $table->index('product_id');
            $table->index('status');
            $table->index('expired_at');
            $table->index(['status', 'expired_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('licenses');
    }
};
```

- [ ] **Step 5: Create migration - devices table**

Run: `php artisan make:migration create_devices_table`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('license_id')->constrained()->cascadeOnDelete();
            $table->string('device_id');
            $table->string('device_name');
            $table->string('ip_address', 45)->nullable();
            $table->dateTime('activated_at');
            $table->dateTime('last_seen_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['license_id', 'device_id']);
            $table->index('device_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('devices');
    }
};
```

- [ ] **Step 6: Create migration - activation_requests table**

Run: `php artisan make:migration create_activation_requests_table`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activation_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('license_id')->constrained()->cascadeOnDelete();
            $table->string('old_device_id')->nullable();
            $table->string('new_device_id');
            $table->string('new_device_name');
            $table->string('ip_address', 45)->nullable();
            $table->string('status', 20)->default('pending');
            $table->dateTime('requested_at');
            $table->dateTime('handled_at')->nullable();
            $table->foreignId('handled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('license_id');
            $table->index('status');
            $table->index('handled_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activation_requests');
    }
};
```

- [ ] **Step 7: Create migration - subscriptions table**

Run: `php artisan make:migration create_subscriptions_table`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('license_id')->constrained()->cascadeOnDelete();
            $table->foreignId('plan_id')->constrained('subscription_plans')->cascadeOnDelete();
            $table->string('status', 20)->default('active');
            $table->date('starts_at');
            $table->date('ends_at');
            $table->dateTime('renewed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('license_id');
            $table->index('plan_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};
```

- [ ] **Step 8: Create migration - audit_logs table**

Run: `php artisan make:migration create_audit_logs_table`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('license_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action', 100);
            $table->json('payload')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->dateTime('created_at');

            $table->index('user_id');
            $table->index('license_id');
            $table->index('action');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
```

- [ ] **Step 9: Run migrations**

Run: `php artisan migrate`

Expected: 8 tables created successfully

- [ ] **Step 10: Commit**

```bash
git add database/migrations/
git commit -m "feat: add licensing database tables"
```

---

## Task 2: Enums

**Files:**
- Create: `app/Enums/LicenseStatus.php`
- Create: `app/Enums/ActivationRequestStatus.php`
- Create: `app/Enums/SubscriptionStatus.php`
- Create: `app/Enums/AuditAction.php`

- [ ] **Step 1: Create LicenseStatus enum**

Run: `php artisan make:enum App\\Enums\\LicenseStatus`

```php
<?php

namespace App\Enums;

enum LicenseStatus: string
{
    case Active = 'active';
    case Suspended = 'suspended';
    case Revoked = 'revoked';
    case Expired = 'expired';
}
```

- [ ] **Step 2: Create ActivationRequestStatus enum**

Run: `php artisan make:enum App\\Enums\\ActivationRequestStatus`

```php
<?php

namespace App\Enums;

enum ActivationRequestStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';
}
```

- [ ] **Step 3: Create SubscriptionStatus enum**

Run: `php artisan make:enum App\\Enums\\SubscriptionStatus`

```php
<?php

namespace App\Enums;

enum SubscriptionStatus: string
{
    case Active = 'active';
    case Cancelled = 'cancelled';
    case Expired = 'expired';
}
```

- [ ] **Step 4: Create AuditAction enum**

Run: `php artisan make:enum App\\Enums\\AuditAction`

```php
<?php

namespace App\Enums;

enum AuditAction: string
{
    case LicenseCreated = 'license.created';
    case LicenseActivated = 'license.activated';
    case LicenseValidated = 'license.validated';
    case LicenseRevoked = 'license.revoked';
    case LicenseSuspended = 'license.suspended';
    case LicenseExpired = 'license.expired';
    case DeviceBound = 'device.bound';
    case ActivationApproved = 'activation.approved';
    case ActivationRejected = 'activation.rejected';
    case ActivationRequested = 'activation.requested';
    case SubscriptionCreated = 'subscription.created';
    case SubscriptionRenewed = 'subscription.renewed';
    case DevicesForceReset = 'devices.force_reset';
}
```

- [ ] **Step 5: Commit**

```bash
git add app/Enums/
git commit -m "feat: add licensing enums"
```

---

## Task 3: Models

**Files:**
- Create: `app/Models/Product.php`
- Create: `app/Models/SubscriptionPlan.php`
- Create: `app/Models/License.php`
- Create: `app/Models/Device.php`
- Create: `app/Models/ActivationRequest.php`
- Create: `app/Models/Subscription.php`
- Create: `app/Models/AuditLog.php`
- Modify: `app/Models/User.php:18-47` (add is_admin, isAdmin method)

- [ ] **Step 1: Create Product model**

Run: `php artisan make:model Product`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function subscriptionPlans(): HasMany
    {
        return $this->hasMany(SubscriptionPlan::class);
    }

    public function licenses(): HasMany
    {
        return $this->hasMany(License::class);
    }

    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }
}
```

- [ ] **Step 2: Create SubscriptionPlan model**

Run: `php artisan make:model SubscriptionPlan`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SubscriptionPlan extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'name',
        'duration_days',
        'price',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }
}
```

- [ ] **Step 3: Create License model**

Run: `php artisan make:model License`

```php
<?php

namespace App\Models;

use App\Enums\LicenseStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

class License extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'customer_name',
        'customer_email',
        'license_key',
        'status',
        'max_devices',
        'started_at',
        'expired_at',
        'activated_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'date',
            'expired_at' => 'date',
            'activated_at' => 'datetime',
            'max_devices' => 'integer',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function devices(): HasMany
    {
        return $this->hasMany(Device::class);
    }

    public function activeDevices(): HasMany
    {
        return $this->hasMany(Device::class)->where('is_active', true);
    }

    public function activationRequests(): HasMany
    {
        return $this->hasMany(ActivationRequest::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }

    public function scopeActive(Builder $query): void
    {
        $query->where('status', LicenseStatus::Active);
    }

    public function scopeExpired(Builder $query): void
    {
        $query->where('status', LicenseStatus::Expired);
    }

    public function isActive(): bool
    {
        return $this->status === LicenseStatus::Active
            && $this->expired_at->isFuture();
    }

    public function canActivateDevice(): bool
    {
        return $this->isActive()
            && $this->activeDevices()->count() < $this->max_devices;
    }

    public function isDeviceBound(string $deviceId): bool
    {
        return $this->devices()
            ->where('device_id', $deviceId)
            ->where('is_active', true)
            ->exists();
    }

    public function activeDeviceCount(): int
    {
        return $this->activeDevices()->count();
    }
}
```

- [ ] **Step 4: Create Device model**

Run: `php artisan make:model Device`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Device extends Model
{
    use HasFactory;

    protected $fillable = [
        'license_id',
        'device_id',
        'device_name',
        'ip_address',
        'activated_at',
        'last_seen_at',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'activated_at' => 'datetime',
            'last_seen_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    public function license(): BelongsTo
    {
        return $this->belongsTo(License::class);
    }
}
```

- [ ] **Step 5: Create ActivationRequest model**

Run: `php artisan make:model ActivationRequest`

```php
<?php

namespace App\Models;

use App\Enums\ActivationRequestStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class ActivationRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'license_id',
        'old_device_id',
        'new_device_id',
        'new_device_name',
        'ip_address',
        'status',
        'requested_at',
        'handled_at',
        'handled_by',
    ];

    protected function casts(): array
    {
        return [
            'requested_at' => 'datetime',
            'handled_at' => 'datetime',
        ];
    }

    public function license(): BelongsTo
    {
        return $this->belongsTo(License::class);
    }

    public function handledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handled_by');
    }

    public function scopePending(Builder $query): void
    {
        $query->where('status', ActivationRequestStatus::Pending);
    }

    public function approve(int $adminId): void
    {
        $this->update([
            'status' => ActivationRequestStatus::Approved,
            'handled_at' => now(),
            'handled_by' => $adminId,
        ]);

        if ($this->old_device_id) {
            $this->license->devices()
                ->where('device_id', $this->old_device_id)
                ->update(['is_active' => false]);
        }

        $this->license->devices()->create([
            'device_id' => $this->new_device_id,
            'device_name' => $this->new_device_name,
            'activated_at' => now(),
            'last_seen_at' => now(),
        ]);
    }

    public function reject(int $adminId): void
    {
        $this->update([
            'status' => ActivationRequestStatus::Rejected,
            'handled_at' => now(),
            'handled_by' => $adminId,
        ]);
    }
}
```

- [ ] **Step 6: Create Subscription model**

Run: `php artisan make:model Subscription`

```php
<?php

namespace App\Models;

use App\Enums\SubscriptionStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Subscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'license_id',
        'plan_id',
        'status',
        'starts_at',
        'ends_at',
        'renewed_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'date',
            'ends_at' => 'date',
            'renewed_at' => 'datetime',
        ];
    }

    public function license(): BelongsTo
    {
        return $this->belongsTo(License::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class);
    }
}
```

- [ ] **Step 7: Create AuditLog model**

Run: `php artisan make:model AuditLog`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'license_id',
        'action',
        'payload',
        'ip_address',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function license(): BelongsTo
    {
        return $this->belongsTo(License::class);
    }

    public static function log(
        string $action,
        ?array $payload = null,
        ?License $license = null,
        ?User $user = null,
        ?string $ipAddress = null,
    ): self {
        return static::create([
            'action' => $action,
            'payload' => $payload,
            'license_id' => $license?->id,
            'user_id' => $user?->id,
            'ip_address' => $ipAddress ?? request()->ip(),
            'created_at' => now(),
        ]);
    }
}
```

- [ ] **Step 8: Modify User model - add is_admin**

Edit `app/Models/User.php` - add `is_admin` to fillable, casts, and add `isAdmin()` method:

```php
// Add to fillable array (line ~16)
'is_admin',

// Add to casts array (line ~31)
'is_admin' => 'boolean',

// Add new method after initials() (after line 47)
public function isAdmin(): bool
{
    return $this->is_admin;
}
```

- [ ] **Step 9: Commit**

```bash
git add app/Models/ app/Enums/
git commit -m "feat: add licensing models and enums"
```

---

## Task 4: Factories

**Files:**
- Create: `database/factories/ProductFactory.php`
- Create: `database/factories/SubscriptionPlanFactory.php`
- Create: `database/factories/LicenseFactory.php`
- Create: `database/factories/DeviceFactory.php`
- Create: `database/factories/ActivationRequestFactory.php`

- [ ] **Step 1: Create ProductFactory**

Run: `php artisan make:factory ProductFactory`

```php
<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        return [
            'name' => fake()->words(2, true),
            'slug' => fn(array $attributes) => Str::slug($attributes['name']),
            'description' => fake()->sentence(),
            'is_active' => true,
        ];
    }
}
```

- [ ] **Step 2: Create SubscriptionPlanFactory**

Run: `php artisan make:factory SubscriptionPlanFactory`

```php
<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\SubscriptionPlan;
use Illuminate\Database\Eloquent\Factories\Factory;

class SubscriptionPlanFactory extends Factory
{
    protected $model = SubscriptionPlan::class;

    public function definition(): array
    {
        return [
            'product_id' => ProductFactory::new(),
            'name' => fake()->randomElement(['Monthly', 'Yearly', 'Lifetime']),
            'duration_days' => fake()->randomElement([30, 90, 365]),
            'price' => fake()->randomElement([99000, 299000, 599000, 999000]),
            'is_active' => true,
        ];
    }
}
```

- [ ] **Step 3: Create LicenseFactory**

Run: `php artisan make:factory LicenseFactory`

```php
<?php

namespace Database\Factories;

use App\Enums\LicenseStatus;
use App\Models\License;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class LicenseFactory extends Factory
{
    protected $model = License::class;

    public function definition(): array
    {
        return [
            'product_id' => ProductFactory::new(),
            'customer_name' => fake()->name(),
            'customer_email' => fake()->email(),
            'license_key' => 'LIC-' . strtoupper(Str::random(8)) . '-' . strtoupper(Str::random(8)),
            'status' => LicenseStatus::Active,
            'max_devices' => 1,
            'started_at' => now()->subMonth(),
            'expired_at' => now()->addYear(),
        ];
    }

    public function active(): static
    {
        return $this->state(fn() => [
            'status' => LicenseStatus::Active,
            'expired_at' => now()->addYear(),
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn() => [
            'status' => LicenseStatus::Expired,
            'expired_at' => now()->subDay(),
        ]);
    }

    public function suspended(): static
    {
        return $this->state(fn() => [
            'status' => LicenseStatus::Suspended,
        ]);
    }

    public function revoked(): static
    {
        return $this->state(fn() => [
            'status' => LicenseStatus::Revoked,
        ]);
    }

    public function withMaxDevices(int $max = 3): static
    {
        return $this->state(fn() => ['max_devices' => $max]);
    }
}
```

- [ ] **Step 4: Create DeviceFactory**

Run: `php artisan make:factory DeviceFactory`

```php
<?php

namespace Database\Factories;

use App\Models\Device;
use App\Models\License;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class DeviceFactory extends Factory
{
    protected $model = Device::class;

    public function definition(): array
    {
        return [
            'license_id' => LicenseFactory::new(),
            'device_id' => (string) Str::uuid(),
            'device_name' => fake()->word() . ' ' . fake()->randomNumber(3),
            'activated_at' => now(),
            'last_seen_at' => now(),
            'is_active' => true,
        ];
    }
}
```

- [ ] **Step 5: Create ActivationRequestFactory**

Run: `php artisan make:factory ActivationRequestFactory`

```php
<?php

namespace Database\Factories;

use App\Enums\ActivationRequestStatus;
use App\Models\ActivationRequest;
use App\Models\License;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ActivationRequestFactory extends Factory
{
    protected $model = ActivationRequest::class;

    public function definition(): array
    {
        return [
            'license_id' => LicenseFactory::new(),
            'new_device_id' => (string) Str::uuid(),
            'new_device_name' => fake()->word(),
            'status' => ActivationRequestStatus::Pending,
            'requested_at' => now(),
        ];
    }

    public function pending(): static
    {
        return $this->state(fn() => ['status' => ActivationRequestStatus::Pending]);
    }

    public function approved(): static
    {
        return $this->state(fn() => [
            'status' => ActivationRequestStatus::Approved,
            'handled_at' => now(),
            'handled_by' => UserFactory::new(),
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn() => [
            'status' => ActivationRequestStatus::Rejected,
            'handled_at' => now(),
            'handled_by' => UserFactory::new(),
        ]);
    }
}
```

- [ ] **Step 6: Commit**

```bash
git add database/factories/
git commit -m "feat: add licensing factories"
```

---

## Task 5: Services (Business Logic)

**Files:**
- Create: `app/Services/LicenseKeyService.php`
- Create: `app/Services/LicenseService.php`

- [ ] **Step 1: Create LicenseKeyService**

Run: `php artisan make:service LicenseKeyService`

```php
<?php

namespace App\Services;

use Illuminate\Support\Str;

class LicenseKeyService
{
    public function generate(): string
    {
        $segment1 = strtoupper(Str::random(8));
        $segment2 = strtoupper(Str::random(8));

        return "LIC-{$segment1}-{$segment2}";
    }

    public function validateFormat(string $key): bool
    {
        return (bool) preg_match('/^LIC-[A-Z0-9]{8}-[A-Z0-9]{8}$/', $key);
    }
}
```

- [ ] **Step 2: Create LicenseService**

Run: `php artisan make:service LicenseService`

```php
<?php

namespace App\Services;

use App\Enums\ActivationRequestStatus;
use App\Enums\AuditAction;
use App\Enums\LicenseStatus;
use App\Models\AuditLog;
use App\Models\License;

class LicenseService
{
    public function __construct(
        private LicenseKeyService $licenseKeyService,
    ) {}

    public function validate(string $licenseKey, string $deviceId, ?string $appVersion = null): array
    {
        $license = License::where('license_key', $licenseKey)->first();

        if (! $license) {
            return $this->invalidResponse('not_found', 'License key not found');
        }

        if ($license->status === LicenseStatus::Revoked) {
            return $this->invalidResponse('revoked', 'License has been revoked');
        }

        if ($license->status === LicenseStatus::Suspended) {
            return $this->invalidResponse('suspended', 'License is suspended');
        }

        if ($license->status === LicenseStatus::Expired || $license->expired_at->isPast()) {
            return $this->invalidResponse('expired', 'License has expired');
        }

        if (! $license->isDeviceBound($deviceId)) {
            return $this->invalidResponse('device_mismatch', 'Device is not registered with this license');
        }

        $license->devices()
            ->where('device_id', $deviceId)
            ->update([
                'last_seen_at' => now(),
                'ip_address' => request()->ip(),
            ]);

        $cacheUntil = now()->addDays(7);
        $cacheTtl = now()->diffInSeconds($cacheUntil);

        AuditLog::log(
            action: AuditAction::LicenseValidated->value,
            payload: ['device_id' => $deviceId, 'app_version' => $appVersion],
            license: $license,
            ipAddress: request()->ip(),
        );

        return [
            'valid' => true,
            'status' => LicenseStatus::Active->value,
            'expired_at' => $license->expired_at->format('Y-m-d'),
            'cache_until' => $cacheUntil->format('Y-m-d'),
            'cache_ttl_seconds' => $cacheTtl,
            'server_time' => now()->toIso8601String(),
            'message' => 'License valid',
        ];
    }

    public function activate(string $licenseKey, string $deviceId, string $deviceName): array
    {
        $license = License::where('license_key', $licenseKey)->first();

        if (! $license) {
            return [
                'success' => false,
                'status' => 'not_found',
                'message' => 'License key not found',
            ];
        }

        if (! $license->isActive()) {
            $status = $license->status === LicenseStatus::Expired || $license->expired_at->isPast()
                ? 'expired'
                : $license->status->value;

            return [
                'success' => false,
                'status' => $status,
                'message' => match ($status) {
                    'revoked' => 'License is revoked',
                    'suspended' => 'License is suspended',
                    'expired' => 'License has expired. Renewal required.',
                    default => 'License is not active',
                },
            ];
        }

        $existingDevice = $license->devices()->where('device_id', $deviceId)->first();

        if ($existingDevice) {
            if ($existingDevice->is_active) {
                $existingDevice->update([
                    'last_seen_at' => now(),
                    'ip_address' => request()->ip(),
                ]);

                AuditLog::log(
                    action: AuditAction::LicenseActivated->value,
                    payload: ['device_id' => $deviceId, 'device_name' => $deviceName],
                    license: $license,
                    ipAddress: request()->ip(),
                );

                return [
                    'success' => true,
                    'status' => 'active',
                    'message' => 'Device already activated',
                    'expired_at' => $license->expired_at->format('Y-m-d'),
                ];
            }

            $existingDevice->update([
                'is_active' => true,
                'last_seen_at' => now(),
                'ip_address' => request()->ip(),
            ]);

            return [
                'success' => true,
                'status' => 'active',
                'message' => 'Device reactivated',
                'expired_at' => $license->expired_at->format('Y-m-d'),
            ];
        }

        if ($license->canActivateDevice()) {
            $license->devices()->create([
                'device_id' => $deviceId,
                'device_name' => $deviceName,
                'ip_address' => request()->ip(),
                'activated_at' => now(),
                'last_seen_at' => now(),
            ]);

            if (! $license->activated_at) {
                $license->update(['activated_at' => now()]);
            }

            AuditLog::log(
                action: AuditAction::DeviceBound->value,
                payload: ['device_id' => $deviceId, 'device_name' => $deviceName],
                license: $license,
                ipAddress: request()->ip(),
            );

            return [
                'success' => true,
                'status' => 'active',
                'message' => 'Device activated successfully',
                'expired_at' => $license->expired_at->format('Y-m-d'),
            ];
        }

        $existingDeviceIds = $license->activeDevices()->pluck('device_id')->toArray();

        $request = $license->activationRequests()->create([
            'old_device_id' => $existingDeviceIds[0] ?? null,
            'new_device_id' => $deviceId,
            'new_device_name' => $deviceName,
            'ip_address' => request()->ip(),
            'status' => ActivationRequestStatus::Pending,
            'requested_at' => now(),
        ]);

        AuditLog::log(
            action: AuditAction::ActivationRequested->value,
            payload: [
                'activation_request_id' => $request->id,
                'old_device_id' => $request->old_device_id,
                'new_device_id' => $deviceId,
            ],
            license: $license,
            ipAddress: request()->ip(),
        );

        return [
            'success' => false,
            'status' => 'pending_approval',
            'message' => 'Device limit reached. Activation request sent to admin.',
            'activation_request_id' => $request->id,
        ];
    }

    public function checkUpdate(string $licenseKey, string $currentVersion): array
    {
        $exists = License::where('license_key', $licenseKey)
            ->where('status', LicenseStatus::Active)
            ->where('expired_at', '>', now())
            ->exists();

        if (! $exists) {
            return [
                'update_available' => false,
                'latest_version' => $currentVersion,
                'download_url' => null,
                'message' => 'Unable to check updates',
                'release_notes' => null,
            ];
        }

        return [
            'update_available' => false,
            'latest_version' => $currentVersion,
            'download_url' => null,
            'message' => 'You are using the latest version',
            'release_notes' => null,
        ];
    }

    private function invalidResponse(string $status, string $message): array
    {
        return [
            'valid' => false,
            'status' => $status,
            'cache_until' => null,
            'cache_ttl_seconds' => 0,
            'server_time' => now()->toIso8601String(),
            'message' => $message,
        ];
    }
}
```

- [ ] **Step 3: Commit**

```bash
git add app/Services/
git commit -m "feat: add license services"
```

---

## Task 6: API Endpoints

**Files:**
- Create: `app/Http/Requests/ValidateLicenseRequest.php`
- Create: `app/Http/Requests/ActivateLicenseRequest.php`
- Create: `app/Http/Requests/CheckUpdateRequest.php`
- Create: `app/Http/Controllers/Api/ValidateLicenseController.php`
- Create: `app/Http/Controllers/Api/ActivateLicenseController.php`
- Create: `app/Http/Controllers/Api/CheckUpdateController.php`
- Modify: `routes/api.php`
- Modify: `bootstrap/app.php` (add rate limiting)

- [ ] **Step 1: Create ValidateLicenseRequest**

Run: `php artisan make:request ValidateLicenseRequest`

```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ValidateLicenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'license_key' => ['required', 'string', 'regex:/^LIC-[A-Z0-9]{8}-[A-Z0-9]{8}$/'],
            'device_id' => ['required', 'string', 'min:1', 'max:255'],
            'app_version' => ['nullable', 'string', 'max:50'],
        ];
    }
}
```

- [ ] **Step 2: Create ActivateLicenseRequest**

Run: `php artisan make:request ActivateLicenseRequest`

```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ActivateLicenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'license_key' => ['required', 'string', 'regex:/^LIC-[A-Z0-9]{8}-[A-Z0-9]{8}$/'],
            'device_id' => ['required', 'string', 'min:1', 'max:255'],
            'device_name' => ['required', 'string', 'min:1', 'max:255'],
        ];
    }
}
```

- [ ] **Step 3: Create CheckUpdateRequest**

Run: `php artisan make:request CheckUpdateRequest`

```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CheckUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'license_key' => ['required', 'string', 'regex:/^LIC-[A-Z0-9]{8}-[A-Z0-9]{8}$/'],
            'current_version' => ['required', 'string', 'max:50'],
        ];
    }
}
```

- [ ] **Step 4: Create ValidateLicenseController**

Run: `php artisan make:controller Api/ValidateLicenseController`

```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ValidateLicenseRequest;
use App\Services\LicenseService;
use Illuminate\Http\JsonResponse;

class ValidateLicenseController extends Controller
{
    public function __construct(
        private LicenseService $licenseService,
    ) {}

    public function __invoke(ValidateLicenseRequest $request): JsonResponse
    {
        $result = $this->licenseService->validate(
            licenseKey: $request->validated('license_key'),
            deviceId: $request->validated('device_id'),
            appVersion: $request->validated('app_version'),
        );

        return response()->json($result);
    }
}
```

- [ ] **Step 5: Create ActivateLicenseController**

Run: `php artisan make:controller Api/ActivateLicenseController`

```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ActivateLicenseRequest;
use App\Services\LicenseService;
use Illuminate\Http\JsonResponse;

class ActivateLicenseController extends Controller
{
    public function __construct(
        private LicenseService $licenseService,
    ) {}

    public function __invoke(ActivateLicenseRequest $request): JsonResponse
    {
        $result = $this->licenseService->activate(
            licenseKey: $request->validated('license_key'),
            deviceId: $request->validated('device_id'),
            deviceName: $request->validated('device_name'),
        );

        return response()->json($result);
    }
}
```

- [ ] **Step 6: Create CheckUpdateController**

Run: `php artisan make:controller Api/CheckUpdateController`

```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CheckUpdateRequest;
use App\Services\LicenseService;
use Illuminate\Http\JsonResponse;

class CheckUpdateController extends Controller
{
    public function __construct(
        private LicenseService $licenseService,
    ) {}

    public function __invoke(CheckUpdateRequest $request): JsonResponse
    {
        $result = $this->licenseService->checkUpdate(
            licenseKey: $request->validated('license_key'),
            currentVersion: $request->validated('current_version'),
        );

        return response()->json($result);
    }
}
```

- [ ] **Step 7: Configure API routes**

Read `routes/api.php` first, then add:

```php
<?php

use App\Http\Controllers\Api\ActivateLicenseController;
use App\Http\Controllers\Api\CheckUpdateController;
use App\Http\Controllers\Api\ValidateLicenseController;
use Illuminate\Support\Facades\Route;

Route::post('/license/validate', ValidateLicenseController::class)
    ->middleware('throttle:60,1');

Route::post('/license/activate', ActivateLicenseController::class)
    ->middleware('throttle:30,1');

Route::post('/license/check-update', CheckUpdateController::class)
    ->middleware('throttle:30,1');
```

- [ ] **Step 8: Configure rate limiting in AppServiceProvider**

Read `app/Providers/AppServiceProvider.php` first, then add:

```php
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Http\Request;

public function boot(): void
{
    RateLimiter::for('license:validate', function (Request $request) {
        return Limit::perMinute(60)->by($request->ip());
    });

    RateLimiter::for('license:activate', function (Request $request) {
        return Limit::perMinute(30)->by($request->ip());
    });

    RateLimiter::for('license:update', function (Request $request) {
        return Limit::perMinute(30)->by($request->ip());
    });
}
```

- [ ] **Step 9: Commit**

```bash
git add app/Http/Requests/ app/Http/Controllers/ routes/api.php app/Providers/AppServiceProvider.php
git commit -m "feat: add licensing API endpoints"
```

---

## Task 7: Admin Panel - Middleware & Layout

**Files:**
- Create: `app/Http/Middleware/CheckAdminMiddleware.php`
- Create: `resources/views/layouts/admin.blade.php`
- Modify: `bootstrap/app.php` (register middleware)

- [ ] **Step 1: Create CheckAdminMiddleware**

Run: `php artisan make:middleware CheckAdminMiddleware`

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckAdminMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()?->isAdmin()) {
            abort(403, 'Unauthorized access. Admin only.');
        }

        return $next($request);
    }
}
```

- [ ] **Step 2: Create admin layout**

Run: `mkdir -p resources/views/layouts`

Create `resources/views/layouts/admin.blade.php`:

```blade
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ $header ?? 'Admin Panel' }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    {{ $slot }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
```

- [ ] **Step 3: Register middleware in bootstrap/app.php**

Read `bootstrap/app.php` first, then modify:

```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->alias([
        'check.admin' => \App\Http\Middleware\CheckAdminMiddleware::class,
    ]);
})
```

- [ ] **Step 4: Configure Folio for admin pages**

Folio is already installed (see composer.json: `laravel/folio`). Folio uses file-based routing - files in `resources/views/pages/` automatically become routes.

Create the admin pages directory structure first:

Run: `mkdir -p resources/views/pages/admin/{products,plans,licenses,devices,activation-requests,audit-logs}`

Read `app/Providers/FolioServiceProvider.php` first. If not exists, create it:

```php
<?php

namespace App\Providers;

use Illuminate\Support\Facades\Folio;
use Illuminate\Support\ServiceProvider;

class FolioServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Folio::path(resource_path('views/pages'))
            ->middleware([
                'auth',
                'verified',
                'check.admin',
            ]);
    }
}
```

Read `bootstrap/providers.php` and add the FolioServiceProvider if not already registered:

```php
<?php

return [
    App\Providers\AppServiceProvider::class,
    App\Providers\FortifyServiceProvider::class,
    App\Providers\FolioServiceProvider::class,  // Add this line
];
```

**Folio Routing Behavior:**
- `resources/views/pages/admin/index.blade.php` → Route: `/admin`
- `resources/views/pages/admin/products/index.blade.php` → Route: `/admin/products`
- `resources/views/pages/admin/products/create.blade.php` → Route: `/admin/products/create`
- `resources/views/pages/admin/products/[product]/edit.blade.php` → Route: `/admin/products/{product}/edit` (dynamic segment)

The middleware `check.admin` applied in FolioServiceProvider protects ALL pages under `/admin/*`.

- [ ] **Step 5: Add admin navigation component**

Run: `mkdir -p resources/views/components`

Create `resources/views/components/admin-nav.blade.php`:

```blade
<nav class="space-y-1">
    <x-nav-link href="{{ route('admin.dashboard') ?? '/admin' }}" :active="request()->is('admin')">
        {{ __('Dashboard') }}
    </x-nav-link>
    <x-nav-link href="{{ url('/admin/licenses') }}" :active="request()->is('admin/licenses*')">
        {{ __('Licenses') }}
    </x-nav-link>
    <x-nav-link href="{{ url('/admin/products') }}" :active="request()->is('admin/products*')">
        {{ __('Products') }}
    </x-nav-link>
    <x-nav-link href="{{ url('/admin/plans') }}" :active="request()->is('admin/plans*')">
        {{ __('Subscription Plans') }}
    </x-nav-link>
    <x-nav-link href="{{ url('/admin/activation-requests') }}" :active="request()->is('admin/activation-requests*')">
        {{ __('Activation Requests') }}
    </x-nav-link>
    <x-nav-link href="{{ url('/admin/devices') }}" :active="request()->is('admin/devices*')">
        {{ __('Devices') }}
    </x-nav-link>
    <x-nav-link href="{{ url('/admin/audit-logs') }}" :active="request()->is('admin/audit-logs*')">
        {{ __('Audit Logs') }}
    </x-nav-link>
</nav>
```

Update the admin layout to include the navigation. Edit `resources/views/layouts/admin.blade.php`:

```blade
<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ $header ?? 'Admin Panel' }}
            </h2>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="flex gap-6">
                <div class="w-64 flex-shrink-0">
                    <div class="bg-white rounded-lg shadow p-4 sticky top-6">
                        <x-admin-nav />
                    </div>
                </div>
                <div class="flex-1">
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6 text-gray-900">
                            {{ $slot }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
```

- [ ] **Step 6: Commit**

```bash
git add app/Http/Middleware/CheckAdminMiddleware.php resources/views/layouts/admin.blade.php resources/views/components/admin-nav.blade.php bootstrap/app.php app/Providers/FolioServiceProvider.php
git commit -m "feat: add admin middleware, Folio setup, and navigation"
```

---

## Task 8: Admin Panel - Volt Pages

**Files:**
- Create: `resources/views/pages/admin/index.blade.php`
- Create: `resources/views/pages/admin/products/index.blade.php`
- Create: `resources/views/pages/admin/products/create.blade.php`
- Create: `resources/views/pages/admin/products/[product]/edit.blade.php`
- Create: `resources/views/pages/admin/plans/index.blade.php`
- Create: `resources/views/pages/admin/plans/create.blade.php`
- Create: `resources/views/pages/admin/plans/[plan]/edit.blade.php`
- Create: `resources/views/pages/admin/licenses/index.blade.php`
- Create: `resources/views/pages/admin/licenses/create.blade.php`
- Create: `resources/views/pages/admin/licenses/[license]/edit.blade.php`
- Create: `resources/views/pages/admin/devices/index.blade.php`
- Create: `resources/views/pages/admin/activation-requests/index.blade.php`
- Create: `resources/views/pages/admin/audit-logs/index.blade.php`

- [ ] **Step 1: Create admin dashboard page**

Run: `mkdir -p resources/views/pages/admin`

Create `resources/views/pages/admin/index.blade.php`:

```blade
<?php

use App\Models\License;
use App\Models\ActivationRequest;
use App\Models\Device;
use function Livewire\Volt\{state};

state(['stats' => [], 'recentRequests' => [], 'expiringSoon' => []]);

$mount = function () {
    $this->stats = [
        'active_licenses' => License::where('status', 'active')->count(),
        'expired_today' => License::whereDate('expired_at', today())->count(),
        'pending_activations' => ActivationRequest::where('status', 'pending')->count(),
        'active_devices' => Device::where('is_active', true)->count(),
    ];

    $this->recentRequests = ActivationRequest::with('license')
        ->where('status', 'pending')
        ->latest()
        ->take(5)
        ->get();

    $this->expiringSoon = License::query()
        ->where('status', 'active')
        ->whereBetween('expired_at', [now(), now()->addDays(7)])
        ->take(5)
        ->get();
};
?>

<x-layouts.admin>
    <x-slot:header>Dashboard</x-slot:header>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
        <div class="bg-blue-50 p-4 rounded-lg">
            <div class="text-blue-600 text-2xl font-bold">{{ $stats['active_licenses'] }}</div>
            <div class="text-blue-800 text-sm">Active Licenses</div>
        </div>
        <div class="bg-red-50 p-4 rounded-lg">
            <div class="text-red-600 text-2xl font-bold">{{ $stats['expired_today'] }}</div>
            <div class="text-red-800 text-sm">Expired Today</div>
        </div>
        <div class="bg-yellow-50 p-4 rounded-lg">
            <div class="text-yellow-600 text-2xl font-bold">{{ $stats['pending_activations'] }}</div>
            <div class="text-yellow-800 text-sm">Pending Activations</div>
        </div>
        <div class="bg-green-50 p-4 rounded-lg">
            <div class="text-green-600 text-2xl font-bold">{{ $stats['active_devices'] }}</div>
            <div class="text-green-800 text-sm">Active Devices</div>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold mb-4">Pending Activation Requests</h3>
            @if($recentRequests->isEmpty())
                <p class="text-gray-500">No pending requests</p>
            @else
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-gray-600">
                            <th class="pb-2">License</th>
                            <th class="pb-2">New Device</th>
                            <th class="pb-2">Requested</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($recentRequests as $req)
                        <tr class="border-t">
                            <td class="py-2">{{ $req->license->license_key }}</td>
                            <td class="py-2">{{ $req->new_device_name }}</td>
                            <td class="py-2">{{ $req->requested_at->diffForHumans() }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold mb-4">Expiring Soon</h3>
            @if($expiringSoon->isEmpty())
                <p class="text-gray-500">No licenses expiring soon</p>
            @else
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-gray-600">
                            <th class="pb-2">Customer</th>
                            <th class="pb-2">Expires</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($expiringSoon as $license)
                        <tr class="border-t">
                            <td class="py-2">{{ $license->customer_name }}</td>
                            <td class="py-2">{{ $license->expired_at->format('M d, Y') }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>
</x-layouts.admin>
```

- [ ] **Step 2: Create Products pages**

Create `resources/views/pages/admin/products/index.blade.php`:

```blade
<?php

use App\Models\Product;
use Illuminate\Support\Str;
use function Livewire\Volt\{state, rules};

state(['products' => [], 'name' => '', 'description' => '', 'is_active' => true, 'showForm' => false]);

$mount = function () {
    $this->products = Product::withCount('subscriptionPlans', 'licenses')->latest()->get();
};

$rules = [
    'name' => 'required|max:255',
    'description' => 'nullable',
    'is_active' => 'boolean',
];

$save = function () {
    $this->validate();
    Product::create([
        'name' => $this->name,
        'slug' => Str::slug($this->name),
        'description' => $this->description,
        'is_active' => $this->is_active,
    ]);
    $this->mount();
    $this->showForm = false;
    $this->reset(['name', 'description', 'is_active']);
};

$delete = function (Product $product) {
    $product->delete();
    $this->mount();
};

$slug = fn() => Str::slug($this->name);
?>

<x-layouts.admin>
    <x-slot:header>Products</x-slot:header>

    <div class="mb-4 flex justify-end">
        <x-primary-button wire:click="$toggle('showForm')">
            {{ $showForm ? 'Cancel' : 'Create Product' }}
        </x-primary-button>
    </div>

    @if($showForm)
    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <form wire:submit="save" class="space-y-4">
            <div>
                <x-input-label for="name" value="Name" />
                <x-text-input wire:model="name" id="name" class="w-full" />
                <x-input-error for="name" />
                <p class="text-sm text-gray-500 mt-1">Slug: {{ $slug }}</p>
            </div>
            <div>
                <x-input-label for="description" value="Description" />
                <textarea wire:model="description" id="description" class="w-full rounded-md border-gray-300"></textarea>
            </div>
            <div>
                <label class="flex items-center gap-2">
                    <input type="checkbox" wire:model="is_active" class="rounded">
                    Active
                </label>
            </div>
            <x-primary-button>Save</x-primary-button>
        </form>
    </div>
    @endif

    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-sm font-medium text-gray-600">Name</th>
                    <th class="px-4 py-3 text-left text-sm font-medium text-gray-600">Slug</th>
                    <th class="px-4 py-3 text-left text-sm font-medium text-gray-600">Plans</th>
                    <th class="px-4 py-3 text-left text-sm font-medium text-gray-600">Licenses</th>
                    <th class="px-4 py-3 text-left text-sm font-medium text-gray-600">Status</th>
                    <th class="px-4 py-3 text-left text-sm font-medium text-gray-600">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @foreach($products as $product)
                <tr>
                    <td class="px-4 py-3">{{ $product->name }}</td>
                    <td class="px-4 py-3">{{ $product->slug }}</td>
                    <td class="px-4 py-3">{{ $product->subscription_plans_count }}</td>
                    <td class="px-4 py-3">{{ $product->licenses_count }}</td>
                    <td class="px-4 py-3">
                        <span @class([
                            'px-2 py-1 rounded text-xs',
                            'bg-green-100 text-green-800' => $product->is_active,
                            'bg-red-100 text-red-800' => !$product->is_active,
                        ])>
                            {{ $product->is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </td>
                    <td class="px-4 py-3 space-x-2">
                        <a href="/admin/products/{{ $product->id }}/edit" class="text-blue-600 hover:underline">Edit</a>
                        <button wire:click="delete({{ $product->id }})" class="text-red-600 hover:underline" onclick="return confirm('Delete?')">Delete</button>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</x-layouts.admin>
```

Create `resources/views/pages/admin/products/[product]/edit.blade.php`:

```blade
<?php

use App\Models\Product;
use function Livewire\Volt\{state, rules};

$product = fn() => Product::findOrFail(request()->route('product'));

state(['name' => '', 'description' => '', 'is_active' => true]);

$mount = function (Product $product) {
    $this->name = $product->name;
    $this->description = $product->description ?? '';
    $this->is_active = $product->is_active;
};

$rules = [
    'name' => 'required|max:255',
    'description' => 'nullable',
    'is_active' => 'boolean',
];

$save = function (Product $product) {
    $this->validate();
    $product->update([
        'name' => $this->name,
        'slug' => \Illuminate\Support\Str::slug($this->name),
        'description' => $this->description,
        'is_active' => $this->is_active,
    ]);
    session()->flash('success', 'Product updated.');
};

$cancel = fn() => $this->redirect('/admin/products');
?>

<x-layouts.admin>
    <x-slot:header>Edit Product</x-slot:header>

    <form wire:submit="save({{ request()->route('product') }})" class="max-w-lg space-y-4">
        <div>
            <x-input-label for="name" value="Name" />
            <x-text-input wire:model="name" id="name" class="w-full" />
            <x-input-error for="name" />
        </div>
        <div>
            <x-input-label for="description" value="Description" />
            <textarea wire:model="description" id="description" class="w-full rounded-md border-gray-300"></textarea>
        </div>
        <div>
            <label class="flex items-center gap-2">
                <input type="checkbox" wire:model="is_active" class="rounded">
                Active
            </label>
        </div>
        <div class="flex gap-4">
            <x-primary-button>Save</x-primary-button>
            <x-secondary-button wire:click="cancel">Cancel</x-secondary-button>
        </div>
    </form>
</x-layouts.admin>
```

- [ ] **Step 3: Create Plans pages**

Create `resources/views/pages/admin/plans/index.blade.php`:

```blade
<?php

use App\Models\Product;
use App\Models\SubscriptionPlan;
use function Livewire\Volt\{state};

state(['plans' => [], 'showForm' => false, 'product_id' => '', 'name' => '', 'duration_days' => 30, 'price' => 0]);

$mount = function () {
    $this->plans = SubscriptionPlan::with('product')->latest()->get();
};

$rules = [
    'product_id' => 'required|exists:products,id',
    'name' => 'required|max:255',
    'duration_days' => 'required|integer|min:1',
    'price' => 'required|numeric|min:0',
];

$save = function () {
    $this->validate();
    SubscriptionPlan::create([
        'product_id' => $this->product_id,
        'name' => $this->name,
        'duration_days' => $this->duration_days,
        'price' => $this->price,
        'is_active' => true,
    ]);
    $this->mount();
    $this->showForm = false;
    $this->reset(['product_id', 'name', 'duration_days', 'price']);
};

$delete = function (SubscriptionPlan $plan) {
    $plan->delete();
    $this->mount();
};
?>

<x-layouts.admin>
    <x-slot:header>Subscription Plans</x-slot:header>

    <div class="mb-4 flex justify-end">
        <x-primary-button wire:click="$toggle('showForm')">
            {{ $showForm ? 'Cancel' : 'Create Plan' }}
        </x-primary-button>
    </div>

    @if($showForm)
    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <form wire:submit="save" class="space-y-4">
            <div>
                <x-input-label for="product_id" value="Product" />
                <select wire:model="product_id" id="product_id" class="w-full rounded-md border-gray-300">
                    <option value="">Select Product</option>
                    @foreach(Product::active()->get() as $product)
                    <option value="{{ $product->id }}">{{ $product->name }}</option>
                    @endforeach
                </select>
                <x-input-error for="product_id" />
            </div>
            <div>
                <x-input-label for="name" value="Plan Name" />
                <x-text-input wire:model="name" id="name" class="w-full" />
                <x-input-error for="name" />
            </div>
            <div>
                <x-input-label for="duration_days" value="Duration (days)" />
                <x-text-input wire:model="duration_days" id="duration_days" type="number" class="w-full" />
                <x-input-error for="duration_days" />
            </div>
            <div>
                <x-input-label for="price" value="Price" />
                <x-text-input wire:model="price" id="price" type="number" step="0.01" class="w-full" />
                <x-input-error for="price" />
            </div>
            <x-primary-button>Save</x-primary-button>
        </form>
    </div>
    @endif

    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-sm font-medium text-gray-600">Product</th>
                    <th class="px-4 py-3 text-left text-sm font-medium text-gray-600">Name</th>
                    <th class="px-4 py-3 text-left text-sm font-medium text-gray-600">Duration</th>
                    <th class="px-4 py-3 text-left text-sm font-medium text-gray-600">Price</th>
                    <th class="px-4 py-3 text-left text-sm font-medium text-gray-600">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @foreach($plans as $plan)
                <tr>
                    <td class="px-4 py-3">{{ $plan->product->name }}</td>
                    <td class="px-4 py-3">{{ $plan->name }}</td>
                    <td class="px-4 py-3">{{ $plan->duration_days }} days</td>
                    <td class="px-4 py-3">Rp {{ number_format($plan->price, 0, ',', '.') }}</td>
                    <td class="px-4 py-3">
                        <a href="/admin/plans/{{ $plan->id }}/edit" class="text-blue-600 hover:underline">Edit</a>
                        <button wire:click="delete({{ $plan->id }})" class="text-red-600 hover:underline ml-2" onclick="return confirm('Delete?')">Delete</button>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</x-layouts.admin>
```

Create `resources/views/pages/admin/plans/[plan]/edit.blade.php`:

```blade
<?php

use App\Models\Product;
use App\Models\SubscriptionPlan;
use function Livewire\Volt\{state};

$plan = fn() => SubscriptionPlan::findOrFail(request()->route('plan'));

state(['name' => '', 'duration_days' => 30, 'price' => 0, 'is_active' => true]);

$mount = function (SubscriptionPlan $plan) {
    $this->name = $plan->name;
    $this->duration_days = $plan->duration_days;
    $this->price = $plan->price;
    $this->is_active = $plan->is_active;
};

$rules = [
    'name' => 'required|max:255',
    'duration_days' => 'required|integer|min:1',
    'price' => 'required|numeric|min:0',
    'is_active' => 'boolean',
];

$save = function (SubscriptionPlan $plan) {
    $this->validate();
    $plan->update([
        'name' => $this->name,
        'duration_days' => $this->duration_days,
        'price' => $this->price,
        'is_active' => $this->is_active,
    ]);
    session()->flash('success', 'Plan updated.');
};

$cancel = fn() => $this->redirect('/admin/plans');
?>

<x-layouts.admin>
    <x-slot:header>Edit Plan</x-slot:header>

    <form wire:submit="save({{ request()->route('plan') }})" class="max-w-lg space-y-4">
        <div>
            <x-input-label for="name" value="Name" />
            <x-text-input wire:model="name" id="name" class="w-full" />
            <x-input-error for="name" />
        </div>
        <div>
            <x-input-label for="duration_days" value="Duration (days)" />
            <x-text-input wire:model="duration_days" id="duration_days" type="number" class="w-full" />
            <x-input-error for="duration_days" />
        </div>
        <div>
            <x-input-label for="price" value="Price" />
            <x-text-input wire:model="price" id="price" type="number" step="0.01" class="w-full" />
            <x-input-error for="price" />
        </div>
        <div>
            <label class="flex items-center gap-2">
                <input type="checkbox" wire:model="is_active" class="rounded">
                Active
            </label>
        </div>
        <div class="flex gap-4">
            <x-primary-button>Save</x-primary-button>
            <x-secondary-button wire:click="cancel">Cancel</x-secondary-button>
        </div>
    </form>
</x-layouts.admin>
```

- [ ] **Step 4: Create Licenses pages**

Create `resources/views/pages/admin/licenses/index.blade.php`:

```blade
<?php

use App\Models\License;
use App\Models\Product;
use App\Models\SubscriptionPlan;
use App\Services\LicenseKeyService;
use function Livewire\Volt\{state};

state(['licenses' => [], 'showForm' => false, 'product_id' => '', 'customer_name' => '', 'customer_email' => '', 'max_devices' => 1, 'started_at' => '', 'plan_id' => '', 'notes' => '']);

$mount = function () {
    $this->licenses = License::with('product')->latest()->get();
};

$rules = [
    'product_id' => 'required|exists:products,id',
    'customer_name' => 'required|max:255',
    'customer_email' => 'required|email',
    'max_devices' => 'required|integer|min:1',
    'started_at' => 'required|date',
    'plan_id' => 'required|exists:subscription_plans,id',
    'notes' => 'nullable',
];

$save = function () {
    $this->validate();
    $plan = SubscriptionPlan::find($this->plan_id);
    $expiredAt = \Carbon\Carbon::parse($this->started_at)->addDays($plan->duration_days);

    $license = License::create([
        'product_id' => $this->product_id,
        'customer_name' => $this->customer_name,
        'customer_email' => $this->customer_email,
        'license_key' => app(LicenseKeyService::class)->generate(),
        'status' => 'active',
        'max_devices' => $this->max_devices,
        'started_at' => $this->started_at,
        'expired_at' => $expiredAt,
        'notes' => $this->notes,
    ]);

    $license->subscriptions()->create([
        'plan_id' => $this->plan_id,
        'status' => 'active',
        'starts_at' => $this->started_at,
        'ends_at' => $expiredAt,
    ]);

    $this->mount();
    $this->showForm = false;
    $this->reset(['product_id', 'customer_name', 'customer_email', 'max_devices', 'started_at', 'plan_id', 'notes']);
};
?>

<x-layouts.admin>
    <x-slot:header>Licenses</x-slot:header>

    <div class="mb-4 flex justify-end">
        <x-primary-button wire:click="$toggle('showForm')">
            {{ $showForm ? 'Cancel' : 'Create License' }}
        </x-primary-button>
    </div>

    @if($showForm)
    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <form wire:submit="save" class="space-y-4">
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <x-input-label for="product_id" value="Product" />
                    <select wire:model="product_id" id="product_id" class="w-full rounded-md border-gray-300">
                        <option value="">Select Product</option>
                        @foreach(Product::active()->get() as $product)
                        <option value="{{ $product->id }}">{{ $product->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <x-input-label for="customer_name" value="Customer Name" />
                    <x-text-input wire:model="customer_name" id="customer_name" class="w-full" />
                </div>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <x-input-label for="customer_email" value="Customer Email" />
                    <x-text-input wire:model="customer_email" id="customer_email" type="email" class="w-full" />
                </div>
                <div>
                    <x-input-label for="max_devices" value="Max Devices" />
                    <x-text-input wire:model="max_devices" id="max_devices" type="number" class="w-full" />
                </div>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <x-input-label for="started_at" value="Start Date" />
                    <x-text-input wire:model="started_at" id="started_at" type="date" class="w-full" />
                </div>
                <div>
                    <x-input-label for="plan_id" value="Subscription Plan" />
                    <select wire:model="plan_id" id="plan_id" class="w-full rounded-md border-gray-300">
                        <option value="">Select Plan</option>
                        @foreach(SubscriptionPlan::where('is_active', true)->get() as $plan)
                        <option value="{{ $plan->id }}">{{ $plan->name }} ({{ $plan->duration_days }} days)</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div>
                <x-input-label for="notes" value="Notes" />
                <textarea wire:model="notes" id="notes" class="w-full rounded-md border-gray-300"></textarea>
            </div>
            <x-primary-button>Create License</x-primary-button>
        </form>
    </div>
    @endif

    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-sm font-medium text-gray-600">License Key</th>
                    <th class="px-4 py-3 text-left text-sm font-medium text-gray-600">Customer</th>
                    <th class="px-4 py-3 text-left text-sm font-medium text-gray-600">Product</th>
                    <th class="px-4 py-3 text-left text-sm font-medium text-gray-600">Status</th>
                    <th class="px-4 py-3 text-left text-sm font-medium text-gray-600">Expires</th>
                    <th class="px-4 py-3 text-left text-sm font-medium text-gray-600">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @foreach($licenses as $license)
                <tr>
                    <td class="px-4 py-3 font-mono text-sm">{{ $license->license_key }}</td>
                    <td class="px-4 py-3">{{ $license->customer_name }}</td>
                    <td class="px-4 py-3">{{ $license->product->name }}</td>
                    <td class="px-4 py-3">
                        <span @class([
                            'px-2 py-1 rounded text-xs',
                            'bg-green-100 text-green-800' => $license->status === 'active',
                            'bg-yellow-100 text-yellow-800' => $license->status === 'suspended',
                            'bg-red-100 text-red-800' => in_array($license->status, ['expired', 'revoked']),
                        ])>
                            {{ $license->status }}
                        </span>
                    </td>
                    <td class="px-4 py-3">{{ $license->expired_at->format('M d, Y') }}</td>
                    <td class="px-4 py-3">
                        <a href="/admin/licenses/{{ $license->id }}/edit" class="text-blue-600 hover:underline">Edit</a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</x-layouts.admin>
```

Create `resources/views/pages/admin/licenses/[license]/edit.blade.php`:

```blade
<?php

use App\Models\License;
use App\Enums\LicenseStatus;
use App\Enums\AuditAction;
use App\Models\AuditLog;
use function Livewire\Volt\{state};

$license = fn() => License::with(['devices', 'activationRequests'])->findOrFail(request()->route('license'));

state(['status' => '', 'notes' => '']);

$mount = function (License $license) {
    $this->status = $license->status;
    $this->notes = $license->notes ?? '';
};

$save = function (License $license) {
    $oldStatus = $license->status;
    $license->update(['notes' => $this->notes]);
    session()->flash('success', 'License updated.');
};

$suspend = function (License $license) {
    $license->update(['status' => LicenseStatus::Suspended]);
    AuditLog::log(AuditAction::LicenseSuspended->value, ['admin_id' => auth()->id()], $license, auth()->user());
    $this->mount($license);
};

$activate = function (License $license) {
    $license->update(['status' => LicenseStatus::Active]);
    session()->flash('success', 'License activated.');
    $this->mount($license);
};

$revoke = function (License $license) {
    $license->update(['status' => LicenseStatus::Revoked]);
    $license->devices()->update(['is_active' => false]);
    AuditLog::log(AuditAction::LicenseRevoked->value, ['admin_id' => auth()->id()], $license, auth()->user());
    $this->mount($license);
};

$forceReset = function (License $license) {
    $license->devices()->update(['is_active' => false]);
    $license->activationRequests()->where('status', 'pending')->update(['status' => 'rejected']);
    $license->update(['activated_at' => null]);
    AuditLog::log(AuditAction::DevicesForceReset->value, ['admin_id' => auth()->id()], $license, auth()->user());
    session()->flash('success', 'All devices reset.');
    $this->mount($license);
};
?>

<x-layouts.admin>
    <x-slot:header>Edit License</x-slot:header>

    <div class="space-y-6">
        <div class="bg-white rounded-lg shadow p-6">
            <div class="grid grid-cols-2 gap-4 mb-6">
                <div>
                    <p class="text-sm text-gray-600">License Key</p>
                    <p class="font-mono font-bold">{{ $license->license_key }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-600">Customer</p>
                    <p>{{ $license->customer_name }} ({{ $license->customer_email }})</p>
                </div>
                <div>
                    <p class="text-sm text-gray-600">Product</p>
                    <p>{{ $license->product->name }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-600">Expires</p>
                    <p>{{ $license->expired_at->format('M d, Y') }}</p>
                </div>
            </div>

            <form wire:submit="save({{ request()->route('license') }})" class="space-y-4">
                <div>
                    <x-input-label for="notes" value="Notes" />
                    <textarea wire:model="notes" id="notes" class="w-full rounded-md border-gray-300"></textarea>
                </div>
                <x-primary-button>Save Notes</x-primary-button>
            </form>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold mb-4">Actions</h3>
            <div class="flex gap-4">
                @if($license->status !== 'suspended')
                <x-secondary-button wire:click="suspend({{ request()->route('license') }})">Suspend</x-secondary-button>
                @else
                <x-primary-button wire:click="activate({{ request()->route('license') }})">Activate</x-primary-button>
                @endif
                @if($license->status !== 'revoked')
                <x-danger-button wire:click="revoke({{ request()->route('license') }})" onclick="return confirm('Revoke license PERMANENTLY?')">Revoke</x-danger-button>
                @endif
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold mb-4">Devices ({{ $license->devices->count() }})</h3>
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-gray-600">
                        <th class="pb-2">Device Name</th>
                        <th class="pb-2">Device ID</th>
                        <th class="pb-2">Status</th>
                        <th class="pb-2">Last Seen</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($license->devices as $device)
                    <tr class="border-t">
                        <td class="py-2">{{ $device->device_name }}</td>
                        <td class="py-2 font-mono text-xs">{{ $device->device_id }}</td>
                        <td class="py-2">
                            <span @class([
                                'px-2 py-1 rounded text-xs',
                                'bg-green-100 text-green-800' => $device->is_active,
                                'bg-gray-100 text-gray-600' => !$device->is_active,
                            ])>
                                {{ $device->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                        <td class="py-2">{{ $device->last_seen_at?->diffForHumans() }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            <div class="mt-4">
                <x-danger-button wire:click="forceReset({{ request()->route('license') }})" onclick="return confirm('Reset ALL devices? Customer must reactivate.')">
                    Force Reset All Devices
                </x-danger-button>
            </div>
        </div>
    </div>
</x-layouts.admin>
```

- [ ] **Step 5: Create Devices page (read-only)**

Create `resources/views/pages/admin/devices/index.blade.php`:

```blade
<?php

use App\Models\Device;
use function Livewire\Volt\{state};

state(['devices' => [], 'search' => '']);

$mount = function () {
    $this->devices = Device::with('license')->latest()->get();
};
?>

<x-layouts.admin>
    <x-slot:header>Devices</x-slot:header>

    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-sm font-medium text-gray-600">License</th>
                    <th class="px-4 py-3 text-left text-sm font-medium text-gray-600">Device Name</th>
                    <th class="px-4 py-3 text-left text-sm font-medium text-gray-600">Device ID</th>
                    <th class="px-4 py-3 text-left text-sm font-medium text-gray-600">IP Address</th>
                    <th class="px-4 py-3 text-left text-sm font-medium text-gray-600">Last Seen</th>
                    <th class="px-4 py-3 text-left text-sm font-medium text-gray-600">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @foreach($devices as $device)
                <tr>
                    <td class="px-4 py-3 font-mono text-sm">{{ $device->license->license_key }}</td>
                    <td class="px-4 py-3">{{ $device->device_name }}</td>
                    <td class="px-4 py-3 font-mono text-xs">{{ $device->device_id }}</td>
                    <td class="px-4 py-3">{{ $device->ip_address ?? '-' }}</td>
                    <td class="px-4 py-3">{{ $device->last_seen_at?->diffForHumans() }}</td>
                    <td class="px-4 py-3">
                        <span @class([
                            'px-2 py-1 rounded text-xs',
                            'bg-green-100 text-green-800' => $device->is_active,
                            'bg-gray-100 text-gray-600' => !$device->is_active,
                        ])>
                            {{ $device->is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</x-layouts.admin>
```

- [ ] **Step 6: Create Activation Requests page**

Create `resources/views/pages/admin/activation-requests/index.blade.php`:

```blade
<?php

use App\Models\ActivationRequest;
use App\Enums\ActivationRequestStatus;
use function Livewire\Volt\{state};

state(['requests' => [], 'filter' => 'pending']);

$requests = function () {
    return ActivationRequest::with('license')
        ->when($this->filter !== 'all', fn($q) => $q->where('status', $this->filter))
        ->latest()
        ->get();
};

$approve = function (ActivationRequest $request) {
    $request->approve(auth()->id());
    session()->flash('success', 'Activation approved.');
};

$reject = function (ActivationRequest $request) {
    $request->reject(auth()->id());
    session()->flash('success', 'Activation rejected.');
};
?>

<x-layouts.admin>
    <x-slot:header>Activation Requests</x-slot:header>

    <div class="mb-4">
        <select wire:model="filter" class="rounded border-gray-300">
            <option value="pending">Pending</option>
            <option value="approved">Approved</option>
            <option value="rejected">Rejected</option>
            <option value="all">All</option>
        </select>
    </div>

    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-sm font-medium text-gray-600">License</th>
                    <th class="px-4 py-3 text-left text-sm font-medium text-gray-600">Old Device</th>
                    <th class="px-4 py-3 text-left text-sm font-medium text-gray-600">New Device</th>
                    <th class="px-4 py-3 text-left text-sm font-medium text-gray-600">Requested</th>
                    <th class="px-4 py-3 text-left text-sm font-medium text-gray-600">Status</th>
                    <th class="px-4 py-3 text-left text-sm font-medium text-gray-600">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @foreach($requests() as $req)
                <tr>
                    <td class="px-4 py-3 font-mono text-sm">{{ $req->license->license_key }}</td>
                    <td class="px-4 py-3 text-sm">{{ $req->old_device_id ?? 'N/A' }}</td>
                    <td class="px-4 py-3">{{ $req->new_device_name }} ({{ Str::limit($req->new_device_id, 8) }})</td>
                    <td class="px-4 py-3">{{ $req->requested_at->diffForHumans() }}</td>
                    <td class="px-4 py-3">
                        <span @class([
                            'px-2 py-1 rounded text-xs',
                            'bg-yellow-100 text-yellow-800' => $req->status === 'pending',
                            'bg-green-100 text-green-800' => $req->status === 'approved',
                            'bg-red-100 text-red-800' => $req->status === 'rejected',
                        ])>
                            {{ $req->status }}
                        </span>
                    </td>
                    <td class="px-4 py-3">
                        @if($req->status === 'pending')
                        <button wire:click="approve({{ $req->id }})" class="text-green-600 hover:underline">Approve</button>
                        <button wire:click="reject({{ $req->id }})" class="text-red-600 hover:underline ml-2">Reject</button>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</x-layouts.admin>
```

- [ ] **Step 7: Create Audit Logs page**

Create `resources/views/pages/admin/audit-logs/index.blade.php`:

```blade
<?php

use App\Models\AuditLog;
use function Livewire\Volt\{state};

state(['logs' => [], 'actionFilter' => '']);

$mount = function () {
    $this->logs = AuditLog::with(['user', 'license'])
        ->when($this->actionFilter, fn($q) => $q->where('action', $this->actionFilter))
        ->latest()
        ->limit(100)
        ->get();
};

$actions = [
    'license.created',
    'license.activated',
    'license.validated',
    'license.revoked',
    'license.suspended',
    'license.expired',
    'device.bound',
    'activation.approved',
    'activation.rejected',
    'activation.requested',
    'subscription.created',
    'subscription.renewed',
    'devices.force_reset',
];
?>

<x-layouts.admin>
    <x-slot:header>Audit Logs</x-slot:header>

    <div class="mb-4">
        <select wire:model="actionFilter" wire:change="$refresh" class="rounded border-gray-300">
            <option value="">All Actions</option>
            @foreach($actions as $action)
            <option value="{{ $action }}">{{ $action }}</option>
            @endforeach
        </select>
    </div>

    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-sm font-medium text-gray-600">Time</th>
                    <th class="px-4 py-3 text-left text-sm font-medium text-gray-600">Action</th>
                    <th class="px-4 py-3 text-left text-sm font-medium text-gray-600">License</th>
                    <th class="px-4 py-3 text-left text-sm font-medium text-gray-600">User</th>
                    <th class="px-4 py-3 text-left text-sm font-medium text-gray-600">IP</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @foreach($logs as $log)
                <tr>
                    <td class="px-4 py-3">{{ $log->created_at->format('M d, H:i:s') }}</td>
                    <td class="px-4 py-3">{{ $log->action }}</td>
                    <td class="px-4 py-3">{{ $log->license?->license_key ?? '-' }}</td>
                    <td class="px-4 py-3">{{ $log->user?->name ?? '-' }}</td>
                    <td class="px-4 py-3">{{ $log->ip_address ?? '-' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</x-layouts.admin>
```

- [ ] **Step 8: Add web route redirect**

Read `routes/web.php` first, then add:

```php
Route::redirect('/admin', '/admin/licenses');
```

- [ ] **Step 9: Commit**

```bash
git add resources/views/pages/admin/ resources/views/layouts/admin.blade.php routes/web.php
git commit -m "feat: add admin panel Volt pages"
```

---

## Task 9: Scheduler Commands

**Files:**
- Create: `app/Console/Commands/LicensesCheckExpired.php`
- Create: `app/Console/Commands/LicensesNotifyExpiring.php`
- Modify: `routes/console.php`

- [ ] **Step 1: Create LicensesCheckExpired command**

Run: `php artisan make:command LicensesCheckExpired`

```php
<?php

namespace App\Console\Commands;

use App\Enums\AuditAction;
use App\Enums\LicenseStatus;
use App\Models\AuditLog;
use App\Models\License;
use Illuminate\Console\Command;

class LicensesCheckExpired extends Command
{
    protected $signature = 'licenses:check-expired';

    protected $description = 'Mark expired licenses as expired';

    public function handle(): int
    {
        $expired = License::query()
            ->where('status', LicenseStatus::Active)
            ->where('expired_at', '<', today())
            ->get();

        foreach ($expired as $license) {
            $license->update(['status' => LicenseStatus::Expired]);
            AuditLog::log(AuditAction::LicenseExpired->value, [], $license);
        }

        $this->info("Marked {$expired->count()} license(s) as expired.");

        return Command::SUCCESS;
    }
}
```

- [ ] **Step 2: Create LicensesNotifyExpiring command**

Run: `php artisan make:command LicensesNotifyExpiring`

```php
<?php

namespace App\Console\Commands;

use App\Models\License;
use Illuminate\Console\Command;

class LicensesNotifyExpiring extends Command
{
    protected $signature = 'licenses:notify-expiring {--days=7}';

    protected $description = 'List licenses expiring soon';

    public function handle(): int
    {
        $days = (int) $this->option('days');

        $licenses = License::query()
            ->where('status', 'active')
            ->whereBetween('expired_at', [now(), now()->addDays($days)])
            ->get();

        if ($licenses->isEmpty()) {
            $this->info("No licenses expiring within {$days} days.");
            return Command::SUCCESS;
        }

        $this->info("Licenses expiring within {$days} days ({$licenses->count()}):");
        foreach ($licenses as $license) {
            $this->line("- {$license->license_key}: {$license->customer_name} (expires {$license->expired_at->format('M d, Y')})");
        }

        return Command::SUCCESS;
    }
}
```

- [ ] **Step 3: Configure scheduler**

Read `routes/console.php` first, then add:

```php
$schedule->command('licenses:check-expired')->daily();
$schedule->command('licenses:notify-expiring --days=7')->daily();
```

- [ ] **Step 4: Commit**

```bash
git add app/Console/Commands/ routes/console.php
git commit -m "feat: add scheduler commands for license expiry"
```

---

## Task 10: Unit Tests

**Files:**
- Create: `tests/Unit/Services/LicenseKeyServiceTest.php`
- Create: `tests/Unit/Services/LicenseServiceTest.php`
- Create: `tests/Feature/Api/LicenseValidationTest.php`
- Create: `tests/Feature/Api/LicenseActivationTest.php`
- Create: `tests/Feature/Api/LicenseCheckUpdateTest.php`

- [ ] **Step 1: Create LicenseKeyServiceTest**

Run: `php artisan make:test LicenseKeyServiceTest --unit`

```php
<?php

namespace Tests\Unit\Services;

use App\Services\LicenseKeyService;
use Tests\TestCase;

class LicenseKeyServiceTest extends TestCase
{
    public function test_generate_returns_valid_format(): void
    {
        $service = new LicenseKeyService();
        $key = $service->generate();

        $this->assertMatchesRegularExpression('/^LIC-[A-Z0-9]{8}-[A-Z0-9]{8}$/', $key);
    }

    public function test_generate_returns_unique_keys(): void
    {
        $service = new LicenseKeyService();
        $keys = array_map(fn() => $service->generate(), range(1, 100));

        $this->assertCount(100, array_unique($keys));
    }

    public function test_validate_format_accepts_valid_key(): void
    {
        $service = new LicenseKeyService();

        $this->assertTrue($service->validateFormat('LIC-ABCD1234-EFGH5678'));
        $this->assertTrue($service->validateFormat('LIC-00000000-00000000'));
        $this->assertTrue($service->validateFormat('LIC-XXXXXXXX-YYYYYYYY'));
    }

    public function test_validate_format_rejects_invalid_key(): void
    {
        $service = new LicenseKeyService();

        $this->assertFalse($service->validateFormat('INVALID'));
        $this->assertFalse($service->validateFormat('LIC-ABCD-EFGH'));
        $this->assertFalse($service->validateFormat('lic-abcd1234-efgh5678'));
        $this->assertFalse($service->validateFormat(''));
    }
}
```

- [ ] **Step 2: Create LicenseServiceTest**

Run: `php artisan make:test LicenseServiceTest --unit`

```php
<?php

namespace Tests\Unit\Services;

use App\Enums\LicenseStatus;
use App\Models\Device;
use App\Models\License;
use App\Services\LicenseKeyService;
use App\Services\LicenseService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class LicenseServiceTest extends TestCase
{
    use LazilyRefreshDatabase;

    private LicenseService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new LicenseService(new LicenseKeyService());
    }

    public function test_activate_creates_device_for_new_license(): void
    {
        $license = License::factory()->active()->create();

        $result = $this->service->activate(
            $license->license_key,
            'device-123',
            'Test Device'
        );

        $this->assertTrue($result['success']);
        $this->assertEquals('active', $result['status']);
        $this->assertEquals(1, $license->fresh()->devices()->count());
    }

    public function test_activate_is_idempotent_for_same_device(): void
    {
        $license = License::factory()->active()->withDevices(1)->create();
        $device = $license->devices->first();

        $result = $this->service->activate(
            $license->license_key,
            $device->device_id,
            $device->device_name
        );

        $this->assertTrue($result['success']);
        $this->assertEquals('Device already activated', $result['message']);
    }

    public function test_activate_returns_pending_when_max_devices_reached(): void
    {
        $license = License::factory()->active()->withMaxDevices(1)->withDevices(1)->create();

        $result = $this->service->activate(
            $license->license_key,
            'new-device-id',
            'New Device'
        );

        $this->assertFalse($result['success']);
        $this->assertEquals('pending_approval', $result['status']);
        $this->assertDatabaseHas('activation_requests', [
            'license_id' => $license->id,
            'status' => 'pending',
        ]);
    }

    public function test_validate_returns_valid_for_active_license(): void
    {
        $license = License::factory()->active()->withDevices(1)->create();
        $device = $license->devices->first();

        $result = $this->service->validate($license->license_key, $device->device_id);

        $this->assertTrue($result['valid']);
        $this->assertEquals('active', $result['status']);
    }

    public function test_validate_returns_invalid_for_expired_license(): void
    {
        $license = License::factory()->expired()->withDevices(1)->create();
        $device = $license->devices->first();

        $result = $this->service->validate($license->license_key, $device->device_id);

        $this->assertFalse($result['valid']);
        $this->assertEquals('expired', $result['status']);
    }

    public function test_validate_returns_invalid_for_device_mismatch(): void
    {
        $license = License::factory()->active()->withDevices(1)->create();

        $result = $this->service->validate($license->license_key, 'unknown-device');

        $this->assertFalse($result['valid']);
        $this->assertEquals('device_mismatch', $result['status']);
    }
}
```

- [ ] **Step 3: Create LicenseValidationTest (Feature)**

Run: `php artisan make:test LicenseValidationTest --feature`

```php
<?php

namespace Tests\Feature\Api;

use App\Models\Device;
use App\Models\License;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class LicenseValidationTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_can_validate_active_license(): void
    {
        $license = License::factory()->active()->withDevices(1)->create();
        $device = $license->devices->first();

        $response = $this->postJson('/api/license/validate', [
            'license_key' => $license->license_key,
            'device_id' => $device->device_id,
        ]);

        $response->assertOk();
        $response->assertJson(['valid' => true, 'status' => 'active']);
    }

    public function test_cannot_validate_expired_license(): void
    {
        $license = License::factory()->expired()->withDevices(1)->create();
        $device = $license->devices->first();

        $response = $this->postJson('/api/license/validate', [
            'license_key' => $license->license_key,
            'device_id' => $device->device_id,
        ]);

        $response->assertOk();
        $response->assertJson(['valid' => false, 'status' => 'expired']);
    }

    public function test_cannot_validate_revoked_license(): void
    {
        $license = License::factory()->revoked()->withDevices(1)->create();
        $device = $license->devices->first();

        $response = $this->postJson('/api/license/validate', [
            'license_key' => $license->license_key,
            'device_id' => $device->device_id,
        ]);

        $response->assertOk();
        $response->assertJson(['valid' => false, 'status' => 'revoked']);
    }

    public function test_cannot_validate_device_mismatch(): void
    {
        $license = License::factory()->active()->withDevices(1)->create();

        $response = $this->postJson('/api/license/validate', [
            'license_key' => $license->license_key,
            'device_id' => 'unknown-device',
        ]);

        $response->assertOk();
        $response->assertJson(['valid' => false, 'status' => 'device_mismatch']);
    }

    public function test_rejects_invalid_license_key_format(): void
    {
        $response = $this->postJson('/api/license/validate', [
            'license_key' => 'INVALID-KEY',
            'device_id' => 'device-123',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['license_key']);
    }
}
```

- [ ] **Step 4: Create LicenseActivationTest (Feature)**

Run: `php artisan make:test LicenseActivationTest --feature`

```php
<?php

namespace Tests\Feature\Api;

use App\Models\License;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class LicenseActivationTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_can_activate_first_device(): void
    {
        $license = License::factory()->active()->create();

        $response = $this->postJson('/api/license/activate', [
            'license_key' => $license->license_key,
            'device_id' => (string) Str::uuid(),
            'device_name' => 'Test Device',
        ]);

        $response->assertOk();
        $response->assertJson(['success' => true, 'status' => 'active']);
    }

    public function test_cannot_activate_expired_license(): void
    {
        $license = License::factory()->expired()->create();

        $response = $this->postJson('/api/license/activate', [
            'license_key' => $license->license_key,
            'device_id' => (string) Str::uuid(),
            'device_name' => 'Test Device',
        ]);

        $response->assertOk();
        $response->assertJson(['success' => false, 'status' => 'expired']);
    }

    public function test_device_limit_creates_pending_request(): void
    {
        $license = License::factory()->active()->withMaxDevices(1)->withDevices(1)->create();

        $response = $this->postJson('/api/license/activate', [
            'license_key' => $license->license_key,
            'device_id' => (string) Str::uuid(),
            'device_name' => 'New Device',
        ]);

        $response->assertOk();
        $response->assertJson(['success' => false, 'status' => 'pending_approval']);
    }

    public function test_rejects_invalid_license_key_format(): void
    {
        $response = $this->postJson('/api/license/activate', [
            'license_key' => 'INVALID',
            'device_id' => (string) Str::uuid(),
            'device_name' => 'Test',
        ]);

        $response->assertStatus(422);
    }
}
```

- [ ] **Step 5: Create LicenseCheckUpdateTest (Feature)**

Run: `php artisan make:test LicenseCheckUpdateTest --feature`

```php
<?php

namespace Tests\Feature\Api;

use App\Models\License;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class LicenseCheckUpdateTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_check_update_returns_no_update_for_active_license(): void
    {
        $license = License::factory()->active()->create();

        $response = $this->postJson('/api/license/check-update', [
            'license_key' => $license->license_key,
            'current_version' => '1.0.0',
        ]);

        $response->assertOk();
        $response->assertJson([
            'update_available' => false,
            'latest_version' => '1.0.0',
        ]);
    }

    public function test_check_update_returns_no_update_for_invalid_license(): void
    {
        $response = $this->postJson('/api/license/check-update', [
            'license_key' => 'LIC-ABCD1234-EFGH5678',
            'current_version' => '1.0.0',
        ]);

        $response->assertOk();
        $response->assertJson(['update_available' => false]);
    }

    public function test_rejects_invalid_license_key_format(): void
    {
        $response = $this->postJson('/api/license/check-update', [
            'license_key' => 'INVALID',
            'current_version' => '1.0.0',
        ]);

        $response->assertStatus(422);
    }
}
```

- [ ] **Step 6: Run tests**

Run: `php artisan test`

Expected: All tests pass

- [ ] **Step 7: Commit**

```bash
git add tests/
git commit -m "test: add licensing tests"
```

---

## Task 11: Lint and Final Verification

- [ ] **Step 1: Run Pint**

Run: `vendor/bin/pint --parallel`

Expected: No errors

- [ ] **Step 2: Run full test suite**

Run: `composer test`

Expected: All tests pass

- [ ] **Step 3: Verify routes are registered**

Run: `php artisan route:list --path=api`

Expected: Should show 3 license routes

- [ ] **Step 4: Commit final changes**

```bash
git add .
git commit -m "chore: final lint and verification"
```

---

## Self-Review Checklist

- [ ] All 6 database tables + users modification created
- [ ] All 4 enums created (LicenseStatus, ActivationRequestStatus, SubscriptionStatus, AuditAction)
- [ ] All 7 models created with proper relationships and scopes
- [ ] All 6 factories created with useful states (active, expired, etc.)
- [ ] Both services created (LicenseKeyService, LicenseService)
- [ ] All 3 form requests with validation rules
- [ ] All 3 API controllers (invokable)
- [ ] API routes configured with rate limiting
- [ ] CheckAdminMiddleware created and registered
- [ ] Admin layout created
- [ ] All 13 Volt admin pages created
- [ ] Both scheduler commands created
- [ ] All test files created with actual test code
- [ ] No placeholder text (TBD, TODO, etc.)
- [ ] All code follows Laravel conventions and PHP 8.3 features

---

## Summary

**6 Milestones:**
1. **Foundation** - Migrations, enums, models (Task 1-3)
2. **Core Business Logic** - Services, factories (Task 4-5)
3. **API Endpoints** - Controllers, routes, rate limiting (Task 6)
4. **Admin Panel** - Folio setup, middleware, layout, Volt pages (Task 7-8)
5. **Automation** - Scheduler commands (Task 9)
6. **Testing & Verification** - Tests, lint (Task 10-11)

**Folio Integration Points:**
- `FolioServiceProvider` registers path `resources/views/pages` with admin middleware
- All admin pages live in `resources/views/pages/admin/` - auto-routed by Folio
- Dynamic routes use `[model]` folder naming convention
- No manual route registration needed for admin pages
- Auth + `check.admin` middleware protects entire `/admin/*` route group

**Total Files to Create:**
- 8 migrations
- 4 enums
- 7 models (+ 1 modified)
- 5 factories
- 2 services
- 3 requests
- 3 controllers
- 1 middleware
- 1 FolioServiceProvider
- 1 layout + 1 admin-nav component
- 13 Volt pages (Folio file-based routing)
- 2 console commands
- 5 test files

**Total: ~52 files**

---

## Execution Options

**Plan complete and saved to `docs/superpowers/plans/2026-05-14-licensing-server.md`. Two execution options:**

**1. Subagent-Driven (recommended)** - I dispatch a fresh subagent per task, review between tasks, fast iteration

**2. Inline Execution** - Execute tasks in this session using executing-plans, batch execution with checkpoints

**Which approach?**