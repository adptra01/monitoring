<?php

namespace App\Services;

use App\DTOs\OfflineTokenData;
use App\Models\Device;
use App\Models\License;
use App\Models\LicenseToken;
use Carbon\Carbon;
use Illuminate\Support\Facades\Crypt;

class OfflineTokenService
{
    protected const GRACE_SECONDS = 604800;

    protected const MAX_CLOCK_DRIFT = 3600;

    protected const TOKEN_TTL_SECONDS = 604800;

    public function issue(License $license, Device $device): array
    {
        $now = Carbon::now();
        $offlineUntil = $now->copy()->addSeconds(static::GRACE_SECONDS);

        $data = new OfflineTokenData(
            licenseKey: $license->key,
            deviceFingerprint: $device->fingerprint,
            productSlug: $license->product->slug,
            issuedAt: $now,
            offlineUntil: $offlineUntil,
            graceSeconds: static::GRACE_SECONDS,
            serverTime: $now,
        );

        $payload = json_encode($data->toArray());
        $encrypted = Crypt::encryptString($payload);
        $tokenHash = hash('sha256', $encrypted);

        LicenseToken::create([
            'license_id' => $license->id,
            'device_id' => $device->id,
            'token_hash' => $tokenHash,
            'issued_at' => $now,
            'expires_at' => $offlineUntil,
        ]);

        return [
            'token' => $encrypted,
            'offline_until' => $offlineUntil->toIso8601String(),
        ];
    }

    public function verify(string $encryptedToken, string $fingerprint): array
    {
        try {
            $payload = Crypt::decryptString($encryptedToken);
        } catch (\Exception) {
            return ['valid' => false, 'reason' => 'Token tidak valid'];
        }

        $dataArray = json_decode($payload, true);

        if (! $dataArray || ! isset($dataArray['device_fingerprint'])) {
            return ['valid' => false, 'reason' => 'Format token tidak valid'];
        }

        if ($dataArray['device_fingerprint'] !== $fingerprint) {
            return ['valid' => false, 'reason' => 'Token tidak cocok dengan perangkat'];
        }

        $data = OfflineTokenData::fromArray($dataArray);
        $now = Carbon::now();

        $serverTime = $data->serverTime;
        $drift = abs($now->diffInSeconds($serverTime));

        if ($drift > static::MAX_CLOCK_DRIFT) {
            return ['valid' => false, 'reason' => 'Deteksi manipulasi waktu'];
        }

        if ($now->greaterThan($data->offlineUntil)) {
            return ['valid' => false, 'reason' => 'Token offline telah kedaluwarsa'];
        }

        $tokenHash = hash('sha256', $encryptedToken);
        $storedToken = LicenseToken::where('token_hash', $tokenHash)->first();

        if (! $storedToken) {
            return ['valid' => false, 'reason' => 'Token tidak ditemukan di server'];
        }

        if ($storedToken->revoked_at) {
            return ['valid' => false, 'reason' => 'Token telah dicabut'];
        }

        return [
            'valid' => true,
            'data' => $data,
            'token' => $storedToken,
        ];
    }

    public function refresh(License $license, Device $device, string $encryptedToken): array
    {
        $verification = $this->verify($encryptedToken, $device->fingerprint);

        if (! $verification['valid']) {
            return $verification;
        }

        $tokenHash = hash('sha256', $encryptedToken);
        LicenseToken::where('token_hash', $tokenHash)->update(['revoked_at' => Carbon::now()]);

        return $this->issue($license, $device);
    }

    public function revokeByLicense(License $license): void
    {
        LicenseToken::where('license_id', $license->id)
            ->whereNull('revoked_at')
            ->update(['revoked_at' => Carbon::now()]);
    }

    public function revokeByDevice(Device $device): void
    {
        LicenseToken::where('device_id', $device->id)
            ->whereNull('revoked_at')
            ->update(['revoked_at' => Carbon::now()]);
    }
}
