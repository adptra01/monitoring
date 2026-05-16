# Architecture Audit — License Monitor

> Audit arsitektur dan technical readiness untuk Central License Server
> Tanggal: 16 Mei 2026

---

## Executive Summary

License Monitor memiliki fondasi arsitektur yang solid untuk licensing SaaS skala kecil-menengah. Service layer sudah terpisah, enum sudah digunakan dengan benar, API memiliki struktur yang jelas, dan database sudah dinormalisasi dengan baik. Namun terdapat beberapa gap kritis yang harus diselesaikan sebelum sistem siap digunakan sebagai central license server production.

**Masalah paling kritis:**
1. **Tidak ada signed offline token** — grace period saat ini hanya berdasarkan timestamp server, tanpa cryptographic signing. Client bisa dengan mudah memalsukan offline_until.
2. **Tidak ada license key signing** — key hanya random string, tidak bisa diverifikasi offline tanpa server.
3. **Fingerprint binding terlalu longgar** — tanpa signing, fingerprint bisa dipalsukan.
4. **API belum memiliki authentication** — endpoint API bisa diakses siapa saja yang tahu license key.
5. **CheckUpdateController hanya placeholder** — tidak ada version tracking sama sekali.
6. **Tidak ada anti-clock-tampering** — client bisa memundurkan jam untuk memperpanjang lisensi.
7. **Tidak ada revocation broadcast** — lisensi yang di-revoke tidak bisa dikomunikasikan ke client offline.

---

## Overall Scores

| Dimension | Score | Grade |
|-----------|:-----:|:-----:|
| **Overall Architecture** | **72/100** | C+ |
| **Production Readiness** | **55/100** | D |
| **Security Readiness** | **38/100** | F |
| **Offline-First Compatibility** | **30/100** | F |
| **Scalability** | **70/100** | C+ |
| **Maintainability** | **80/100** | B- |

---

## 1. Backend Architecture Readiness

### 1.1 Service Layer

| Kriteria | Status | Notes |
|----------|--------|-------|
| Separation of concerns | ✅ Baik | Controllers thin, Services厚 |
| DI (Dependency Injection) | ✅ Baik | Constructor injection di controllers |
| Single Responsibility | ⚠️ Sedang | LicenseService terlalu besar (mix validation, device mgmt, activation, suspend/revoke) |
| Testability | ✅ Baik | Services bisa di-mock dengan mudah |
| Extensibility | ⚠️ Sedang | Tidak ada interfaces/contracts |

**Temuan:**
- `LicenseService` melanggar SRP — menangani validasi, device registration, activation request, suspend/revoke/restore. Untuk production, ini perlu dipecah.
- Tidak ada `interface` untuk service — menyulitkan testing lanjutan dan future SDK.
- `LicenseKeyService` sangat baik — fokus, testable, single responsibility.
- `GitHubService` baik — terisolasi, ter-cache, pagination handle.

### 1.2 Controller Layer

| Kriteria | Status | Notes |
|----------|--------|-------|
| Thin controllers | ✅ Baik | API controllers hanya routing + response formatting |
| Validation | ✅ Baik | FormRequest sudah digunakan |
| Response consistency | ⚠️ Sedang | `ApiController` base class OK, tapi response structure minimal |
| Error handling | ⚠️ Sedang | Tidak ada global exception handler untuk API |

**Temuan:**
- Base `ApiController` dengan `success()` dan `error()` helper sudah baik.
- Tidak ada API Resource classes — data mentah dari controller langsung di-return.
- Tidak ada global exception handler — `ModelNotFoundException`, `ValidationException` dll tidak di-catch secara terpusat.
- Tidak ada API documentation (OpenAPI/Swagger).

### 1.3 Middleware Structure

| Kriteria | Status | Notes |
|----------|--------|-------|
| Admin middleware | ✅ Baik | CheckAdminMiddleware clean |
| API middleware | ⚠️ Sedang | Tidak ada API auth middleware |
| Licensing middleware | ❌ Tidak ada | Tidak ada middleware untuk client licensing flow |
| Rate limiting | ⚠️ Sedang | Throttle:60,1 di API — tidak ada per-endpoint limit |

**Temuan:**
- **CRITICAL:** Tidak ada API authentication. License server endpoints bisa diakses siapa saja dengan license key yang valid. Tidak ada API key, tidak ada IP whitelist, tidak ada HMAC signing.
- Middleware untuk licensing flow (redirect jika unlicensed, grace period check, dll) — ini di sisi client, tapi tidak disediakan sebagai publishable package.

### 1.4 API Versioning Strategy

| Kriteria | Status | Notes |
|----------|--------|-------|
| URL versioning | ✅ Baik | `/api/v1/` prefix |
| Backward compat | ✅ Baik | v1 namespace terisolasi |
| Future-proof | ⚠️ Sedang | Tidak ada Content Negotiation / Accept header versioning |

