<?php

namespace App\Models;

use App\Enums\LicenseStatus;
use App\Services\LicenseKeyService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class License extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'user_id',
        'subscription_plan_id',
        'key',
        'status',
        'max_devices',
        'expires_at',
        'starts_at',
        'notes',
        'customer_name',
        'customer_phone',
        'customer_store',
        'customer_address',
        'devices',
    ];

    protected function casts(): array
    {
        return [
            'status' => LicenseStatus::class,
            'max_devices' => 'integer',
            'expires_at' => 'datetime',
            'starts_at' => 'datetime',
            'devices' => 'array',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function subscriptionPlan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class);
    }

    public static function generateKey(): string
    {
        return app(LicenseKeyService::class)->generate();
    }

    public function isValid(): bool
    {
        if ($this->status !== LicenseStatus::Active) {
            return false;
        }

        if ($this->expires_at && $this->expires_at->isPast()) {
            return false;
        }

        return true;
    }

    public function hasAvailableSlots(): bool
    {
        $devices = $this->devices ?? [];

        return count($devices) < $this->max_devices;
    }

    public function registeredDevicesCount(): int
    {
        return count($this->devices ?? []);
    }
}
