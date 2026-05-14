<?php

namespace App\Models;

use App\Enums\LicenseMode;
use App\Enums\LicenseStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class License extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'user_id',
        'subscription_plan_id',
        'key',
        'status',
        'mode',
        'max_devices',
        'expires_at',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'status' => LicenseStatus::class,
            'mode' => LicenseMode::class,
            'max_devices' => 'integer',
            'expires_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function subscriptionPlan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class);
    }

    public function devices(): HasMany
    {
        return $this->hasMany(Device::class);
    }

    public function activationRequests(): HasMany
    {
        return $this->hasMany(ActivationRequest::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
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
        return $this->devices()->count() < $this->max_devices;
    }
}