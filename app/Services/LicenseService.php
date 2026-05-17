<?php

namespace App\Services;

use App\Enums\LicenseStatus;
use App\Models\License;

class LicenseService
{
    public function __construct(
        protected LicenseKeyService $keyService
    ) {}

    public function create(array $data): License
    {
        $data['key'] = $data['key'] ?? $this->keyService->generate();

        return License::create($data);
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

        return true;
    }

    public function revoke(License $license): bool
    {
        $license->update(['status' => LicenseStatus::Revoked]);

        return true;
    }

    public function restore(License $license): bool
    {
        $license->update(['status' => LicenseStatus::Active]);

        return true;
    }
}
