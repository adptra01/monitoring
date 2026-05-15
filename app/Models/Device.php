<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Device extends Model
{
    use HasFactory;

    protected $fillable = [
        'license_id',
        'name',
        'fingerprint',
        'platform',
        'platform_version',
        'app_version',
        'ip_address',
        'last_seen_at',
        'activated_at',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'last_seen_at' => 'datetime',
            'activated_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    public function license(): BelongsTo
    {
        return $this->belongsTo(License::class);
    }

    public function activationRequests(): HasMany
    {
        return $this->hasMany(ActivationRequest::class);
    }
}