### 1.5 Business Logic Isolation

**Temuan:**
- Business logic mayoritas di `LicenseService` — sudah baik untuk ukuran kecil.
- Tidak ada `Action` classes — validasi, device binding, activation flow semuanya di satu service.
- Tidak ada `DTO` (Data Transfer Objects) — data passing menggunakan array.
- Tidak ada `Events` untuk licensing flow — tidak ada event listener untuk aktivasi, revoke, expired.

**Rekomendasi:**
- Pisahkan `LicenseService` menjadi:
  - `LicenseValidationService` — validasi status, expired, device limit
  - `DeviceRegistrationService` — register, fingerprint check
  - `ActivationService` — activation request lifecycle
  - `LicenseStatusService` — suspend, revoke, restore
- Tambahkan Event: `LicenseActivated`, `LicenseRevoked`, `LicenseExpired`, `DeviceRegistered`
- Tambahkan Listener: invalidate cache, kirim notifikasi, webhook callbacks
- Gunakan DTO untuk response licensing yang konsisten

---

## 2. Database Readiness

### 2.1 Relational Structure

| Kriteria | Status | Notes |
|----------|--------|-------|
| Normalization | ✅ Baik | Normalisasi 3NF |
| Foreign keys | ✅ Baik | Relasi proper |
| Indexing | ⚠️ Sedang | Hanya primary key + slug index |
| Polymorphic audit | ✅ Baik | AuditLog menggunakan entity_type/entity_id |

**Temuan:**
- Struktur relasional sudah sangat baik untuk licensing system.
- **Tidak ada index pada:** `licenses.key`, `licenses.status`, `devices.fingerprint`, `activation_requests.status`, `audit_logs.action`, `audit_logs.entity_type`, `audit_logs.created_at`.
- Untuk production dengan 100k+ license, missing index akan menjadi masalah serius.

### 2.2 Missing Tables

| Table | Purpose | Priority |
|-------|---------|----------|
| `license_tokens` | Signed offline tokens for client validation | 🔴 CRITICAL |
| `activation_codes` | One-time activation codes with expiry | 🟡 Medium (bisa merge dengan activation_requests) |
| `api_clients` | Client app registrations with API keys | 🟡 Medium |
| `webhook_endpoints` | Customer webhook URLs | 🟢 Low |
| `license_events` | Event log terpisah untuk client-facing | 🟢 Low |

### 2.3 Indexing Strategy

```sql
-- Missing indexes that need to be added:
CREATE INDEX idx_licenses_key ON licenses(key);                          -- Most queried column
CREATE INDEX idx_licenses_status ON licenses(status);                    -- Filter queries
CREATE INDEX idx_licenses_expires_at ON licenses(expires_at);            -- Expiry checks
CREATE INDEX idx_devices_fingerprint ON devices(fingerprint);            -- Device lookup
CREATE INDEX idx_devices_license_id ON devices(license_id);              -- Join performance
CREATE INDEX idx_activation_requests_status ON activation_requests(status);
CREATE INDEX idx_audit_logs_entity ON audit_logs(entity_type, entity_id);
CREATE INDEX idx_audit_logs_created_at ON audit_logs(created_at);        -- Audit queries
CREATE INDEX idx_subscriptions_status ON subscriptions(status);
```

### 2.4 Token Management Readiness

| Feature | Status | Notes |
|---------|--------|-------|
| Offline token storage | ❌ Tidak ada | Tidak ada kolom/tabel untuk signed offline token |
| Signed activation sessions | ❌ Tidak ada | Tidak ada cryptographic signing |
| Grace period tracking | ⚠️ Parsial | Hanya based on `activated_at + 7 days` dan `last_seen_at + 7 days` |
| Periodic revalidation | ❌ Tidak ada | Tidak ada mekanisme revalidation |
| Device reset approve | ✅ Ada | Activation request flow sudah menangani ini |
| Anti-abuse monitoring | ❌ Tidak ada | Tidak ada rate limiting per-device atau per-IP |

---

## 3. API Licensing Readiness

### 3.1 Endpoint Audit

| Endpoint | Method | Purpose | Readiness |
|----------|--------|---------|-----------|
| `/api/v1/activate` | POST | Register device | ⚠️ Missing signed token response |
| `/api/v1/verify/{key}/{fp}` | GET | Verify activation | ⚠️ Token verification lemah |
| `/api/v1/status/{key}/{fp}` | GET | Check status | ✅ OK |
| `/api/v1/validate` | POST | Validate license | ⚠️ Missing device binding validation |
| `/api/v1/check-update` | POST | Check app update | ❌ Placeholder — tidak berguna |

