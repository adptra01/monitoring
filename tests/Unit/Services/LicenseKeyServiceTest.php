<?php

namespace Tests\Unit\Services;

use App\Services\LicenseKeyService;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class LicenseKeyServiceTest extends TestCase
{
    private LicenseKeyService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new LicenseKeyService;
    }

    #[Test]
    public function it_generates_license_key_with_correct_format(): void
    {
        $key = $this->service->generate();

        $this->assertMatchesRegularExpression('/^LIC-[A-Z0-9]{8}-[A-Z0-9]{8}$/', $key);
    }

    #[Test]
    public function it_generates_unique_keys(): void
    {
        $keys = array_map(fn () => $this->service->generate(), range(1, 100));

        $this->assertCount(100, array_unique($keys));
    }

    #[Test]
    public function it_validates_correct_format(): void
    {
        $this->assertTrue($this->service->validateFormat('LIC-A1B2C3D4-E5F6G7H8'));
    }

    #[Test]
    public function it_rejects_missing_prefix(): void
    {
        $this->assertFalse($this->service->validateFormat('A1B2C3D4-E5F6G7H8'));
    }

    #[Test]
    public function it_rejects_wrong_prefix(): void
    {
        $this->assertFalse($this->service->validateFormat('ABC-A1B2C3D4-E5F6G7H8'));
    }

    #[Test]
    public function it_rejects_lowercase_characters(): void
    {
        $this->assertFalse($this->service->validateFormat('LIC-a1b2c3d4-e5f6g7h8'));
    }

    #[Test]
    public function it_rejects_too_short_segments(): void
    {
        $this->assertFalse($this->service->validateFormat('LIC-A1B2-E5F6G7H8'));
    }

    #[Test]
    public function it_rejects_empty_string(): void
    {
        $this->assertFalse($this->service->validateFormat(''));
    }
}
