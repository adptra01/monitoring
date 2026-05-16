<?php

namespace App\DTOs;

use Carbon\Carbon;
use Carbon\CarbonInterface;

class OfflineTokenData
{
    public function __construct(
        public readonly string $licenseKey,
        public readonly string $deviceFingerprint,
        public readonly string $productSlug,
        public readonly CarbonInterface $issuedAt,
        public readonly CarbonInterface $offlineUntil,
        public readonly int $graceSeconds,
        public readonly CarbonInterface $serverTime,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            licenseKey: $data['license_key'],
            deviceFingerprint: $data['device_fingerprint'],
            productSlug: $data['product_slug'],
            issuedAt: Carbon::parse($data['issued_at']),
            offlineUntil: Carbon::parse($data['offline_until']),
            graceSeconds: $data['grace_seconds'],
            serverTime: Carbon::parse($data['server_time']),
        );
    }

    public function toArray(): array
    {
        return [
            'license_key' => $this->licenseKey,
            'device_fingerprint' => $this->deviceFingerprint,
            'product_slug' => $this->productSlug,
            'issued_at' => $this->issuedAt->toIso8601String(),
            'offline_until' => $this->offlineUntil->toIso8601String(),
            'grace_seconds' => $this->graceSeconds,
            'server_time' => $this->serverTime->toIso8601String(),
        ];
    }
}