### 3.2 API Design Issues

**1. Tidak ada API authentication.**
  - `POST /api/v1/activate` bisa dipanggil siapa saja yang tahu license key.
  - **Risk:** Brute-force license key enumeration.
  - **Fix:** Integrate API key + HMAC signing untuk setiap request.

**2. GET endpoints menggunakan API keys di URL.**
  - `GET /api/v1/verify/{key}/{fingerprint}` — license key dan fingerprint ada di URL.
  - **Risk:** Logging di server/proxy mencatat credentials.
  - **Fix:** Gunakan POST dengan body terenkripsi atau minimal header-based auth.

**3. Tidak ada endpoint batch.**
  - Client harus satu-satu validate per device.
  - Untuk server dengan 1000+ client, ini tidak scalable.

**4. Tidak ada public key endpoint.**
  - Untuk offline signature verification, client perlu public key dari server.
  - **Missing:** `GET /api/v1/public-key`

**5. Tidak ada sync endpoint.**
  - **Missing:** `POST /api/v1/sync` — bulk sync status, kirim heartbeat, terima revocation list.

### 3.3 Recommended Endpoint Additions

| Endpoint | Method | Purpose | Priority |
|----------|--------|---------|----------|
| `/api/v1/auth` | POST | Client app authentication (API key → session token) | 🔴 Critical |
| `/api/v1/public-key` | GET | Get RSA public key for offline token verification | 🔴 Critical |
| `/api/v1/sync` | POST | Periodic sync — heartbeat + revocation check + token refresh | 🟡 High |
| `/api/v1/devices/{id}/reset` | POST | Request device reset approval | 🟡 High |
| `/api/v1/token/refresh` | POST | Refresh offline token before expiry | 🟡 High |
| `/api/v1/revocations` | GET | Get list of revoked licenses (hash list) | 🟡 High |
| `/api/v1/events` | GET | Changelog for client (version updates, announcements) | 🟢 Medium |

### 3.4 API Consistency Improvements

```php
// Standardized response envelope needed:
interface LicenseResponse
{
    public function toArray(): array;
}

class ActivationResponse implements LicenseResponse
{
    public function __construct(
        public readonly bool $success,
        public readonly string $message,
        public readonly ?OfflineToken $token,
        public readonly ?array $metadata,
    ) {}
}

// Every API response should include:
{
    "success": true,
    "message": "...",
    "data": {
        // ...
    },
    "meta": {
        "server_time": "2026-05-16T10:00:00Z",
        "server_version": "1.0.0",
        "request_id": "uuid"
    }
}
```

---

## 4. Offline-First Licensing Compatibility

### 4.1 Current State

Saat ini, sistem hanya mendukung **online validation**. "Offline mode" sebenarnya adalah grace period 7 hari setelah aktivasi online — bukan offline-first yang sesungguhnya.

**Flow target vs current:**

| Target Flow | Current Implementation | Gap |
|-------------|----------------------|-----|
| Encrypted offline token | ❌ Tidak ada | Return hanya `offline_until` timestamp, tanpa signing |
| Signed cache validation | ❌ Tidak ada | Client tidak bisa memverifikasi keaslian response sendiri |
| Periodic sync | ❌ Tidak ada | Tidak ada endpoint sync berkala |
| Offline grace period | ⚠️ Parsial | Grace period 7 hari, tapi tanpa signing rentan spoof |
| Token refresh | ❌ Tidak ada | Tidak ada mekanisme refresh token |
| Anti-replay validation | ❌ Tidak ada | Token bisa di-replay selama masa berlaku |
| Anti-clock-tampering | ❌ Tidak ada | Client bisa memundurkan jam |
| Revocation handling | ❌ Tidak ada | Device tidak tahu jika di-revoke saat offline |

### 4.2 Required: Signed Offline Token Architecture

```php
// OfflineToken structure (signed with RSA-256)
class OfflineToken
{
    public function __construct(
        public readonly string $license_key,
        public readonly string $device_fingerprint,
        public readonly string $product_slug,
        public readonly int $activated_at,          // Unix timestamp
        public readonly int $offline_until,          // Unix timestamp
        public readonly int $max_offline_seconds,    // e.g., 604800 (7 days)
        public readonly string $signature,           // RSA-SHA256 signature
    ) {}

    public function isValid(string $publicKey): bool
    {
        $payload = $this->license_key
            . $this->device_fingerprint
            . $this->product_slug
            . $this->activated_at
            . $this->offline_until
            . $this->max_offline_seconds;

        return openssl_verify(
            $payload,
            base64_decode($this->signature),
            $publicKey,
            OPENSSL_ALGO_SHA256
        );
    }
}
```

