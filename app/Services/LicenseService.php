<?php

namespace App\Services;

use App\Enums\ActivationRequestStatus;
use App\Enums\LicenseStatus;
use App\Models\License;
use App\Models\Device;
use App\Models\ActivationRequest;
use App\Models\AuditLog;
use Illuminate\Support\Carbon;

class LicenseService
{
    public function __construct(
        protected LicenseKeyService $keyService
    ) {}

    public function create(array $data): License
    {
        $data['key'] = $data['key'] ?? $this->keyService->generate();

        $license = License::create($data);

        $this->log($license, 'created', $license->toArray());

        return $license;
    }

    public function validate(License $license): array
    {
        if ($license->status !== LicenseStatus::Active) {
            return ['valid' => false, 'reason' => 'License is ' . $license->status->value];
        }

        if ($license->expires_at && $license->expires_at->isPast()) {
            return ['valid' => false, 'reason' => 'License has expired'];
        }

        return ['valid' => true, 'reason' => null];
    }

    public function registerDevice(License $license, array $deviceData): Device
    {
        $deviceData['fingerprint'] = $deviceData['fingerprint'] ?? Str::random(64);

        $device = $license->devices()->create($deviceData);

        $this->log($license, 'device_registered', ['device_id' => $device->id]);

        return $device;
    }

    public function checkDeviceLimit(License $license): bool
    {
        return $license->devices()->count() < $license->max_devices;
    }

    public function createActivationRequest(Device $device): ActivationRequest
    {
        $license = $device->license;

        if ($license->mode->requiresActivation() === false) {
            $device->last_seen_at = now();
            $device->save();

            return null;
        }

        $existingRequest = ActivationRequest::where('license_id', $license->id)
            ->where('device_id', $device->id)
            ->where('status', ActivationRequestStatus::Pending)
            ->where('expires_at', '>', now())
            ->first();

        if ($existingRequest) {
            return $existingRequest;
        }

        $request = ActivationRequest::create([
            'license_id' => $license->id,
            'device_id' => $device->id,
            'code' => ActivationRequest::generateCode(),
            'status' => ActivationRequestStatus::Pending,
            'expires_at' => now()->addMinutes(30),
        ]);

        $this->log($license, 'activation_request_created', [
            'request_id' => $request->id,
            'device_id' => $device->id,
        ]);

        return $request;
    }

    public function approveActivationRequest(ActivationRequest $request, ?string $userId = null): bool
    {
        $request->update([
            'status' => ActivationRequestStatus::Approved,
            'activated_at' => now(),
        ]);

        $request->device->update(['last_seen_at' => now()]);

        $this->log($request->license, 'activation_approved', [
            'request_id' => $request->id,
            'device_id' => $request->device_id,
            'approved_by' => $userId,
        ]);

        return true;
    }

    public function rejectActivationRequest(ActivationRequest $request, string $reason, ?string $userId = null): bool
    {
        $request->update([
            'status' => ActivationRequestStatus::Rejected,
            'rejection_reason' => $reason,
        ]);

        $this->log($request->license, 'activation_rejected', [
            'request_id' => $request->id,
            'device_id' => $request->device_id,
            'reason' => $reason,
            'rejected_by' => $userId,
        ]);

        return true;
    }

    public function suspend(License $license): bool
    {
        $license->update(['status' => LicenseStatus::Suspended]);
        $this->log($license, 'suspended', ['previous_status' => 'active']);
        return true;
    }

    public function revoke(License $license): bool
    {
        $license->update(['status' => LicenseStatus::Revoked]);
        $this->log($license, 'revoked', ['previous_status' => $license->getOriginal('status')]);
        return true;
    }

    public function restore(License $license): bool
    {
        $license->update(['status' => LicenseStatus::Active]);
        $this->log($license, 'restored', ['previous_status' => $license->getOriginal('status')]);
        return true;
    }

    public function verifyActivation(Device $device, string $code): array
    {
        $request = ActivationRequest::where('device_id', $device->id)
            ->where('code', $code)
            ->where('status', ActivationRequestStatus::Approved)
            ->where('activated_at', '>', now()->subDays(7))
            ->first();

        if ($request) {
            $device->update(['last_seen_at' => now()]);
            return ['valid' => true, 'offline_until' => $request->activated_at->addDays(7)];
        }

        if ($device->last_seen_at && $device->last_seen_at->isAfter(now()->subDays(7))) {
            return ['valid' => true, 'offline_until' => $device->last_seen_at->addDays(7)];
        }

        return ['valid' => false, 'reason' => 'Device not activated or offline grace period expired'];
    }

    protected function log(License $license, string $action, array $changes = [], ?int $userId = null): void
    {
        AuditLog::create([
            'action' => $action,
            'entity_type' => License::class,
            'entity_id' => $license->id,
            'user_id' => $userId,
            'new_values' => $changes,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'created_at' => now(),
        ]);
    }
}