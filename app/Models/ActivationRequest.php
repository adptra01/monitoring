<?php

namespace App\Models;

use App\Enums\ActivationRequestStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class ActivationRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'license_id',
        'device_id',
        'code',
        'status',
        'expires_at',
        'activated_at',
        'rejection_reason',
    ];

    protected function casts(): array
    {
        return [
            'status' => ActivationRequestStatus::class,
            'expires_at' => 'datetime',
            'activated_at' => 'datetime',
        ];
    }

    public function license(): BelongsTo
    {
        return $this->belongsTo(License::class);
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }

    public static function generateCode(): string
    {
        return strtoupper(Str::random(8));
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function isPending(): bool
    {
        return $this->status === ActivationRequestStatus::Pending;
    }
}