**Flow with signed tokens:**
```
1. Device activation → Server generates OfflineToken → RSA signed → Return to client
2. Client stores encrypted token locally (AES-256 dengan key derived dari fingerprint)
3. Offline validation:
   a. Load token
   b. Verify RSA signature (client has embedded public key)
   c. Check offline_until > now()
   d. Check clock hasn't been tampered (compare with last known server time)
4. Grace period running out → Re-sync with server via POST /api/v1/sync
5. Server returns new token with updated offline_until
```

### 4.3 Anti-Clock-Tampering Strategy

```php
class ClockTamperingDetector
{
    // Client-side pseudo-code:
    public function detect(array $tokens, int $currentTime): bool
    {
        // 1. Store last known server time from previous sync
        $lastServerTime = $this->getLastServerTime();

        // 2. If current time < last known server time → tampering!
        if ($currentTime < $lastServerTime) {
            return true;
        }

        // 3. If more than X days passed without sync → lock
        $lastSyncAt = $this->getLastSyncAt();
        if ($lastSyncAt && ($currentTime - $lastSyncAt) > $this->maxOfflineSeconds) {
            return true;
        }

        // 4. Store multiple checkpoints with progressing timestamps
        //    If timestamps go backward → tampering
        return false;
    }
}
```

---

## 5. Device Binding Architecture

### 5.1 Current Fingerprint Strategy

- `devices.fingerprint` = string(64) — expected SHA-256 hash
- Client sends fingerprint via API
- Server checks uniqueness + license_id scope

### 5.2 Issues

| Issue | Severity | Explanation |
|-------|----------|-------------|
| **No fingerprint validation** | 🔴 Critical | Server tidak memvalidasi fingerprint — client bisa kirim string apapun |
| **No multi-factor binding** | 🟡 High | Hanya 1 identifier (fingerprint). Seharusnya: fingerprint + hostname + MAC |
| **No anti-spoofing** | 🔴 Critical | Tanpa signing, fingerprint mudah dipalsukan |
| **Device reset too easy** | 🟡 Medium | Tidak ada cooldown atau limit untuk device re-registration |
| **Virtualization edge cases** | 🟡 Medium | Docker/VM bisa memiliki fingerprint identik |

### 5.3 Recommended Fingerprint Strategy

```php
// Server-side fingerprint composition
class DeviceFingerprint
{
    // Client should collect and hash:
    const COMPONENTS = [
        'cpu_serial',       // CPU serial number (if available)
        'mac_address',      // Primary MAC address
        'disk_serial',      // Root disk serial
        'machine_id',       // /etc/machine-id or equivalent
        'hostname',         // system hostname
        'os_type',          // OS/distribution
    ];

    // Server should store each component separately
    // for partial matching during device migration
}
```

**Device migration flow:**
```
User request device reset → Admin approve → 
Server issues new token with new fingerprint → 
Old token invalidated → 
New device gets 7-day grace
```

---

## 6. Security Review

### 6.1 Current Security Posture

| Area | Status | Risk Level |
|------|--------|:----------:|
| API authentication | ❌ Tidak ada | 🔴 Critical |
| License key encryption | ❌ Tidak ada | 🔴 Critical |
| Offline token signing | ❌ Tidak ada | 🔴 Critical |
| Fingerprint validation | ❌ Tidak ada | 🔴 Critical |
| Replay attack protection | ❌ Tidak ada | 🟡 High |
| Brute-force protection | ⚠️ Partial | 🟡 High |
| Rate limiting | ⚠️ Basic | 🟡 High |
| Input validation | ✅ Ada | 🟢 Low |
| SQL injection | ✅ Safe (Eloquent) | 🟢 Low |
| XSS | ✅ Safe (Blade) | 🟢 Low |
| CSRF | ✅ Ada (Fortify) | 🟢 Low |

### 6.2 Critical Security Risks

**R1: No API Authentication** 🔴
- **Problem:** API endpoints tidak memiliki authentication layer. Siapa pun yang tahu license key bisa activate/validate.
- **Risk:** License key enumeration via brute force, fake activations.
- **Fix:** Implementasikan API key + HMAC signing.
  ```php
  // Client sends HMAC-SHA256 signature of the request body
  // Server validates using pre-shared API key
  // Each client app gets unique API key saat registrasi
  ```

**R2: License Key Brute Force** 🔴
- **Problem:** Format key `XXXX-XXXX-XXXX-XXXX` dengan 36^16 kemungkinan. Tanpa rate limiting ketat per IP, brute force feasible.
- **Risk:** Attacker bisa menebak key valid.
- **Fix:**
  - Rate limit: 3 gagal per IP per 15 menit untuk aktivasi
  - Rate limit: 10 gagal per IP per jam untuk validasi
  - Account lockout setelah N percobaan gagal

