<?php

namespace App\Services;

use App\Enums\LicenseStatus;
use App\Events\LicenseCreated;
use App\Events\LicenseRestored;
use App\Events\LicenseRevoked;
use App\Events\LicenseSuspended;
use App\Models\License;
use Illuminate\Support\Facades\Event;

class LicenseService
{
    public function __construct(
        protected LicenseKeyService $keyService
    ) {}

    public function create(array $data): License
    {
        $data['key'] = $data['key'] ?? $this->keyService->generate();

        $license = License::create($data);

        Event::dispatch(new LicenseCreated($license));

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

        Event::dispatch(new LicenseSuspended($license));

        return true;
    }

    public function revoke(License $license): bool
    {
        $license->update(['status' => LicenseStatus::Revoked]);

        Event::dispatch(new LicenseRevoked($license));

        return true;
    }

    public function restore(License $license): bool
    {
        $license->update(['status' => LicenseStatus::Active]);

        Event::dispatch(new LicenseRestored($license));

        return true;
    }
}
