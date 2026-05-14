<?php

namespace App\Models;

use App\Enums\LicenseStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

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
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'date:Y-m-d',
            'expired_at' => 'date:Y-m-d',
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

    public function currentSubscription(): HasOne
    {
        return $this->hasOne(Subscription::class)
            ->where('status', 'active')
            ->latestOfMany();
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

    public function scopeSuspended(Builder $query): void
    {
        $query->where('status', LicenseStatus::Suspended);
    }

    public function scopeRevoked(Builder $query): void
    {
        $query->where('status', LicenseStatus::Revoked);
    }

    public function scopeExpiringSoon(Builder $query, int $days = 7): void
    {
        $query->where('status', LicenseStatus::Active)
            ->whereDate('expired_at', '<=', now()->addDays($days))
            ->whereDate('expired_at', '>', now());
    }

    public function scopeWhereKey(Builder $query, string $licenseKey): void
    {
        $query->where('license_key', $licenseKey);
    }

    public function isActive(): bool
    {
        return $this->status === LicenseStatus::Active->value
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