**R3: Fingerprint Spoofing** 🔴
- **Problem:** Server tidak memvalidasi keaslian fingerprint.
- **Risk:** Attacker bisa register device palsu dengan fingerprint sembarangan.
- **Fix:** Client harus sign fingerprint dengan private key, server verifikasi dengan public key.

**R4: Offline Token Forgery** 🔴
- **Problem:** `offline_until` adalah plain timestamp tanpa signing.
- **Risk:** Client bisa mengubah `offline_until` di response untuk memperpanjang masa berlaku.
- **Fix:** RSA-signed offline token (lihat section 4.2).

**R5: No Expired License Cleanup** 🟡
- **Problem:** Expired licenses tetap di database, tidak ada auto-cleanup.
- **Risk:** Database bloat, performance degradation.
- **Fix:** Implement soft-delete atau archive setelah X bulan.

### 6.3 Recommended Security Hardening

```php
// 1. API Client Authentication Middleware
class VerifyApiClientMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $apiKey = $request->header('X-API-Key');
        $signature = $request->header('X-Request-Signature');
        $timestamp = $request->header('X-Request-Timestamp');

        // Replay protection: timestamp must be within 5 minutes
        if (abs(now()->timestamp - (int) $timestamp) > 300) {
            return response()->json(['error' => 'Request expired'], 401);
        }

        $client = ApiClient::where('api_key', $apiKey)->first();
        if (! $client) {
            return response()->json(['error' => 'Invalid API key'], 401);
        }

        // HMAC validation
        $payload = $request->getContent() . $timestamp;
        $expectedSig = hash_hmac('sha256', $payload, $client->secret);
        if (! hash_equals($expectedSig, $signature)) {
            return response()->json(['error' => 'Invalid signature'], 401);
        }

        return $next($request);
    }
}

// 2. Brute Force Protection per License Key
class LicenseKeyRateLimiter
{
    public function check(string $licenseKey): void
    {
        $key = 'license_attempts:' . $licenseKey;
        $attempts = Cache::increment($key);

        if ($attempts === 1) {
            Cache::expire($key, 900); // 15 menit
        }

        if ($attempts > 5) {
            throw new \App\Exceptions\LicenseLockedException(
                'Too many attempts. License locked for 15 minutes.'
            );
        }
    }
}
```

---

## 7. Operational Sustainability

### 7.1 Maintenance Burden

| Area | Burden | Notes |
|------|--------|-------|
| Database migrations | ✅ Low | Hanya 19 migration, well-structured |
| Artisan commands | ✅ Low | 3 commands, simple logic |
| Queue jobs | ✅ None | Tidak ada queue jobs — bisa jadi masalah di scale |
| Frontend maintenance | ⚠️ Medium | Volt + Folio + Flux — perlu familiaritas dengan Livewire ecosystem |
| Dependency updates | ⚠️ Medium | Flux UI dan Volt masih aktif berkembang, breaking changes mungkin terjadi |

### 7.2 Monitoring Complexity

- **Logging:** Menggunakan Laravel default log — tidak ada structured logging untuk licensing events.
- **Metrics:** Tidak ada metrics endpoint untuk monitoring health.
- **Alerting:** Tidak ada notifikasi untuk expired massal atau aktivasi mencurigakan.
- **Dashboard:** Admin dashboard sudah ada untuk overview, tapi tidak ada real-time metrics.

### 7.3 Scaling Limitations

| Limitation | Impact | Mitigation |
|------------|--------|------------|
| Queue driver = database | Bottleneck untuk 1000+ concurrent activation | Migrate ke Redis |
| Cache driver = database | Inefficient untuk cache-heavy operations | Migrate ke Redis |
| SQLite in production | Not suitable for production | Wajib MySQL/PostgreSQL |
| No read replicas | Single point of failure | Tambah read replica untuk validation |
| API rate limit 60/min | Too restrictive for many clients | Perlu per-client rate limiting |

---

## 8. Architecture Gap Analysis

### 8.1 Missing Components

| Component | Priority | Reason |
|-----------|:--------:|--------|
| **Client SDK package** | 🔴 Critical | Tidak ada PHP package untuk client Laravel/CodeIgniter. Semua licensing logic harus di-copy manual |
| **Offline token service** | 🔴 Critical | RSA signing/verification untuk offline tokens |
| **API client authentication** | 🔴 Critical | HMAC-based request signing |
| **Clock tampering detection** | 🟡 High | Client-side + server-side validation |
| **Revocation list cache** | 🟡 High | Server perlu menyediakan revocation list untuk client sync |
| **Webhook system** | 🟡 High | Callback untuk activation, expiration, revocation |
| **Event system** | 🟡 Medium | Licensing events + listeners untuk extensibility |
| **API documentation** | 🟡 Medium | OpenAPI/Swagger spec untuk third-party integration |
| **Health check endpoint** | 🟢 Low | `/api/v1/health` untuk monitoring |
| **Metrics endpoint** | 🟢 Low | `/api/v1/metrics` Prometheus-compatible |

