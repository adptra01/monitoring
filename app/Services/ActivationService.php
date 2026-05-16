<?php

namespace App\Services;

use App\Enums\ActivationRequestStatus;
use App\Events\ActivationApproved;
use App\Events\ActivationRejected;
use App\Models\ActivationRequest;
use App\Models\Device;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Event;

class ActivationService
{
    protected const OFFLINE_GRACE_DAYS = 7;

    public function createRequest(Device $device): ?ActivationRequest
    {
        $license = $device->license;

        if ($license->mode->requiresActivation() === false) {
            $device->update(['last_seen_at' => now()]);

            return null;
        }

        $existing = ActivationRequest::where('license_id', $license->id)
            ->where('device_id', $device->id)
            ->where('status', ActivationRequestStatus::Pending)
            ->where('expires_at', '>', now())
            ->first();

        if ($existing) {
            return $existing;
        }

        $request = ActivationRequest::create([
            'license_id' => $license->id,
            'device_id' => $device->id,
            'code' => ActivationRequest::generateCode(),
            'status' => ActivationRequestStatus::Pending,
            'expires_at' => now()->addMinutes(30),
        ]);

        return $request;
    }

    public function approveRequest(ActivationRequest $request, ?string $userId = null): bool
    {
        $request->update([
            'status' => ActivationRequestStatus::Approved,
            'activated_at' => now(),
        ]);

        $request->device->update(['last_seen_at' => now()]);

        Event::dispatch(new ActivationApproved($request, $userId));

        return true;
    }

    public function rejectRequest(ActivationRequest $request, string $reason, ?string $userId = null): bool
    {
        $request->update([
            'status' => ActivationRequestStatus::Rejected,
            'rejection_reason' => $reason ?: 'Ditolak oleh admin',
        ]);

        Event::dispatch(new ActivationRejected($request, $reason, $userId));

        return true;
    }

    public function verifyActivation(Device $device, string $code): array
    {
        $request = ActivationRequest::where('device_id', $device->id)
            ->where('code', $code)
            ->where('status', ActivationRequestStatus::Approved)
            ->where('activated_at', '>', now()->subDays(static::OFFLINE_GRACE_DAYS))
            ->first();

        if ($request) {
            $device->update(['last_seen_at' => now()]);

            return [
                'valid' => true,
                'offline_until' => $request->activated_at->addDays(static::OFFLINE_GRACE_DAYS),
            ];
        }

        if ($device->last_seen_at && $device->last_seen_at->isAfter(now()->subDays(static::OFFLINE_GRACE_DAYS))) {
            return [
                'valid' => true,
                'offline_until' => $device->last_seen_at->addDays(static::OFFLINE_GRACE_DAYS),
            ];
        }

        return ['valid' => false, 'reason' => 'Perangkat tidak diaktifkan atau masa tenggang offline telah kedaluwarsa'];
    }

    public function isWithinGracePeriod(Device $device): bool
    {
        $approvedRequest = ActivationRequest::where('device_id', $device->id)
            ->where('status', ActivationRequestStatus::Approved)
            ->where('activated_at', '>', now()->subDays(static::OFFLINE_GRACE_DAYS))
            ->first();

        if ($approvedRequest) {
            return true;
        }

        return $device->last_seen_at && $device->last_seen_at->isAfter(now()->subDays(static::OFFLINE_GRACE_DAYS));
    }

    public function calculateOfflineUntil(Device $device): ?CarbonInterface
    {
        $approvedRequest = ActivationRequest::where('device_id', $device->id)
            ->where('status', ActivationRequestStatus::Approved)
            ->orderByDesc('activated_at')
            ->first();

        if ($approvedRequest) {
            return $approvedRequest->activated_at->addDays(static::OFFLINE_GRACE_DAYS);
        }

        if ($device->last_seen_at && $device->last_seen_at->isAfter(now()->subDays(static::OFFLINE_GRACE_DAYS))) {
            return $device->last_seen_at->addDays(static::OFFLINE_GRACE_DAYS);
        }

        return null;
    }
}
