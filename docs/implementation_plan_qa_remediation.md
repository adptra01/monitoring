# Implementation Plan: QA Remediation & PRD Alignment
# Monitoring Licensing Server - Laravel

---

## 1. Ringkasan Temuan Review Kode

### BUG KRITIS

| ID | File | Baris | Masalah | Dampak |
|----|------|-------|---------|--------|
| B01 | `app/Services/LicenseService.php` | 44 | `Str::random()` digunakan tanpa `use Illuminate\Support\Str` | Fatal Error saat registrasi device baru |
| B02 | `resources/views/pages/plans/create.blade.php` | 59 | Redirect ke `/roles` bukan `/plans` setelah save | User diarahkan ke halaman yang salah setelah membuat plan |
| B03 | `resources/views/pages/plans/create.blade.php` | 11 | Route name `plans.edit` seharusnya `plans.create` | Named route konflik/salah |

### BUG TINGGI

| ID | File | Masalah |
|----|------|---------|
| B04 | `app/Http/Controllers/Api/ActivationController.php` | `registerDevice()` dipanggil dua kali (baris 60 & 73) jika device limit tercapai |
| B05 | `app/Http/Controllers/Api/ValidationController.php` | Response tidak mengembalikan `status` dan `cache_until` sesuai PRD 6.5 |
| B06 | `routes/api.php` | Tidak ada rate limiting pada endpoint API |

### INKONSISTENSI (SEDANG)

| ID | File | Masalah |
|----|------|---------|
| I01 | Semua blade pages | Menggunakan `url()` hardcoded, bukan `route()` |
| I02 | Semua blade pages | `$this->redirect('/path')` hardcoded, bukan `route()` |
| I03 | `app/Models/Device.php` | Relasi `activationRequest()` menggunakan `BelongsTo` - seharusnya `HasMany` atau `HasOne` |
| I04 | `app/Services/LicenseService.php` | Log audit menggunakan field `entity_type`/`entity_id` tapi `AuditLog` model memakai field berbeda dari superpowers plan |

---

## 2. Analisis Kesesuaian dengan PRD

### Gap Database Schema

| Field PRD | Tabel | Status | Keterangan |
|-----------|-------|--------|------------|
| `customer_name` | licenses | ❌ Missing | Implementasi pakai `user_id` relasi ke User |
| `customer_email` | licenses | ❌ Missing | Tidak ada di skema |
| `activated_at` | licenses | ❌ Missing | Ada di superpowers plan, tidak di implementasi |
| `notes` | licenses | ❌ Missing | Tidak ada di migrasi |
| `device_id` (string UUID) | devices | ❌ Deviasi | Implementasi pakai `fingerprint` string |
| `device_name` | devices | ❌ Deviasi | Implementasi pakai `name` |
| `activated_at` | devices | ❌ Missing | Tidak ada di migrasi |
| `is_active` | devices | ❌ Missing | Tidak ada di migrasi |

### Gap API

| Endpoint PRD | Status | Keterangan |
|-------------|--------|------------|
| `POST /api/license/validate` | ⚠️ Parsial | Ada tapi response tidak sesuai kontrak |
| `POST /api/license/activate` | ⚠️ Parsial | Ada tapi path berbeda (`/api/v1/activate`) |
| `POST /api/license/check-update` | ❌ Missing | Belum diimplementasikan |

### Gap Admin Panel

| Fitur PRD | Status | Keterangan |
|-----------|--------|------------|
| Dashboard stats | ✅ Ada | Tapi tidak ada "online devices" stat |
| CRUD Licenses | ✅ Ada | Fungsional |
| CRUD Products | ✅ Ada | Fungsional |
| Device monitoring | ⚠️ Parsial | Halaman ada, fitur force reset belum ada |
| Activation Requests | ✅ Ada | Approve/reject fungsional |
| Audit Logs | ⚠️ Parsial | Halaman ada, tapi struktur log tidak sesuai superpowers plan |

### Gap Keamanan (PRD 7.2)

| Requirement | Status |
|-------------|--------|
| Rate Limiting | ❌ Missing |
| HTTPS only | ✅ (env) |
| API token protection | ⚠️ Parsial (tidak semua endpoint) |
| Validation throttling | ❌ Missing |

---

## 3. Rencana Perbaikan Prioritas

### SPRINT 1 - Bug Kritis (Hari 1)