### 8.2 Weak Architecture Areas

| Area | Problem | Solution Complexity |
|------|---------|:------------------:|
| LicenseService terlalu besar | SRP violation | Medium — refactor ke 3-4 service |
| Tidak ada Contracts/Interfaces | Tight coupling | Low — tambah interface, bind ke container |
| API response tidak konsisten | SDK development lebih sulit | Low — standardize dengan DTO + Resource |
| Missing database indexes | Query performance di scale | Low — tambah migration untuk index |
| API rate limiting too broad | Per-client limits tidak mungkin | Medium — implement rate limiter per API key |
| No queue jobs | Sync operations blocking | Medium — implement queue untuk activation |

### 8.3 Overengineered Areas

| Area | Explanation | Recommendation |
|------|-------------|----------------|
| **Spatie Permission** | Untuk licensing server, 4 roles sudah cukup. Spatie Permission dengan 15+ permissions mungkin overkill | Tetap gunakan — berguna untuk multi-tenant di masa depan |
| **Folio routing** | File-based routing cocok untuk pages, tapi licensing core lebih cocok controller-based | Tidak perlu refactor — Folio OK untuk admin panel |
| **Stripe integration field** | `stripe_price_id_*` dan `stripe_subscription_id` sudah di schema tapi Stripe belum terintegrasi | OK sebagai preparation — tidak overengineered |

### 8.4 Underdeveloped Areas

| Area | Impact | Current | Target |
|------|--------|---------|--------|
| **Offline token system** | 🔴 Blocking production | Tidak ada | RSA-signed JWT-like token |
| **Client SDK** | 🔴 Blocking production | Tidak ada | Publisheable PHP package |
| **License validation in code** | 🔴 Blocking production | Tidak ada | Middleware trait untuk client |
| **API security** | 🔴 Blocking production | No auth | HMAC signing |
| **Check update flow** | 🟡 Important | Placeholder | Full version tracking |
| **Device fingerprint** | 🟡 Important | Simple hash | Multi-factor binding |
| **Testing licensing flow** | 🟡 Important | Unit tests exist | Integration test for full activation flow |

---

## 9. Recommended Refactoring

### 9.1 Immediate (Before Production)

1. **Add database indexes** — 9 indexes (lihat section 2.3)
2. **Extract LicenseService** — Pisahkan menjadi 3-4 service terfokus
3. **Implement API client authentication** — HMAC signing middleware
4. **Implement signed offline tokens** — RSA key pair + OfflineToken DTO
5. **Implement license key rate limiting** — Anti brute-force
6. **Add licensing Events** — Event + Listener untuk activation lifecycle
7. **API standardization** — DTO + consistent response envelope
8. **Add missing database fields:**
   - `devices.fingerprint_components` (json) — menyimpan fingerprint components untuk migration
   - `devices.fingerprint_algorithm` (string) — versioning fingerprint algorithm
   - `licenses.signature` (text) — server signature untuk license key
   - `api_clients` table — client app registration

### 9.2 Short-term (1-2 months)

1. **Client SDK package** — Installable via Composer untuk Laravel
2. **Offline validation middleware trait** — Reusable trait untuk client middleware
3. **Sync endpoint** — `POST /api/v1/sync` untuk periodic revalidation
4. **Auto-approval rules** — Allow auto-approve berdasarkan IP range atau geolocation
5. **Webhook system** — Callback URL untuk integrasi third-party

### 9.3 Medium-term (3-6 months)

1. **Revocation list distribution** — Signed, timestamped revocation list
2. **Multi-tenant support** — Isolasi per tenant/organization
3. **Admin API** — REST API untuk management (bukan hanya client licensing)
4. **Metrics & monitoring** — Prometheus endpoint + structured logging
5. **Usage analytics** — Track feature usage, active devices trends

---

## 10. Recommended New Tables

### 10.1 `api_clients`

```php
Schema::create('api_clients', function (Blueprint $table) {
    $table->id();
    $table->string('name');                          // Client app name
    $table->string('api_key', 64)->unique();          // Public identifier
    $table->string('api_secret', 128);                // Hashed secret for HMAC
    $table->ipAddress('allowed_ips')->nullable();      // IP whitelist (optional)
    $table->boolean('is_active')->default(true);
    $table->timestamp('last_used_at')->nullable();
    $table->timestamps();
});
```

### 10.2 `license_tokens`

