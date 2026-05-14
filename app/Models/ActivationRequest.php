<?php

namespace App\Models;

use App\Enums\ActivationRequestStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