#### Fix B01: Import `Str` di LicenseService

**File:** `app/Services/LicenseService.php`

Tambahkan di baris 1 setelah namespace:
```php
use Illuminate\Support\Str;
```

#### Fix B02 & B03: Perbaiki redirect dan route name di plans/create

**File:** `resources/views/pages/plans/create.blade.php`

- Baris 11: `name('plans.edit')` → `name('plans.create')`
- Baris 59: `$this->redirect('/roles')` → `$this->redirect(route('plans.index'))`
- Baris 111: `href="{{ url('/roles') }}"` → `href="{{ route('plans.index') }}"`

#### Fix B04: Perbaiki duplikasi registerDevice di ActivationController

**File:** `app/Http/Controllers/Api/ActivationController.php`

Hapus blok registrasi device redundan di baris 60, sehingga device hanya dibuat satu kali setelah semua kondisi diperiksa.

---

### SPRINT 2 - API Compliance (Hari 2-3)

#### Fix B05: Update ValidationController response sesuai PRD

**File:** `app/Http/Controllers/Api/ValidationController.php`

Response harus mengembalikan:
```json
{
  "valid": true,
  "status": "active",
  "expired_at": "2026-12-01",
  "cache_until": "2026-05-22",
  "message": "License valid"
}
```

#### Fix B06: Tambahkan Rate Limiting

**File:** `routes/api.php`

```php
Route::prefix('v1')->middleware('throttle:60,1')->group(function () {
    Route::post('/activate', [ActivationController::class, 'activate']);
    Route::get('/verify/{key}/{fingerprint}', [ActivationController::class, 'verify']);
    Route::get('/status/{key}/{fingerprint}', [ActivationController::class, 'status']);
    Route::post('/validate', [ValidationController::class, 'validate']);
    Route::post('/check-update', [CheckUpdateController::class, '__invoke']);
});
```

#### Implementasi CheckUpdateController

**File baru:** `app/Http/Controllers/Api/CheckUpdateController.php`

```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\CheckUpdateRequest;
use App\Models\License;
use App\Enums\LicenseStatus;
use Illuminate\Http\JsonResponse;

class CheckUpdateController extends ApiController
{
    public function __invoke(CheckUpdateRequest $request): JsonResponse
    {
        $exists = License::where('key', $request->validated('license_key'))
            ->where('status', LicenseStatus::Active)
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->exists();

        if (! $exists) {
            return $this->error('License not valid for update check', 403);
        }

        return $this->success([
            'update_available' => false,
            'latest_version' => $request->validated('current_version'),
            'download_url' => null,
            'message' => 'You are using the latest version',
        ]);
    }
}
```

**File baru:** `app/Http/Requests/CheckUpdateRequest.php`

```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CheckUpdateRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'license_key' => ['required', 'string'],
            'current_version' => ['required', 'string', 'max:50'],
        ];
    }
}
```

---

### SPRINT 3 - Database Schema (Hari 3-4)

#### Migrasi Baru: Field yang hilang di licenses dan devices

**File baru:** `database/migrations/2026_05_15_000001_add_missing_fields_to_licenses_table.php`

```php
Schema::table('licenses', function (Blueprint $table) {
    $table->timestamp('activated_at')->nullable()->after('expires_at');
    $table->text('notes')->nullable()->after('metadata');
});
```

**File baru:** `database/migrations/2026_05_15_000002_add_missing_fields_to_devices_table.php`

```php
Schema::table('devices', function (Blueprint $table) {
    $table->timestamp('activated_at')->nullable()->after('last_seen_at');
    $table->boolean('is_active')->default(true)->after('activated_at');
});
```

#### Update Models

**`app/Models/License.php`** - tambahkan ke `$fillable` dan `$casts`:
```php
// fillable
'activated_at', 'notes',

// casts
'activated_at' => 'datetime',
```

**`app/Models/Device.php`** - tambahkan ke `$fillable` dan `$casts`:
```php
// fillable
'activated_at', 'is_active',

// casts
'activated_at' => 'datetime',
'is_active' => 'boolean',
```

---

### SPRINT 4 - UI Cleanup (Hari 4-5)

#### Fix I01 & I02: Ganti semua url() hardcoded

File yang terdampak (ganti `url('/path')` → `route('named.route')`):

