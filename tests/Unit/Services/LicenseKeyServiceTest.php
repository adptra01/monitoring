<?php

namespace Tests\Unit\Services;

use App\Services\LicenseKeyService;
use PHPUnit\Framework\TestCase;

class LicenseKeyServiceTest extends TestCase
{
    private LicenseKeyService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new LicenseKeyService;
    }

    public function test_generate_returns_correct_format(): void
    {
        $key = $this->service->generate();

        $this->assertMatchesRegularExpression('/^[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}$/', $key);
    }

    public function test_generate_returns_unique_keys(): void
    {
        $keys = [];
        for ($i = 0; $i < 100; $i++) {
            $keys[] = $this->service->generate();
        }

        $this->assertCount(100, array_unique($keys));
    }

    public function test_validate_format_accepts_valid_key(): void
    {
        $validKey = 'ABCD-EFGH-IJKL-MNOP';

        $this->assertTrue($this->service->validateFormat($validKey));
    }

    public function test_validate_format_rejects_invalid_key(): void
    {
        $this->assertFalse($this->service->validateFormat('invalid'));
        $this->assertFalse($this->service->validateFormat('ABCD-EFGH-IJKL'));
        $this->assertFalse($this->service->validateFormat('abcd-efgh-ijkl-mnop'));
        $this->assertFalse($this->service->validateFormat(''));
    }

    public function test_mask_hides_middle_segments(): void
    {
        $key = 'ABCD-EFGH-IJKL-MNOP';
        $masked = $this->service->mask($key);

        $this->assertEquals('ABCD-****-****-MNOP', $masked);
    }

    public function test_mask_handles_invalid_key(): void
    {
        $this->assertEquals('****-****-****-****', $this->service->mask('invalid'));
    }
}