```php
Schema::create('license_tokens', function (Blueprint $table) {
    $table->id();
    $table->foreignId('license_id')->constrained()->cascadeOnDelete();
    $table->foreignId('device_id')->constrained()->cascadeOnDelete();
    $table->string('token_hash', 64)->unique();        // SHA-256 of signed token
    $table->text('signed_token');                       // Full RSA-signed token payload
    $table->timestamp('issued_at');
    $table->timestamp('expires_at');
    $table->timestamp('revoked_at')->nullable();
    $table->timestamps();

    $table->index(['license_id', 'device_id']);
    $table->index('expires_at');
});
```

### 10.3 `revocations`

```php
Schema::create('revocations', function (Blueprint $table) {
    $table->id();
    $table->string('license_key_hash', 64);            // SHA-256 of license key
    $table->timestamp('revoked_at');
    $table->string('reason')->nullable();
    $table->timestamps();

    $table->index('license_key_hash');
});
```

### 10.4 `license_events`

```php
Schema::create('license_events', function (Blueprint $table) {
    $table->id();
    $table->foreignId('license_id')->constrained()->cascadeOnDelete();
    $table->foreignId('device_id')->nullable()->constrained()->nullOnDelete();
    $table->string('event');                            // activated, validated, synced, expired, revoked
    $table->ipAddress('ip_address')->nullable();
    $table->json('metadata')->nullable();
    $table->timestamp('created_at');

    $table->index(['license_id', 'created_at']);
    $table->index('event');
});
```

---

## 11. Recommended Final Architecture

```
┌─────────────────────────────────────────────┐
│              LICENSE SERVER                   │
│  ┌─────────────────────────────────────────┐ │
│  │          ADMIN PANEL (Folio + Volt)      │ │
│  │  Dashboard │ Products │ Licenses │ ...   │ │
│  └─────────────────────────────────────────┘ │
│  ┌─────────────────────────────────────────┐ │
│  │        REST API v1 (api/v1/...)          │ │
│  │  ┌───────┐ ┌────────┐ ┌─────────────┐  │ │
│  │  │ Auth  │ │Public  │ │ Client API   │  │ │
│  │  │Endpoint│ │Key EP  │ │ (HMAC Auth)  │  │ │
│  │  └───────┘ └────────┘ └─────────────┘  │ │
│  │  activate │ validate │ sync │ verify   │ │
│  └─────────────────────────────────────────┘ │
│  ┌─────────────────────────────────────────┐ │
│  │          SERVICE LAYER                    │ │
│  │  ┌────────┐ ┌────────┐ ┌────────────┐  │ │
│  │  │License │ │Device  │ │Activation  │  │ │
│  │  │Validate│ │Register│ │Service     │  │ │
│  │  └────────┘ └────────┘ └────────────┘  │ │
│  │  ┌────────┐ ┌────────┐ ┌────────────┐  │ │
│  │  │Token   │ │Offline │ │Webhook     │  │ │
│  │  │Sign    │ │Sync    │ │Service     │  │ │
│  │  └────────┘ └────────┘ └────────────┘  │ │
│  └─────────────────────────────────────────┘ │
│  ┌─────────────────────────────────────────┐ │
│  │          EVENTS / JOBS                    │ │
│  │  LicenseActivated → Webhook → Email      │ │
│  │  LicenseExpired → Suspend → Notification │ │
│  │  DeviceRegistered → Sync Propagation     │ │
│  └─────────────────────────────────────────┘ │
│  ┌─────────────────────────────────────────┐ │
│  │          DATABASE                         │ │
│  │  MySQL/PostgreSQL + Redis Cache/Queue    │ │
│  └─────────────────────────────────────────┘ │
└─────────────────────────────────────────────┘

                    │
                    ▼
┌─────────────────────────────────────────────┐
│         CLIENT APP (Laravel/CI)              │
│  ┌─────────────────────────────────────────┐ │
│  │   LICENSING MIDDLEWARE (Trait)           │ │
│  │  detect unlicensed → redirect wizard    │ │
│  │  validate offline token → allow/block   │ │
│  │  periodic sync → refresh token          │ │
│  │  detect clock tampering → re-sync       │ │
│  └─────────────────────────────────────────┘ │
│  ┌─────────────────────────────────────────┐ │
│  │   OFFLINE LICENSE MANAGER                │ │
│  │  Encrypted token storage                │ │
│  │  Token signature verification           │ │
│  │  Grace period countdown                 │ │
│  │  Readonly mode / partial lock           │ │
│  └─────────────────────────────────────────┘ │
│  ┌─────────────────────────────────────────┐ │
│  │   BOOTSTRAPPING WIZARD                   │ │
│  │  Step 1: Collect fingerprint            │ │
│  │  Step 2: Enter license key              │ │
│  │  Step 3: Activate / Request approval    │ │
│  │  Step 4: Store token → App ready        │ │
│  └─────────────────────────────────────────┘ │
└─────────────────────────────────────────────┘
```