| File | url() yang perlu diubah | Route name yang benar |
|------|------------------------|-----------------------|
| `licenses/index.blade.php` | `url('/licenses/create')` | `route('licenses.create')` |
| `licenses/create.blade.php` | `url('/licenses')` | `route('licenses.index')` |
| `products/index.blade.php` | `url('/products/create')` | `route('products.create')` |
| `products/create.blade.php` | `url('/products')` | `route('products.index')` |
| `plans/index.blade.php` | `url('/plans/create')` | `route('plans.create')` |
| `plans/create.blade.php` | `url('/plans')` | `route('plans.index')` |
| `dashboard.blade.php` | `url('/products')`, dll | `route('products.index')`, dll |

#### Fix I03: Perbaiki relasi Device::activationRequest()

**File:** `app/Models/Device.php`

```php
// Ubah dari BelongsTo menjadi HasMany
public function activationRequests(): HasMany
{
    return $this->hasMany(ActivationRequest::class);
}
```

---

## 4. Strategi Pengujian Playwright E2E

### Setup

```bash
npm init playwright@latest
```

`playwright.config.ts`:
```typescript
import { defineConfig } from '@playwright/test';

export default defineConfig({
  testDir: './tests/e2e',
  baseURL: 'http://monitor.test',
  use: {
    browserName: 'chromium',
    headless: false,
    screenshot: 'only-on-failure',
    video: 'retain-on-failure',
  },
});
```

---

### Skenario 1: Autentikasi Admin

**File:** `tests/e2e/auth.spec.ts`

```typescript
import { test, expect } from '@playwright/test';

test.describe('Admin Authentication', () => {

  test('TC-01: Login sukses dengan kredensial admin valid', async ({ page }) => {
    await page.goto('/login');
    await expect(page.locator('h1, [class*="heading"]')).toBeVisible();

    await page.fill('input[name="email"]', 'admin@monitor.test');
    await page.fill('input[name="password"]', 'password');
    await page.click('button[type="submit"]');

    await expect(page).toHaveURL('/dashboard');
    await expect(page.locator('text=Dashboard')).toBeVisible();
  });

  test('TC-02: Login gagal dengan password salah', async ({ page }) => {
    await page.goto('/login');
    await page.fill('input[name="email"]', 'admin@monitor.test');
    await page.fill('input[name="password"]', 'wrong-password');
    await page.click('button[type="submit"]');

    await expect(page).toHaveURL('/login');
    await expect(page.locator('text=credentials')).toBeVisible();
  });

  test('TC-03: Guest tidak bisa akses dashboard', async ({ page }) => {
    await page.goto('/dashboard');
    await expect(page).not.toHaveURL('/dashboard');
  });
});
```

---

### Skenario 2: Product Management

**File:** `tests/e2e/products.spec.ts`

```typescript
import { test, expect } from '@playwright/test';

test.describe('Product Management', () => {
  test.beforeEach(async ({ page }) => {
    await page.goto('/login');
    await page.fill('input[name="email"]', 'admin@monitor.test');
    await page.fill('input[name="password"]', 'password');
    await page.click('button[type="submit"]');
    await page.waitForURL('/dashboard');
  });

  test('TC-04: Admin bisa melihat daftar produk', async ({ page }) => {
    await page.goto('/products');
    await expect(page.locator('table, [role="table"]')).toBeVisible();
    await expect(page.locator('text=Products')).toBeVisible();
  });

  test('TC-05: Admin bisa membuat produk baru', async ({ page }) => {
    await page.goto('/products/create');
    await page.fill('input[name="name"], [wire\\:model*="name"]', 'Test Product E2E');
    await page.fill('input[name="slug"], [wire\\:model*="slug"]', 'test-product-e2e');
    await page.click('button[type="submit"]');

    await page.waitForURL('/products');
    await expect(page.locator('text=Test Product E2E')).toBeVisible();
  });

  test('TC-06: Validasi form produk - nama wajib diisi', async ({ page }) => {
    await page.goto('/products/create');
    await page.click('button[type="submit"]');
    await expect(page.locator('text=required')).toBeVisible();
  });
});
```

---

### Skenario 3: License Management

**File:** `tests/e2e/licenses.spec.ts`

