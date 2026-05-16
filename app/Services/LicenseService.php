<?php

namespace App\Services;

use App\Enums\LicenseStatus;
use App\Models\AuditLog;
use App\Models\License;

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
            return ['valid' => false, 'reason' => 'Lisensi adalah '.$license->status->value];
        }

        if ($license->expires_at && $license->expires_at->isPast()) {
            return ['valid' => false, 'reason' => 'Lisensi telah kedaluwarsa'];
        }

        return ['valid' => true, 'reason' => null];
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