---

## 12. Client SDK Architecture

### 12.1 Package Structure

```
laravel-license-client/
├── src/
│   ├── LicenseServiceProvider.php
│   ├── Http/
│   │   ├── Middleware/
│   │   │   └── CheckLicenseMiddleware.php
│   │   └── Controllers/
│   │       └── LicenseWizardController.php
│   ├── Services/
│   │   ├── LicenseClient.php           # HTTP client to license server
│   │   ├── OfflineValidator.php        # Local token validation
│   │   └── FingerprintCollector.php    # Hardware fingerprinting
│   ├── Models/
│   │   └── LocalLicense.php            # Eloquent model for local cache
│   ├── Traits/
│   │   └── HasLicense.php              # Trait for app controllers
│   ├── Console/
│   │   └── LicenseSyncCommand.php      # Artisan command for sync
│   └── config/
│       └── license-client.php
├── resources/
│   └── views/
│       └── wizard.blade.php
├── migrations/
│   └── create_local_licenses_table.php
└── composer.json
```

### 12.2 Key SDK Features

```php
class LicenseClient
{
    // Automatic fingerprint collection
    // Automatic HMAC request signing
    // Automatic token refresh on expiry
    // Built-in clock tampering detection
    // Graceful degradation (readonly → lock)
    // Artisan command for maintenance
    // Configurable grace periods per mode
}
```

---

## 13. Development Priorities

### Phase 1: Foundation (Immediate — 2 weeks)

| Priority | Task | Effort | Dependencies |
|:--------:|------|:------:|:-----------:|
| P0 | Add database indexes | 1 day | None |
| P0 | Implement RSA key pair + OfflineToken service | 3 days | None |
| P0 | Implement API client auth (HMAC) | 2 days | None |
| P0 | Add licensing Events system | 2 days | None |
| P0 | Rate limiting per license key | 1 day | None |
| P0 | Refactor LicenseService | 2 days | None |

### Phase 2: Core (2-4 weeks)

| Priority | Task | Effort |
|:--------:|------|:------:|
| P1 | Client SDK package (MVP) | 5 days |
| P1 | POST /api/v1/sync endpoint | 2 days |
| P1 | Auto-approval rules | 1 day |
| P1 | Token refresh endpoint | 1 day |
| P1 | License events table | 1 day |

### Phase 3: Hardening (4-8 weeks)

| Priority | Task | Effort |
|:--------:|------|:------:|
| P2 | Revocation list distribution | 3 days |
| P2 | Webhook system | 3 days |
| P2 | Client SDK full features | 5 days |
| P2 | API documentation (OpenAPI) | 3 days |
| P2 | Integration tests for full flow | 3 days |

---

## 14. Summary Table

| No | Issue | Severity | Impact | Effort to Fix |
|----|-------|:--------:|--------|:-------------:|
| 1 | No signed offline tokens | 🔴 Critical | Security: offline bypass | 3 days |
| 2 | No API authentication | 🔴 Critical | Security: unauthorized access | 2 days |
| 3 | No fingerprint validation | 🔴 Critical | Security: fake devices | 2 days |
| 4 | LicenseService SRP violation | 🟡 High | Maintainability | 2 days |
| 5 | Missing database indexes | 🟡 High | Performance | 1 day |
| 6 | No client SDK | 🔴 Critical | Cannot use as license server | 5 days |
| 7 | No license key signing | 🟡 High | Security: key forgery | 1 day |
| 8 | No revocation broadcast | 🟡 High | Offline: cannot revoke | 2 days |
| 9 | No clock tampering detection | 🟡 High | Offline: time manipulation | 2 days |
| 10 | CheckUpdate placeholder | 🟢 Low | Feature incomplete | 1 day |
| 11 | No queue jobs | 🟢 Low | Scalability | 2 days |
| 12 | No events/listeners | 🟢 Low | Extensibility | 2 days |

---

## 15. Final Verdict

**License Monitor memiliki fondasi yang baik untuk admin panel licensing, tapi belum siap sebagai Central License Server untuk offline-first client.**

Sistem saat ini sangat cocok untuk:
✅ Internal license management via admin panel
✅ Online-only license validation
✅ Small-scale deployment (< 100 clients)
✅ Product and subscription management
✅ Audit trail and compliance

Sistem **tidak siap** untuk:
❌ Offline-first licensing production
❌ Client SDK integration
❌ Anti-tampering security
❌ Large-scale deployment (> 1000 clients)
❌ Public API exposure without authentication

**Estimated effort to reach production readiness:** 4-6 weeks
**Estimated effort to reach feature-complete:** 8-12 weeks (including Client SDK)