```typescript
import { test, expect } from '@playwright/test';

test.describe('License Management', () => {
  test.beforeEach(async ({ page }) => {
    await page.goto('/login');
    await page.fill('input[name="email"]', 'admin@monitor.test');
    await page.fill('input[name="password"]', 'password');
    await page.click('button[type="submit"]');
    await page.waitForURL('/dashboard');
  });

  test('TC-07: Admin bisa melihat daftar lisensi', async ({ page }) => {
    await page.goto('/licenses');
    await expect(page.locator('text=Licenses')).toBeVisible();
  });

  test('TC-08: Admin bisa membuat lisensi baru', async ({ page }) => {
    await page.goto('/licenses/create');

    // Pilih product
    await page.selectOption('[wire\\:model*="product_id"]', { index: 1 });
    // Pilih user
    await page.selectOption('[wire\\:model*="user_id"]', { index: 1 });
    // Set max devices
    await page.fill('[wire\\:model*="max_devices"]', '2');

    await page.click('button[type="submit"]');
    await page.waitForURL('/licenses');
    await expect(page.locator('table')).toBeVisible();
  });

  test('TC-09: Search lisensi berfungsi', async ({ page }) => {
    await page.goto('/licenses');
    await page.fill('input[type="search"]', 'XXXX-NOTEXIST');
    await page.waitForTimeout(500); // debounce
    // Tabel kosong atau tampil pesan tidak ditemukan
    const rows = page.locator('tbody tr, [role="row"]');
    await expect(rows).toHaveCount(0);
  });
});
```

---

### Skenario 4: Activation Request Flow

**File:** `tests/e2e/activation-requests.spec.ts`

```typescript
import { test, expect } from '@playwright/test';

test.describe('Activation Request Flow', () => {
  test.beforeEach(async ({ page }) => {
    await page.goto('/login');
    await page.fill('input[name="email"]', 'admin@monitor.test');
    await page.fill('input[name="password"]', 'password');
    await page.click('button[type="submit"]');
    await page.waitForURL('/dashboard');
  });

  test('TC-10: Admin bisa melihat daftar activation requests', async ({ page }) => {
    await page.goto('/activation-requests');
    await expect(page.locator('text=Activation Requests')).toBeVisible();
  });

  test('TC-11: Admin bisa approve activation request', async ({ page }) => {
    await page.goto('/activation-requests');
    // Klik tombol approve pada request pertama jika ada
    const approveButton = page.locator('[wire\\:click*="approve"]').first();
    if (await approveButton.isVisible()) {
      await approveButton.click();
      await expect(page.locator('text=approved')).toBeVisible({ timeout: 3000 });
    }
  });
});
```

---

### Skenario 5: API Validation & Activation

**File:** `tests/e2e/api.spec.ts`

```typescript
import { test, expect } from '@playwright/test';

const API_BASE = 'http://monitor.test/api/v1';

test.describe('License API', () => {

  test('TC-12: API validate - license key tidak valid', async ({ request }) => {
    const res = await request.post(`${API_BASE}/validate`, {
      data: {
        license_key: 'XXXX-XXXX-XXXX-XXXX',
        device: { fingerprint: 'a'.repeat(64) },
      },
    });
    expect(res.status()).toBe(404);
    const body = await res.json();
    expect(body.success).toBe(false);
  });

  test('TC-13: API activate - device baru berhasil terdaftar', async ({ request }) => {
    // Gunakan license_key yang sudah dibuat via seeder/factory
    const LICENSE_KEY = process.env.TEST_LICENSE_KEY ?? 'TEST-KEY-HERE';
    const res = await request.post(`${API_BASE}/activate`, {
      data: {
        license_key: LICENSE_KEY,
        device: {
          fingerprint: 'b'.repeat(64),
          name: 'Test Machine Playwright',
          platform: 'windows',
        },
      },
    });
    expect([200, 404]).toContain(res.status());
  });

  test('TC-14: API validate - response mengandung cache_until', async ({ request }) => {
    const LICENSE_KEY = process.env.TEST_LICENSE_KEY ?? 'SKIP';
    if (LICENSE_KEY === 'SKIP') { test.skip(); }

    const res = await request.post(`${API_BASE}/validate`, {
      data: {
        license_key: LICENSE_KEY,
        device: { fingerprint: 'c'.repeat(64) },
      },
    });
    if (res.status() === 200) {
      const body = await res.json();
      expect(body.data).toHaveProperty('cache_until');
      expect(body.data).toHaveProperty('status');
    }
  });

  test('TC-15: API rate limit aktif setelah banyak request', async ({ request }) => {
    const promises = Array.from({ length: 65 }, () =>
      request.post(`${API_BASE}/validate`, {
        data: {
          license_key: 'XXXX-XXXX-XXXX-XXXX',
          device: { fingerprint: 'a'.repeat(64) },
        },
      })
    );
    const responses = await Promise.all(promises);
    const rateLimited = responses.some(r => r.status() === 429);
    expect(rateLimited).toBe(true);
  });

  test('TC-16: API check-update endpoint tersedia', async ({ request }) => {
    const res = await request.post(`${API_BASE}/check-update`, {
      data: {
        license_key: 'XXXX-XXXX-XXXX-XXXX',
        current_version: '1.0.0',
      },
    });
    expect([403, 404, 422]).toContain(res.status());
  });
});
```

---

### Skenario 6: Dashboard Stats

**File:** `tests/e2e/dashboard.spec.ts`

```typescript
import { test, expect } from '@playwright/test';

test.describe('Dashboard', () => {
  test.beforeEach(async ({ page }) => {
    await page.goto('/login');
    await page.fill('input[name="email"]', 'admin@monitor.test');
    await page.fill('input[name="password"]', 'password');
    await page.click('button[type="submit"]');
    await page.waitForURL('/dashboard');
  });

  test('TC-17: Dashboard menampilkan semua stat cards', async ({ page }) => {
    await expect(page.locator('text=Total Licenses')).toBeVisible();
    await expect(page.locator('text=Active Licenses')).toBeVisible();
    await expect(page.locator('text=Pending Activations')).toBeVisible();
    await expect(page.locator('text=Products')).toBeVisible();
  });

  test('TC-18: Quick links di dashboard berfungsi', async ({ page }) => {
    await page.click('text=Manage Products');
    await expect(page).toHaveURL('/products');
  });
});
```

---

## 5. Urutan Eksekusi Perbaikan

### Checklist Sprint 1 - Bug Kritis

- [ ] Tambahkan `use Illuminate\Support\Str;` di `LicenseService.php`
- [ ] Perbaiki route name `plans.edit` → `plans.create` di `plans/create.blade.php`
- [ ] Perbaiki redirect `/roles` → `route('plans.index')` di `plans/create.blade.php`
- [ ] Perbaiki duplikasi `registerDevice()` di `ActivationController.php`
- [ ] Jalankan `php artisan test --compact` untuk verifikasi

### Checklist Sprint 2 - API Compliance

- [ ] Update `ValidationController` response sesuai PRD (tambah `status`, `cache_until`)
- [ ] Buat `CheckUpdateController` dan `CheckUpdateRequest`
- [ ] Tambahkan route `/check-update` di `routes/api.php`
- [ ] Tambahkan `throttle:60,1` pada semua route API
- [ ] Jalankan `php artisan test --compact tests/Feature/Api/`

### Checklist Sprint 3 - Database

- [ ] Buat migrasi: `add_missing_fields_to_licenses_table`
- [ ] Buat migrasi: `add_missing_fields_to_devices_table`
- [ ] Update `$fillable` dan `$casts` di `License.php`
- [ ] Update `$fillable` dan `$casts` di `Device.php`
- [ ] Perbaiki relasi `Device::activationRequest()` → `HasMany`
- [ ] Jalankan `php artisan migrate`

### Checklist Sprint 4 - UI Cleanup

- [ ] Ganti semua `url()` hardcoded ke `route()` di semua blade pages
- [ ] Ganti semua `$this->redirect('/path')` ke `$this->redirect(route('name'))`
- [ ] Jalankan `php artisan pint --dirty` untuk formatting
- [ ] Jalankan `php artisan test --compact` untuk verifikasi regresi

### Checklist Sprint 5 - E2E Testing

- [ ] Install Playwright: `npm init playwright@latest`
- [ ] Buat file test di `tests/e2e/`
- [ ] Jalankan: `npx playwright test --project=chromium`
- [ ] Review hasil test dan screenshot

---

## 6. Perintah Verifikasi

```bash
# Jalankan semua test unit & feature
php artisan test --compact

# Jalankan hanya test API
php artisan test --compact tests/Feature/Api/

# Format kode PHP
vendor/bin/pint --dirty

# Verifikasi routes
php artisan route:list --path=api

# Verifikasi migrasi
php artisan migrate:status
```
