# REDMINE Project Plan — License Monitor

> Central License Server & Client Licensing SDK
> Versi: 1.0 — 16 Mei 2026
> Author: Senior Technical PM
> Status: Approved for Development

---

## 1. PROJECT OVERVIEW

### 1.1 Tujuan Project

Membangun **Central License Server** berbasis Laravel yang bisa:
- Menerbitkan dan mengelola lisensi perangkat lunak
- Melakukan aktivasi perangkat (online/offline/semi-online)
- Menyediakan Client SDK untuk integrasi aplikasi Laravel/CodeIgniter
- Memberikan bootstrap activation wizard
- Mengelola grace period offline
- Menyediakan admin panel untuk operasional licensing

### 1.2 Scope

| In Scope | Out of Scope |
|----------|-------------|
| REST API licensing (activate, validate, verify, sync) | Microservices architecture |
| Client SDK Composer package | Kubernetes deployment |
| Bootstrap activation wizard | Multi-tenant data isolation |
| Offline grace period with signed tokens | Event sourcing / CQRS |
| Device fingerprinting (multi-factor) | Real-time WebSocket sync |
| Admin panel (Folio + Volt + Flux) | White-label licensing |
| HMAC API authentication | Stripe payment integration |
| Activation approval flow | Marketplace / app store |
| Revocation list distribution | Usage-based billing |
| Audit trail | AI-powered fraud detection |
| Role-based access control (Spatie) | Customer portal / self-service |

### 1.3 Target Architecture

```
┌─────────────────────────────────────┐
│         LICENSE SERVER              │
│  Laravel 13 + Livewire 4 + Flux    │
│                                     │
│  ┌─────────┐  ┌─────────────────┐  │
│  │ REST API│  │   Admin Panel   │  │
│  │  v1     │  │  (Folio + Volt) │  │
│  └────┬────┘  └─────────────────┘  │
│       │                             │
│  ┌────▼─────────────────────────┐  │
│  │      Service Layer            │  │
│  │  LicenseValidation            │  │
│  │  DeviceRegistration           │  │
│  │  ActivationService            │  │
│  │  TokenSigner                  │  │
│  └────┬─────────────────────────┘  │
│       │                             │
│  ┌────▼─────────────────────────┐  │
│  │      Database (MySQL/PgSQL)   │  │
│  │  + Redis (cache/queue)        │  │
│  └───────────────────────────────┘  │
└──────────┬──────────────────────────┘
           │ HTTPS + HMAC
┌──────────▼──────────────────────────┐
│         CLIENT APP                   │
│  Laravel / CodeIgniter              │
│                                     │
│  ┌─────────────────────────────┐    │
│  │  License Client SDK          │    │
│  │  ┌──────────┐ ┌──────────┐  │    │
│  │  │Middleware│ │ Wizard   │  │    │
│  │  └──────────┘ └──────────┘  │    │
│  │  ┌──────────┐ ┌──────────┐  │    │
│  │  │Offline   │ │Fingerprint│  │    │
│  │  │Validator │ │Collector │  │    │
│  │  └──────────┘ └──────────┘  │    │
│  └─────────────────────────────┘    │
└─────────────────────────────────────┘
```

### 1.4 MVP Definition

**MVP = Sistem yang bisa dipakai end-to-end oleh 1 client:**

```
Admin buat produk → Admin buat license → 
Client install SDK → Client jalankan app →
Wizard muncul → Client input license key →
Fingerprint terkirim → Device terdaftar →
Admin approve → Token tersimpan →
App berjalan full access →
7 hari kemudian grace habis → 
App readonly → Client re-activate
```

---

## 2. REDMINE PROJECT STRUCTURE

### 2.1 Hierarchy

```
License Monitor (Parent Project)
│
├── Core Server (Subproject)
│   ├── Categories:
│   │   ├── Licensing Engine
│   │   ├── Activation Flow
│   │   ├── Device Management
│   │   ├── API Development
│   │   ├── Admin Panel
│   │   ├── Security
│   │   ├── Database
│   │   └── Testing
│   │
│   ├── Versions:
│   │   ├── v0.1-alpha (MVP Core)
│   │   ├── v0.2-beta (Client SDK)
│   │   ├── v0.3-rc (Security Hardening)
│   │   └── v1.0-stable (Production)
│   │
│   └── Trackers: Bug, Feature, Support, Task
│
├── Client SDK (Subproject)
│   ├── Categories:
│   │   ├── Core SDK
│   │   ├── Middleware
│   │   ├── Wizard UI
│   │   ├── Token Storage
│   │   ├── Fingerprint
│   │   └── Testing
│   │
│   └── Versions:
│       ├── v0.1-dev
│       ├── v0.2-beta
│       └── v1.0-stable
│
├── Infrastructure (Subproject)
│   ├── Categories:
│   │   ├── Deployment
│   │   ├── Monitoring
│   │   └── CI/CD
│   │
│   └── Versions: [sync with Core Server]
│
└── Documentation (Subproject)
    ├── Categories:
    │   ├── API Docs
    │   ├── User Guide
    │   ├── Deployment Guide
    │   └── SDK Integration Guide
    │
    └── Versions: [sync with all]
```

### 2.2 Members & Roles

| Role | Responsibility |
|------|---------------|
| Product Owner | Define priority, accept/reject delivery |
| Developer (Backend) | Server-side API, services, database |
| Developer (Frontend) | Admin panel UI, wizard UI |
| Developer (SDK) | Client package, middleware |
| QA | Testing, bug verification |
| Security Reviewer | Crypto code review, penetration test |

*Dalam konteks solo developer: semua role dipegang 1 orang + AI*

### 2.3 Workflow

```
New → Triage → Design → In Progress → Review → Testing → Done
  │                    │               │         │
  │                    ▼               ▼         ▼
  └──→ Rejected   Request Changes   Failed    Reopen
```

---

## 3. ROADMAP

### 3.1 Phase 1: Working MVP (Weeks 1-4)

**Theme:** "Bikin dulu, benerin belakangan"

**Tujuan:** Sistem licensing benar-benar bisa dipakai dari ujung ke ujung

**Deliverables:**

| Area | Deliverable | Acceptable Quality |
|------|-------------|-------------------|
| Server | REST API activate, validate, verify, status, sync | Bisa diakses via curl |
| Server | Offline basic grace period (7 hari, tanpa signing) | Client bisa jalan offline |
| Admin Panel | License CRUD, Product CRUD, Activation approve/reject | Bisa操作 sehari-hari |
| Client SDK | Composer package basic | Install → wizard muncul |
| Client SDK | Bootstrap wizard | Input key → activate → save token |
| Client SDK | CheckLicenseMiddleware | Redirect ke wizard kalo unlicensed |
| Client SDK | Grace countdown UI | Tampilkan sisa hari |
| Client SDK | Readonly mode | Grace habis → fitur terbatas |
| Database | All MVP tables + migrations | Schema stabil |
| Testing | Full activation flow test | End-to-end verified |

**Technical Goals:**
- Service layer refactored (LicenseValidationService, DeviceRegistrationService, ActivationService)
- API response standardized (DTO + consistent envelope)
- 9 database indexes added
- Client SDK installable via `composer require`

**UX Goals:**
- Admin bisa manage licensing dalam < 5 menit
- Client bisa aktivasi dalam < 2 menit
- Wizard tidak butuh dokumentasi — self-explanatory

**Risiko:**
| Risk | Mitigasi |
|------|----------|
| Client SDK terlalu kompleks | Minimal viable — hanya middleware + wizard + storage |
| Wizard UI jelek | Pake Blade + Tailwind, Flux UI untuk component |
| Grace period tanpa signing rentan | Diterima — signing di Phase 2 |

---

### 3.2 Phase 2: Hardening (Weeks 5-7)

**Theme:** "Kunci pintu yang terbuka"

**Tujuan:** Sistem aman dari eksploitasi umum dan siap untuk beta tester

**Deliverables:**

| Area | Deliverable | Acceptable Quality |
|------|-------------|-------------------|
| Security | HMAC API authentication | Semua request wajib signature |
| Security | RSA signed offline token | Client verify signature offline |
| Security | Brute-force protection per key + IP | 5 gagal → lock 15 menit |
| Security | Replay attack protection | Timestamp window 5 menit |
| Security | Clock tampering detection | Client detect jam dimundurkan |
| Security | Device fingerprint multi-factor | CPU + MAC + hostname + disk |
| SDK | Offline token RSA verification | Token invalid → lock |
| SDK | Encrypted token storage | AES-256 encrypt local cache |
| SDK | Clock tampering detection | Detect + warn + lock |
| API | POST /api/v1/sync | Periodic heartbeat + token refresh |
| API | GET /api/v1/public-key | Client ambil public key |
| API | POST /api/v1/token/refresh | Perpanjang token sebelum expired |

**Technical Goals:**
- 100% API request ter-autentikasi
- Offline token tidak bisa dipalsukan
- Brute force tidak feasible
- Client bisa detect kecurangan jam

**UX Goals:**
- Security transparan — user tidak perlu tahu detail
- Lock screen jelas reason-nya
- Re-activasi mudah (1 klik)

**Risiko:**
| Risk | Mitigasi |
|------|----------|
| RSA signing terlalu lambat | Key size 2048 cukup, cache public key |
| Encrypted storage complexity | AES-256-GCM dengan key derived dari fingerprint |
| Clock detection false positive | Tolerance 5 menit + admin override |

---

### 3.3 Phase 3: Ecosystem (Weeks 8-10)

**Theme:** "Biar gampang dipake banyak orang"

**Tujuan:** Sistem siap untuk multiple client + production monitoring

**Deliverables:**

| Area | Deliverable | Acceptable Quality |
|------|-------------|-------------------|
| Server | Webhook system (activation, expiry, revocation) | POST ke URL callback |
| Server | Revocation list distribution | Client sync revocation dalam 24 jam |
| Server | Admin API (REST untuk management) | CRUD via API key |
| Server | Health check endpoint | `/api/v1/health` |
| Monitoring | Structured logging | JSON log + level |
| Monitoring | Metrics endpoint | Prometheus-compatible |
| Infrastructure | Docker Compose production setup | `docker compose up` |
| Infrastructure | CI/CD pipeline | GitHub Actions |
| Testing | Load test (1000 concurrent) | < 500ms response |
| Documentation | OpenAPI/Swagger spec | Interactive docs |
| Documentation | SDK Integration Guide | Step-by-step |
| Documentation | Deployment Guide | From zero to production |

**Technical Goals:**
- Siap untuk 10+ client apps
- Siap untuk 1000+ devices
- Siap untuk production deployment

**UX Goals:**
- Integrasi SDK dalam 10 menit
- Deployment dalam 30 menit

**Risiko:**
| Risk | Mitigasi |
|------|----------|
| Webhook delivery failure | Retry 3x + queue |
| Load test tidak pass | Query optimization + eager loading |
| Docs tidak update | Auto-generate dari OpenAPI spec |

---

## 4. VERSIONS & MILESTONES

```
v0.1-alpha ─── Week 4 ─── MVP Core
   │
   ├── Milestone M1: Server Core Done
   │   ├── All services refactored
   │   ├── All migrations applied
   │   ├── All API endpoints working
   │   └── Admin panel CRUD functional
   │
   ├── Milestone M2: Client SDK Alpha
   │   ├── Composer package installable
   │   ├── Wizard functional
   │   ├── Middleware redirects unlicensed
   │   └── Offline grace period working
   │
   └── Milestone M3: MVP Demo
       ├── End-to-end activation flow working
       ├── All MVP tests passing
       └── Demoable to stakeholder

v0.2-beta ──── Week 7 ─── Security Hardened
   │
   ├── Milestone M4: Security Gate
   │   ├── HMAC auth deployed
   │   ├── RSA signed tokens
   │   ├── Brute-force protection
   │   └── Clock tampering detection
   │
   └── Milestone M5: Beta Release
       ├── All security features passing
       ├── Beta tester onboarding
       ├── Bug bash completed
       └── Known issues documented

v0.3-rc ────── Week 9 ─── Feature Complete
   │
   ├── Milestone M6: Ecosystem
   │   ├── Webhook system
   │   ├── Revocation distribution
   │   ├── Admin API
   │   └── Monitoring
   │
   └── Milestone M7: Release Candidate
       ├── Load test passed
       ├── All docs written
       ├── Deployment guide tested
       └── RC sign-off

v1.0-stable ── Week 10 ── Production
   │
   └── Milestone M8: Production Launch
       ├── All tests green
       ├── Security audit passed
       ├── Production deployment
       └── Monitoring active
```

---

## 5. EPIC BREAKDOWN

### E-01: Licensing Core Engine

| Field | Value |
|-------|-------|
| **Description** | Service layer untuk license lifecycle: create, validate, suspend, revoke, expire, restore |
| **Dependencies** | None (foundation) |
| **Complexity** | Medium (3 services, events, DTO) |
| **Business Impact** | CRITICAL — tanpa ini, tidak ada lisensi |
| **Phase** | Phase 1 (Week 1) |

**Sub-features:**
- License CRUD (admin panel)
- License key generation with checksum
- License status management (active, suspended, expired, revoked)
- License validation (status + expiry)
- Subscription plan validation
- License events (activated, expired, revoked)
- License key format validation

**Files affected:**
- `app/Services/LicenseValidationService.php` (new)
- `app/Services/LicenseKeyService.php` (refactor)
- `app/Models/License.php`
- `app/Enums/LicenseStatus.php`

---

### E-02: Activation Flow

| Field | Value |
|-------|-------|
| **Description** | End-to-end device activation: request → approve/reject → token → grace period |
| **Dependencies** | E-01 (licensing core) |
| **Complexity** | High (multi-step flow, state machine) |
| **Business Impact** | CRITICAL — core value proposition |
| **Phase** | Phase 1 (Week 1-2) |

**Sub-features:**
- Activation request creation (30 menit expiry)
- Activation request approve/reject (admin panel)
- Auto-activation dalam device limit
- Grace period calculation
- OfflineToken generation (Phase 2: signed)
- Activation cooldown anti-abuse

**API Endpoints:**
- `POST /api/v1/activate` — register device
- `GET /api/v1/verify/{key}/{fp}` — verify activation code
- `GET /api/v1/status/{key}/{fp}` — check status
- `POST /api/v1/validate` — validate license

---

### E-03: Device Management

| Field | Value |
|-------|-------|
| **Description** | Device registration, fingerprinting, limit enforcement |
| **Dependencies** | E-01 |
| **Complexity** | Medium |
| **Business Impact** | HIGH — prevents abuse |
| **Phase** | Phase 1 (Week 2) |

**Sub-features:**
- Device registration with fingerprint
- Device limit per license
- Device list in admin panel
- Device reset request + approval
- Fingerprint multi-factor (Phase 2)
- Device cooldown (max 3 resets per 30 hari)

---

### E-04: REST API v1

| Field | Value |
|-------|-------|
| **Description** | Public API untuk client licensing — activation, validation, sync, status |
| **Dependencies** | E-01, E-02, E-03 |
| **Complexity** | High (security, rate limiting, versioning) |
| **Business Impact** | CRITICAL — client face |
| **Phase** | Phase 1-2 |

**Endpoints:**

| Method | Endpoint | Phase | Auth |
|--------|----------|:-----:|:----:|
| POST | `/api/v1/activate` | 1 | HMAC |
| POST | `/api/v1/validate` | 1 | HMAC |
| GET | `/api/v1/verify/{key}/{fp}` | 1 | HMAC |
| GET | `/api/v1/status/{key}/{fp}` | 1 | HMAC |
| POST | `/api/v1/sync` | 2 | HMAC |
| GET | `/api/v1/public-key` | 2 | None |
| POST | `/api/v1/token/refresh` | 2 | HMAC |
| POST | `/api/v1/auth` | 2 | API Key |
| GET | `/api/v1/health` | 3 | None |
| GET | `/api/v1/metrics` | 3 | Admin |

---

### E-05: Admin Panel

| Field | Value |
|-------|-------|
| **Description** | Folio + Volt + Flux UI admin interface |
| **Dependencies** | None (existing) |
| **Complexity** | Low-Medium |
| **Business Impact** | HIGH — daily operations |
| **Phase** | Phase 1 (existing, minor improvements) |

**Pages:**

| Page | Status | Phase |
|------|:------:|:------:|
| Dashboard | ✅ Existing | - |
| Products CRUD | ✅ Existing | - |
| Plans CRUD | ✅ Existing | - |
| Licenses CRUD | ✅ Existing | - |
| Devices | ✅ Existing | - |
| Activation Requests | ✅ Existing | - |
| Users | ✅ Existing | - |
| Roles | ✅ Existing | - |
| Audit Logs | ✅ Existing | - |
| API Clients management | ❌ New | 2 |
| Webhook management | ❌ New | 3 |
| System Settings | ❌ New | 3 |

---

### E-06: Client SDK

| Field | Value |
|-------|-------|
| **Description** | Composer package untuk client Laravel/CodeIgniter |
| **Dependencies** | E-04 (API) |
| **Complexity** | HIGH (package structure, middleware, wizard, storage) |
| **Business Impact** | CRITICAL — make or break project |
| **Phase** | Phase 1 (MVP) |

**Package name:** `adptra01/laravel-license-client`

**Structure:**
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
│   │   ├── LicenseClient.php
│   │   ├── OfflineValidator.php
│   │   └── FingerprintCollector.php
│   ├── Models/
│   │   └── LocalLicense.php
│   ├── Traits/
│   │   └── HasLicense.php
│   └── Console/
│       └── LicenseSyncCommand.php
├── resources/
│   └── views/
│       ├── wizard.blade.php
│       ├── licensed.blade.php
│       ├── unlicensed.blade.php
│       └── grace-countdown.blade.php
├── migrations/
│   └── create_local_licenses_table.php
├── config/
│   └── license-client.php
└── composer.json
```

**Key Components:**
- `CheckLicenseMiddleware` — Detects unlicensed → redirect to wizard
- `LicenseWizardController` — Step-by-step activation flow
- `LicenseClient` — HTTP client to server (with HMAC signing)
- `OfflineValidator` — Verify signed token, check expiry
- `FingerprintCollector` — Collect CPU, MAC, hostname, OS
- `LocalLicense` — Eloquent model for encrypted local cache
- `LicenseSyncCommand` — `php artisan license:sync`

---

### E-07: Bootstrap Wizard (Client-Side)

| Field | Value |
|-------|-------|
| **Description** | Step-by-step wizard yang muncul saat app belum licensed |
| **Dependencies** | E-06 (SDK) |
| **Complexity** | Medium |
| **Business Impact** | HIGH — first impression |
| **Phase** | Phase 1 |

**Wizard Steps:**
```
Step 1: Detecting Hardware ─────────── spinner
         → Collect CPU, MAC, hostname

Step 2: Enter License Key ──────────── form
         → Input key → Validate format

Step 3: Activating ─────────────────── spinner
         → POST /api/v1/activate

Step 3a: Pending Approval ──────────── info screen
          → "Menunggu approval admin"
          → Poll status setiap 30 detik

Step 4: Activation Approved ───────── success screen
         → Token tersimpan
         → "App Anda siap digunakan"
         → Redirect ke app

[Later: Grace Countdown]
         → "Sisa 5 hari"
         → "Sisa 24 jam" (warning)
         → "Licensi expired" (lock screen)
```

---

### E-08: Offline Token System

| Field | Value |
|-------|-------|
| **Description** | RSA-signed token untuk offline validation |
| **Dependencies** | E-01, E-02 |
| **Complexity** | HIGH (cryptography) |
| **Business Impact** | HIGH — core offline feature |
| **Phase** | Phase 2 (Week 5) |

**Token Structure (Base64 encoded JSON + RSA signature):**
```json
{
  "license_key": "ABCD-EFGH-IJKL-MNOP",
  "device_fingerprint": "a1b2c3d4...",
  "product_slug": "monitor-pro",
  "issued_at": 1747382400,
  "offline_until": 1747987200,
  "grace_seconds": 604800,
  "server_time": 1747382400,
  "signature": "base64_rsa_signature..."
}
```

**Tables:**
- `license_tokens` — semua token yang diterbitkan
- `revocations` — daftar license key yang di-revoke (hash)

**Flow:**
```
Activation → Generate RSA signature → Return to client →
Client verify signature with public key → Store encrypted →
Offline: verify signature + check expiry + check clock →
Online: sync to refresh token
```

---

### E-09: Security

| Field | Value |
|-------|-------|
| **Description** | API security, brute-force protection, encryption |
| **Dependencies** | E-04, E-08 |
| **Complexity** | HIGH |
| **Business Impact** | CRITICAL — trust |
| **Phase** | Phase 2 |

**Components:**
- `VerifyApiClient` middleware — HMAC request signing
- `LicenseRateLimiter` — per-key brute-force protection
- `ClockTamperingDetector` — server + client detection
- `ReplayProtection` — timestamp nonce 5 menit
- `EncryptedTokenStore` — AES-256-GCM local storage

---

### E-10: Monitoring & Infrastructure

| Field | Value |
|-------|-------|
| **Description** | Logging, metrics, deployment, CI/CD |
| **Dependencies** | All previous |
| **Complexity** | Medium |
| **Business Impact** | Medium — operational |
| **Phase** | Phase 3 |

---

## 6. DETAILED TASK BREAKDOWN

### 6.1 Sprint 1: Foundation (Week 1)

**Goal:** Service layer refactored, database indexed, basic API working

**Capacity:** 25 hours

| ID | Task | Type | Prio | Hours | Dependencies | Acceptance Criteria |
|:--:|------|:----:|:----:|:-----:|:------------:|-------------------|
| S1-T1 | Buat LicenseValidationService (extract dari LicenseService) | Backend | 🔴 | 2h | - | validate() method working, test passing |
| S1-T2 | Buat DeviceRegistrationService (extract dari LicenseService) | Backend | 🔴 | 2h | - | registerDevice() working, test passing |
| S1-T3 | Buat ActivationService (extract dari LicenseService) | Backend | 🔴 | 2h | - | createRequest, approve, reject working |
| S1-T4 | Buat License Events (Event classes + Listeners) | Backend | 🟡 | 3h | S1-T1..T3 | LicenseActivated, LicenseRevoked, LicenseExpired events fire correctly |
| S1-T5 | Tambah 9 database indexes (migration) | Database | 🔴 | 2h | - | Migration runs, query EXPLAIN shows index usage |
| S1-T6 | ApiClient model + migration | Database | 🔴 | 1.5h | - | CRUD working, HMAC key generation |
| S1-T7 | ApiClient CRUD di admin panel | Frontend | 🟡 | 2h | S1-T6 | Admin bisa manage API clients dari UI |
| S1-T8 | API response standardization (DTO + base response) | Backend | 🟡 | 3h | - | All endpoints return consistent JSON format |
| S1-T9 | Global API exception handler | Backend | 🟡 | 2h | - | 404, 403, 422 errors have consistent format |
| S1-T10 | Update FormRequest validation rules | Backend | 🟡 | 2h | - | All API requests validated |
| S1-T11 | Unit test: LicenseKeyService | Testing | 🟡 | 1.5h | S1-T1 | generate, validateFormat, mask tested |
| S1-T12 | Unit test: new services | Testing | 🟡 | 2h | S1-T1..T3 | Each service method has test |

**Sprint 1 Total: 25 hours**

**Blockers:** None

**Risks:**
- Service refactor bisa break existing code — need regression test after
- Index migration on SQLite — test on MySQL too

---

### 6.2 Sprint 2: API Core (Week 2)

**Goal:** All MVP API endpoints working + admin activation flow

**Capacity:** 25 hours

| ID | Task | Type | Prio | Hours | Dependencies | Acceptance Criteria |
|:--:|------|:----:|:----:|:-----:|:------------:|-------------------|
| S2-T1 | VerifyApiClientMiddleware (HMAC) | Backend | 🔴 | 3h | S1-T6 | All API requests require HMAC signature |
| S2-T2 | API rate limiting per client | Backend | 🔴 | 2h | S2-T1 | Different clients have different limits |
| S2-T3 | Brute-force rate limiter per license key | Backend | 🔴 | 2h | - | 5 failed activation → lock 15 menit |
| S2-T4 | POST /api/v1/activate — update with new services | Backend | 🔴 | 2h | S1-T1..T3 | Full activation flow working |
| S2-T5 | POST /api/v1/validate — update with new services | Backend | 🔴 | 1h | S1-T1 | Validation returning signed token |
| S2-T6 | GET /api/v1/status — update | Backend | 🟡 | 1h | S1-T1 | Status endpoint working |
| S2-T7 | GET /api/v1/verify — update | Backend | 🟡 | 1h | S1-T3 | Verify activation code |
| S2-T8 | Activation approve/reject from admin panel | Frontend | 🔴 | 2h | S2-T4 | Admin can approve/reject from UI |
| S2-T9 | Activation request list with filters | Frontend | 🟡 | 2h | S2-T8 | Filter by status, search by key |
| S2-T10 | Device list in admin panel | Frontend | 🟡 | 2h | S2-T4 | Show all devices with fingerprint |
| S2-T11 | Feature test: Full activation API flow | Testing | 🔴 | 3h | S2-T4..T6 | Activate → approve → validate → verified |
| S2-T12 | Feature test: License validation + expiry | Testing | 🔴 | 2h | S2-T5 | Expired license returns 403 |
| S2-T13 | Feature test: Activation approve/reject | Testing | 🟡 | 2h | S2-T8 | Approve → token valid, Reject → error |

**Sprint 2 Total: 25 hours**

**Blockers:** None (Sprint 1 must complete)

---

### 6.3 Sprint 3: Client SDK MVP (Week 3)

**Goal:** Client SDK bisa dipasang dan wizard berfungsi

**Capacity:** 25 hours

| ID | Task | Type | Prio | Hours | Dependencies | Acceptance Criteria |
|:--:|------|:----:|:----:|:-----:|:------------:|-------------------|
| S3-T1 | Composer package scaffolding | SDK | 🔴 | 2h | - | `composer require` works |
| S3-T2 | LicenseServiceProvider + config | SDK | 🔴 | 1.5h | S3-T1 | Config publishable |
| S3-T3 | LicenseClient HTTP service (HMAC signing) | SDK | 🔴 | 4h | S2-T1 | Every request signed with HMAC |
| S3-T4 | LocalLicense Eloquent model + migration | SDK | 🔴 | 2h | S3-T1 | Local cache table created |
| S3-T5 | Basic encrypted token storage (AES-256) | SDK | 🔴 | 3h | S3-T4 | Token stored encrypted, decrypted on read |
| S3-T6 | FingerprintCollector service | SDK | 🔴 | 3h | - | Collect CPU + MAC + hostname + OS |
| S3-T7 | CheckLicenseMiddleware | SDK | 🔴 | 3h | S3-T3, S3-T6 | Redirect to wizard if unlicensed |
| S3-T8 | Bootstrap wizard (Step 1-2: fingerprint + input key) | SDK | 🔴 | 3h | S3-T6, S3-T7 | Wizard UI functional |
| S3-T9 | Bootstrap wizard (Step 3-4: activation + success) | SDK | 🔴 | 3h | S3-T3 | Activation flow through wizard |
| S3-T10 | Grace countdown UI component | SDK | 🟡 | 2h | S3-T7 | Show remaining days |
| S3-T11 | Readonly mode + lock mechanism | SDK | 🟡 | 2h | S3-T7 | Grace expired → app restricted |

**Sprint 3 Total: 28.5 hours** *(overload — move T10-T11 to Sprint 4)*

**Adjusted Sprint 3: 23.5 hours** (move T10-T11 = 4h to Sprint 4)

---

### 6.4 Sprint 4: SDK Completion + Grace Handling (Week 4)

**Goal:** Complete SDK features, grace period, full end-to-end

**Capacity:** 25 hours

| ID | Task | Type | Prio | Hours | Dependencies | Acceptance Criteria |
|:--:|------|:----:|:----:|:-----:|:------------:|-------------------|
| S4-T1 | Grace countdown UI (moved from S3) | SDK | 🟡 | 2h | S3-T7 | Show remaining days |
| S4-T2 | Readonly mode + lock mechanism (moved from S3) | SDK | 🟡 | 2h | S3-T7 | Grace expired → app restricted |
| S4-T3 | LicenseSyncCommand (artisan command) | SDK | 🟡 | 2h | S3-T3 | `php artisan license:sync` works |
| S4-T4 | Pending approval polling in wizard | SDK | 🟡 | 2h | S3-T9 | Auto-detect when approved |
| S4-T5 | Activation auto-approve rules (IP range) | Backend | 🟡 | 2h | S2-T8 | Same IP → auto approve |
| S4-T6 | Device cooldown anti-abuse | Backend | 🟡 | 2h | S2-T4 | Max 3 resets per 30 days |
| S4-T7 | Database: license_events table | Database | 🟢 | 1h | - | Events tracked |
| S4-T8 | License events logging in services | Backend | 🟢 | 2h | S4-T7 | All actions logged to license_events |
| S4-T9 | Integration test: Client SDK full flow | Testing | 🔴 | 4h | S3-T1..T9 | Install SDK → wizard → activate → validate → offline |
| S4-T10 | End-to-end test: Grace period → readonly → lock | Testing | 🟡 | 3h | S4-T2 | Grace expires → lock screen |
| S4-T11 | Bug fixes from integration test | Backend | 🔴 | 3h | S4-T9 | All critical bugs fixed |

**Sprint 4 Total: 25 hours**

---

### 6.5 Sprint 5: Security Hardening — Signing & Crypto (Week 5)

**Goal:** RSA signing, offline token security, encryption

**Capacity:** 25 hours

| ID | Task | Type | Prio | Hours | Dependencies | Acceptance Criteria |
|:--:|------|:----:|:----:|:-----:|:------------:|-------------------|
| S5-T1 | RSA key pair generation + management service | Backend | 🔴 | 3h | - | Keys generated, stored, rotatable |
| S5-T2 | OfflineToken DTO + signing service | Backend | 🔴 | 4h | S5-T1 | Token signed with RSA-256 |
| S5-T3 | Token verification endpoint | Backend | 🔴 | 2h | S5-T2 | Server can verify its own tokens |
| S5-T4 | license_tokens table + migration | Database | 🔴 | 1.5h | S5-T2 | Token history tracked |
| S5-T5 | GET /api/v1/public-key endpoint | Backend | 🟡 | 1h | S5-T1 | Public key available |
| S5-T6 | POST /api/v1/token/refresh | Backend | 🟡 | 2h | S5-T2 | Token extended before expiry |
| S5-T7 | revocations table + migration | Database | 🟡 | 1h | - | Revocation list |
| S5-T8 | Revocation service + API integration | Backend | 🟡 | 2h | S5-T7 | Revoked license → client lock |
| S5-T9 | SDK: OfflineValidator with RSA verification | SDK | 🔴 | 3h | S5-T2 | Client verify token signature |
| S5-T10 | SDK: Encrypted token store upgrade (tie to fingerprint) | SDK | 🔴 | 2h | S5-T9 | AES key derived from fingerprint |
| S5-T11 | SDK: Clock tampering detection | SDK | 🟡 | 2h | S5-T9 | Detect backward clock |
| S5-T12 | Unit test: OfflineToken sign/verify | Testing | 🔴 | 2h | S5-T2 | Full crypto test suite |

**Sprint 5 Total: 25.5 hours**

---

### 6.6 Sprint 6: Security Hardening — Hardening Complete (Week 6)

**Goal:** All security features working, penetration testing

**Capacity:** 25 hours

| ID | Task | Type | Prio | Hours | Dependencies | Acceptance Criteria |
|:--:|------|:----:|:----:|:-----:|:------------:|-------------------|
| S6-T1 | POST /api/v1/sync endpoint | Backend | 🔴 | 3h | S5-T2 | Heartbeat + token refresh + revocation check |
| S6-T2 | POST /api/v1/auth (client authentication) | Backend | 🟡 | 2h | S2-T1 | API key → session token exchange |
| S6-T3 | Replay attack protection (timestamp nonce) | Backend | 🔴 | 2h | S2-T1 | Old requests rejected |
| S6-T4 | API key rotation support | Backend | 🟡 | 2h | S1-T6 | Regenerate key, old key grace period |
| S6-T5 | IP whitelist per API client | Backend | 🟡 | 2h | S1-T6 | Restrict by IP range |
| S6-T6 | Security headers middleware | Backend | 🟢 | 1h | - | CSP, HSTS, X-Frame-Options |
| S6-T7 | SDK: Periodic sync + refresh | SDK | 🟡 | 2h | S6-T1 | Auto-sync configurable interval |
| S6-T8 | SDK: Revocation check on sync | SDK | 🟡 | 2h | S6-T1 | Revoked → lock immediately |
| S6-T9 | SDK: Clock tampering automatic recovery | SDK | 🟡 | 2h | S5-T11 | Detect → warn → re-sync → unlock |
| S6-T10 | Security test: brute force | Testing | 🟡 | 2h | S2-T3 | Lockout works |
| S6-T11 | Security test: replay attack | Testing | 🟡 | 1h | S6-T3 | Old signature rejected |
| S6-T12 | Security test: token tampering | Testing | 🔴 | 2h | S5-T9 | Modified token → invalid |
| S6-T13 | Manual security review (all crypto code) | Security | 🔴 | 2h | All above | Code reviewed, no obvious vuln |

**Sprint 6 Total: 25 hours**

---

### 6.7 Sprint 7: Ecosystem — Webhooks & Admin API (Week 7)

**Goal:** Webhook system, admin API, monitoring

**Capacity:** 25 hours

| ID | Task | Type | Prio | Hours | Dependencies | Acceptance Criteria |
|:--:|------|:----:|:----:|:-----:|:------------:|-------------------|
| S7-T1 | Webhook endpoints table + migration | Database | 🟡 | 1h | - | CRUD webhook targets |
| S7-T2 | Webhook service + event dispatcher | Backend | 🟡 | 3h | S7-T1 | POST ke URL on activation, expiry, revocation |
| S7-T3 | Webhook retry + failure handling | Backend | 🟡 | 2h | S7-T2 | 3 retries, failed logged |
| S7-T4 | Webhook management UI (admin panel) | Frontend | 🟢 | 2h | S7-T1 | Admin manage webhook URLs |
| S7-T5 | Admin REST API (CRUD licenses, products) | Backend | 🟡 | 4h | - | Full management via API key |
| S7-T6 | Health check endpoint | Backend | 🟢 | 1h | - | `/api/v1/health` returns OK |
| S7-T7 | Structured logging (JSON format) | Backend | 🟢 | 2h | - | All logs in JSON |
| S7-T8 | Metrics collector service | Backend | 🟢 | 2h | - | Track activation count, active devices |
| S7-T9 | Metrics endpoint | Backend | 🟢 | 2h | S7-T8 | `/api/v1/metrics` |
| S7-T10 | Load test preparation + script | Testing | 🟡 | 3h | - | K6/Locust script |
| S7-T11 | Load test execution + optimization | Testing | 🟡 | 3h | S7-T10 | 1000 concurrent < 500ms |

**Sprint 7 Total: 25 hours**

---

### 6.8 Sprint 8: Documentation & Deployment (Week 8)

**Goal:** Everything documented, deployment-ready

**Capacity:** 25 hours

| ID | Task | Type | Prio | Hours | Dependencies | Acceptance Criteria |
|:--:|------|:----:|:----:|:-----:|:------------:|-------------------|
| S8-T1 | OpenAPI/Swagger documentation | Docs | 🟡 | 4h | All API | All endpoints documented |
| S8-T2 | SDK Integration Guide | Docs | 🔴 | 3h | SDK | Step-by-step with code examples |
| S8-T3 | Deployment Guide | Docs | 🔴 | 3h | Infrastructure | From zero to production |
| S8-T4 | Docker Compose production setup | Infra | 🟡 | 3h | - | `docker compose up` works |
| S8-T5 | GitHub Actions CI/CD | Infra | 🟡 | 3h | - | Test + lint + deploy |
| S8-T6 | User Guide (admin panel) | Docs | 🟢 | 2h | - | Admin manual |
| S8-T7 | .env.example update with all config | Infra | 🟡 | 1h | - | All env vars documented |
| S8-T8 | Final integration test run | Testing | 🔴 | 3h | All previous | All tests green |
| S8-T9 | Bug fixes from final test | Backend | 🔴 | 3h | S8-T8 | All critical bugs fixed |

**Sprint 8 Total: 25 hours**

---

## 7. MVP PRIORITY MATRIX

### MUST HAVE (Phase 1 — Blocking)

| Task | Reason to Include | Consequence if Skipped |
|------|-------------------|------------------------|
| Service layer refactor (LicenseValidationService, DeviceRegistrationService, ActivationService) | Clean code foundation | Technical debt, hard to extend |
| API response standardization | SDK development depends on it | SDK will break on format changes |
| All MVP API endpoints (activate, validate, verify, status) | Core functionality | System cannot function |
| Activation request approve/reject | Admin cannot operate | No way to approve devices |
| Client SDK Composer package | Client cannot integrate | Project has no value |
| CheckLicenseMiddleware | Client app not protected | Anyone can access |
| Bootstrap wizard (basic) | User cannot activate | No UX |
| Local encrypted token storage | Offline mode impossible | No offline grace period |
| FingerprintCollector (basic: hostname + MAC) | Device binding | No anti-abuse |
| Grace period countdown + readonly mode | Offline mode | Grace period useless |
| 9 database indexes | Production performance | Queries slow at 10k+ records |
| Database migrations for all new tables | Schema changes tracked | Cannot deploy |

### SHOULD HAVE (Phase 2 — Important but not blocking)

| Task | Reason | Workaround if Skipped |
|------|--------|----------------------|
| HMAC API authentication | Security | API key in header (less secure but works) |
| RSA signed offline tokens | Anti-tampering | Plain token (vulnerable but works for MVP) |
| Brute-force protection | Security | Manual monitoring |
| Device cooldown | Anti-abuse | Manual device reset |
| Clock tampering detection | Security | Not detected (risk accepted) |
| License events table | Audit | audit_logs partial coverage |
| API key rotation | Security | Manual key change |
| Sync endpoint | Token refresh | Manual re-activation |
| Activation auto-approve (IP range) | UX | Manual approve all |

### NICE TO HAVE (Phase 3 — Future)

| Task | Value | Effort |
|------|-------|:------:|
| Webhook system | Integration | 6h |
| Admin REST API | Automation | 4h |
| Health check endpoint | Monitoring | 1h |
| Structured logging | Debugging | 2h |
| Metrics endpoint | Monitoring | 2h |
| OpenAPI docs | Developer experience | 4h |
| Docker Compose | Deployment | 3h |
| CI/CD pipeline | Automation | 3h |

### FUTURE (Not in v1.0)

| Feature | Reason to Skip |
|---------|---------------|
| Multi-tenant | Single-tenant cukup untuk 1-2 tahun pertama |
| Stripe integration | Manual subscription management OK |
| Customer self-service portal | Admin panel cukup |
| Real-time WebSocket | Periodic polling cukup |
| Usage-based billing | Belum ada kebutuhan |
| AI fraud detection | Belum perlu, abuse masih manual |
| Mobile app SDK | Fokus Laravel dulu |
| Marketplace / app store | Jauh di masa depan |
| GraphQL API | REST cukup |

---

## 8. DEPENDENCY GRAPH

```
Sprint 1 (Foundation)
  S1-T1 LicenseValidationService ◄──── foundation
  S1-T2 DeviceRegistrationService ◄─── foundation
  S1-T3 ActivationService ◄─────────── foundation
  S1-T4 Events ◄────────────────────── depends on S1-T1..T3
  S1-T5 Database indexes ◄──────────── independent
  S1-T6 ApiClient model ◄───────────── independent
  S1-T7 ApiClient UI ◄──────────────── depends on S1-T6
  S1-T8 API standardization ◄───────── independent
  S1-T9 Exception handler ◄─────────── independent
  S1-T10 FormRequest update ◄───────── independent
  S1-T11-T12 Tests ◄────────────────── depends on S1-T1..T3

Sprint 2 (API Core)
  S2-T1 HMAC middleware ◄───────────── depends on S1-T6
  S2-T2 Rate limiting ◄─────────────── depends on S2-T1
  S2-T3 Brute force ◄───────────────── independent
  S2-T4 Activate endpoint ◄─────────── depends on S1-T1..T3
  S2-T5 Validate endpoint ◄─────────── depends on S1-T1
  S2-T6 Status endpoint ◄───────────── depends on S1-T1
  S2-T7 Verify endpoint ◄───────────── depends on S1-T3
  S2-T8 Admin approve UI ◄──────────── depends on S2-T4
  S2-T9 Activation list UI ◄────────── depends on S2-T8
  S2-T10 Device list UI ◄───────────── depends on S2-T4
  S2-T11-T13 Tests ◄────────────────── depends on S2-T4..T8

Sprint 3 (Client SDK)
  S3-T1 Package scaffolding ◄───────── independent
  S3-T2 ServiceProvider ◄───────────── depends on S3-T1
  S3-T3 LicenseClient ◄─────────────── depends on S2-T1
  S3-T4 LocalLicense model ◄────────── depends on S3-T1
  S3-T5 Encrypted storage ◄─────────── depends on S3-T4
  S3-T6 FingerprintCollector ◄──────── independent
  S3-T7 Middleware ◄────────────────── depends on S3-T3, S3-T6
  S3-T8 Wizard step 1-2 ◄──────────── depends on S3-T6, S3-T7
  S3-T9 Wizard step 3-4 ◄──────────── depends on S3-T3

Sprint 4 (SDK Completion)
  S4-T1 Grace countdown UI ◄────────── depends on S3-T7
  S4-T2 Readonly + lock ◄───────────── depends on S3-T7
  S4-T3 Sync command ◄──────────────── depends on S3-T3
  S4-T4 Pending polling ◄───────────── depends on S3-T9
  S4-T5 Auto-approve ◄──────────────── depends on S2-T8
  S4-T6 Device cooldown ◄───────────── depends on S2-T4
  S4-T7 license_events table ◄──────── independent
  S4-T8 Event logging ◄─────────────── depends on S4-T7
  S4-T9 Integration test ◄──────────── depends on all SDK
  S4-T10 E2E grace test ◄───────────── depends on S4-T2

... dan seterusnya untuk Sprint 5-8
```

### Critical Path

The critical path (longest chain of dependencies) is:

```
Week 1: Service refactor → Events
Week 2: ApiClient → HMAC → LicenseClient (SDK)
Week 3: Package → LicenseClient → Middleware → Wizard
Week 4: Wizard completion
Week 5: RSA keys → OfflineToken → SDK Validator
Week 6: Sync endpoint → SDK Sync
Week 7: Webhooks, Admin API
Week 8: Documentation, Deployment
```

**Total critical path: 8 weeks**

**Parallel work possible:**
- Database indexes + API standardization (Week 1, with service refactor)
- FingerprintCollector + Package scaffolding (Week 3, independent)
- Load test prep + Documentation (Week 7-8, parallel)

---

## 9. DATABASE MIGRATION PLAN

### 9.1 Migration Sequence

| Order | Migration | Phase | Risk | Rollback |
|:-----:|-----------|:-----:|:----:|:--------:|
| 1 | Add 9 database indexes | 1 | Low | `DROP INDEX` |
| 2 | Create `api_clients` table | 1 | Low | `DROP TABLE` |
| 3 | Create `license_tokens` table | 2 | Low | `DROP TABLE` |
| 4 | Create `revocations` table | 2 | Low | `DROP TABLE` |
| 5 | Create `license_events` table | 2 | Low | `DROP TABLE` |
| 6 | Create `webhook_endpoints` table | 3 | Low | `DROP TABLE` |
| 7 | Add `fingerprint_components` to `devices` | 2 | Medium | `DROP COLUMN` |
| 8 | Add `fingerprint_algorithm` to `devices` | 2 | Low | `DROP COLUMN` |
| 9 | Add `signature` to `licenses` | 2 | Medium | `DROP COLUMN` |

### 9.2 Migration Details

**Migration 1: Database Indexes**
```php
// 2026_05_16_000001_add_license_monitor_indexes.php
Schema::table('licenses', function (Blueprint $table) {
    $table->index('key');
    $table->index('status');
    $table->index('expires_at');
});
Schema::table('devices', function (Blueprint $table) {
    $table->index('fingerprint');
    $table->index('license_id');
});
Schema::table('activation_requests', function (Blueprint $table) {
    $table->index('status');
});
Schema::table('audit_logs', function (Blueprint $table) {
    $table->index(['entity_type', 'entity_id']);
    $table->index('created_at');
});
Schema::table('subscriptions', function (Blueprint $table) {
    $table->index('status');
});
```

**Migration 2: api_clients**
```php
// 2026_05_16_000002_create_api_clients_table.php
Schema::create('api_clients', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->string('api_key', 64)->unique();
    $table->string('api_secret', 128);
    $table->ipAddress('allowed_ips')->nullable();
    $table->boolean('is_active')->default(true);
    $table->timestamp('last_used_at')->nullable();
    $table->timestamps();
});
```

**Migration 3: license_tokens**
```php
// 2026_05_16_000003_create_license_tokens_table.php
Schema::create('license_tokens', function (Blueprint $table) {
    $table->id();
    $table->foreignId('license_id')->constrained()->cascadeOnDelete();
    $table->foreignId('device_id')->constrained()->cascadeOnDelete();
    $table->string('token_hash', 64)->unique();
    $table->text('signed_token');
    $table->timestamp('issued_at');
    $table->timestamp('expires_at');
    $table->timestamp('revoked_at')->nullable();
    $table->timestamps();

    $table->index(['license_id', 'device_id']);
    $table->index('expires_at');
});
```

**Migration 4: revocations**
```php
// 2026_05_16_000004_create_revocations_table.php
Schema::create('revocations', function (Blueprint $table) {
    $table->id();
    $table->string('license_key_hash', 64);
    $table->timestamp('revoked_at');
    $table->string('reason')->nullable();
    $table->timestamps();

    $table->index('license_key_hash');
});
```

**Migration 5: license_events**
```php
// 2026_05_16_000005_create_license_events_table.php
Schema::create('license_events', function (Blueprint $table) {
    $table->id();
    $table->foreignId('license_id')->constrained()->cascadeOnDelete();
    $table->foreignId('device_id')->nullable()->constrained()->nullOnDelete();
    $table->string('event');
    $table->ipAddress('ip_address')->nullable();
    $table->json('metadata')->nullable();
    $table->timestamp('created_at');

    $table->index(['license_id', 'created_at']);
    $table->index('event');
});
```

**Migration 6: webhook_endpoints**
```php
// 2026_05_16_000006_create_webhook_endpoints_table.php
Schema::create('webhook_endpoints', function (Blueprint $table) {
    $table->id();
    $table->string('url');
    $table->json('events');
    $table->string('secret', 64);
    $table->boolean('is_active')->default(true);
    $table->timestamps();
});
```

**Migration 7-9: Alter existing tables**
```php
// 2026_05_16_000007_add_fingerprint_columns_to_devices.php
Schema::table('devices', function (Blueprint $table) {
    $table->json('fingerprint_components')->nullable()->after('fingerprint');
    $table->string('fingerprint_algorithm', 20)->default('v1')->after('fingerprint_components');
});

// 2026_05_16_000008_add_signature_to_licenses.php
Schema::table('licenses', function (Blueprint $table) {
    $table->text('signature')->nullable()->after('notes');
});
```

---

## 10. API DEVELOPMENT PLAN

### 10.1 Current API (Existing)

| Method | Endpoint | Auth | Status | Issue |
|--------|----------|:----:|:------:|-------|
| POST | `/api/v1/activate` | ❌ None | ⚠️ Needs update | Need HMAC + new services |
| POST | `/api/v1/validate` | ❌ None | ⚠️ Needs update | Need HMAC + DTO response |
| GET | `/api/v1/verify/{key}/{fp}` | ❌ None | ⚠️ Needs update | Need HMAC |
| GET | `/api/v1/status/{key}/{fp}` | ❌ None | ⚠️ Needs update | Need HMAC |
| POST | `/api/v1/check-update` | ❌ None | ❌ Placeholder | Need real implementation or remove |

### 10.2 Required Fixes (Phase 1-2)

| # | Fix | Endpoints | Sprint | Effort |
|:-:|-----|:---------:|:------:|:------:|
| 1 | HMAC authentication | All | S2 | 3h |
| 2 | DTO response format | All | S1 | 3h |
| 3 | Global exception handler | All | S1 | 2h |
| 4 | Rate limiting per client | All | S2 | 2h |
| 5 | Brute-force protection | activate, validate | S2 | 2h |
| 6 | Replay attack protection | All | S6 | 2h |
| 7 | Replace check-update with real or remove | check-update | S2 | 1h |

### 10.3 New Endpoints (Phase 2-3)

| Method | Endpoint | Description | Sprint | Effort |
|--------|----------|-------------|:------:|:------:|
| POST | `/api/v1/auth` | Client authentication | S6 | 2h |
| GET | `/api/v1/public-key` | RSA public key | S5 | 1h |
| POST | `/api/v1/sync` | Periodic sync + token refresh | S6 | 3h |
| POST | `/api/v1/token/refresh` | Refresh offline token | S5 | 2h |
| GET | `/api/v1/health` | Health check | S7 | 1h |
| GET | `/api/v1/metrics` | Prometheus metrics | S7 | 2h |
| GET | `/api/v1/admin/*` | Admin REST API | S7 | 4h |

### 10.4 API Response Standard (DTO)

```json
// Success
{
  "success": true,
  "message": "License activated successfully",
  "data": {
    // endpoint-specific payload
  },
  "meta": {
    "server_time": "2026-05-16T10:00:00Z",
    "server_version": "1.0.0",
    "request_id": "req_abc123"
  }
}

// Error
{
  "success": false,
  "message": "License key not found",
  "errors": {
    "license_key": ["Invalid license key format"]
  },
  "meta": {
    "server_time": "2026-05-16T10:00:00Z",
    "server_version": "1.0.0",
    "request_id": "req_def456"
  }
}
```

---

## 11. CLIENT SDK PLAN

### 11.1 Package Specification

| Attribute | Value |
|-----------|-------|
| **Package name** | `adptra01/laravel-license-client` |
| **Type** | Composer library |
| **PHP requirement** | ^8.1 |
| **Laravel requirement** | ^10.0 | ^11.0 |
| **License** | MIT |
| **Install command** | `composer require adptra01/laravel-license-client` |

### 11.2 Installation Steps (After Published)

```bash
# 1. Install
composer require adptra01/laravel-license-client

# 2. Publish config
php artisan vendor:publish --tag=license-client-config

# 3. Run migration
php artisan migrate

# 4. Configure .env
LICENSE_SERVER_URL=https://your-license-server.com
LICENSE_API_KEY=your-api-key
LICENSE_API_SECRET=your-api-secret

# 5. Add middleware to kernel
// app/Http/Kernel.php
protected $middlewareGroups = [
    'web' => [
        // ...
        \Adptra01\LicenseClient\Http\Middleware\CheckLicenseMiddleware::class,
    ],
];

# 6. Done!
```

### 11.3 SDK Configuration (`config/license-client.php`)

```php
return [
    // License server connection
    'server_url' => env('LICENSE_SERVER_URL'),
    'api_key' => env('LICENSE_API_KEY'),
    'api_secret' => env('LICENSE_API_SECRET'),

    // Behavior
    'grace_warning_days' => 5,      // Show countdown when N days remaining
    'grace_critical_days' => 1,     // Show critical warning
    'sync_interval' => 3600,        // Sync every 3600 seconds (1 hour)
    'offline_grace_seconds' => 604800, // 7 days

    // Features
    'force_license' => true,        // Block access if unlicensed
    'allow_readonly' => true,       // Grace expired → readonly
    'enable_wizard' => true,        // Show bootstrap wizard
    'collect_fingerprint' => true,  // Collect hardware info

    // Storage
    'encryption_key' => env('LICENSE_ENCRYPTION_KEY'),
];
```

### 11.4 Middleware Behavior

```
CheckLicenseMiddleware::handle(request)
│
├── Is licensed?
│   ├── YES: Check token validity
│   │   ├── Valid → Next request (full access)
│   │   └── Invalid → Redirect to re-activation
│   │
│   └── NO: Check if in grace period
│       ├── YES: Add X-Grace-Remaining header → Next request (degraded UI)
│       └── NO: Redirect to bootstrap wizard
│
└── Wizard handles:
    ├── Step 1: Collect fingerprint → detect hardware
    ├── Step 2: Enter license key → validate format
    ├── Step 3: Send activation → POST /api/v1/activate
    │   ├── Pending → Poll every 30s → Wait approval
    │   └── Approved → Store token → Redirect to app
    └── Step 4: App ready
```

---

## 12. TESTING STRATEGY

### 12.1 Test Pyramid

```
         ╱╲
        ╱ E2E ╲        2 tests (Playwright)
       ╱────────╲
      ╱Integration╲     5 tests (PHPUnit feature)
     ╱──────────────╲
    ╱  Feature tests  ╲   15 tests
   ╱────────────────────╲
  ╱   Unit tests          ╲  25+ tests
 ╱──────────────────────────╲
```

### 12.2 Unit Tests (25+)

| Test | File | Coverage |
|------|------|----------|
| LicenseKeyServiceTest | `tests/Unit/Services/LicenseKeyServiceTest.php` | generate, validateFormat, mask, sign, verify |
| LicenseValidationServiceTest | `tests/Unit/Services/LicenseValidationServiceTest.php` | validate, expired, suspended |
| DeviceRegistrationServiceTest | `tests/Unit/Services/DeviceRegistrationServiceTest.php` | register, limit, fingerprint |
| ActivationServiceTest | `tests/Unit/Services/ActivationServiceTest.php` | create, approve, reject, cooldown |
| OfflineTokenTest | `tests/Unit/Services/OfflineTokenTest.php` | sign, verify, expiry |
| ClockTamperingDetectorTest | `tests/Unit/Security/ClockTamperingDetectorTest.php` | detect tampering |
| FingerprintCollectorTest | `tests/Unit/Sdk/FingerprintCollectorTest.php` | collect components |
| LicenseClientTest | `tests/Unit/Sdk/LicenseClientTest.php` | HMAC signing, request |
| OfflineValidatorTest | `tests/Unit/Sdk/OfflineValidatorTest.php` | verify, expiry, tamper |

### 12.3 Feature Tests (15+)

| Test | File | Hooks |
|------|------|-------|
| LicenseActivationTest | `tests/Feature/Api/LicenseActivationTest.php` | ✅ Already exists — update |
| LicenseValidationTest | `tests/Feature/Api/LicenseValidationTest.php` | ✅ Already exists — update |
| ActivationApproveRejectTest | `tests/Feature/Api/ActivationApproveRejectTest.php` | ❌ New |
| SyncEndpointTest | `tests/Feature/Api/SyncEndpointTest.php` | ❌ New |
| TokenRefreshTest | `tests/Feature/Api/TokenRefreshTest.php` | ❌ New |
| BruteForceProtectionTest | `tests/Feature/Api/BruteForceProtectionTest.php` | ❌ New |
| HMACAuthTest | `tests/Feature/Api/HMACAuthTest.php` | ❌ New |
| HealthEndpointTest | `tests/Feature/Api/HealthEndpointTest.php` | ❌ New |

### 12.4 Integration Tests (5)

| Test | Description |
|------|-------------|
| FullActivationFlowTest | SDK install → wizard → activate → approve → validate → offline → sync |
| GracePeriodTest | Activate → wait grace → readonly → lock → re-activate |
| RevocationTest | Activate → admin revoke → sync → client lock |
| ClockTamperingTest | Activate → rewind clock → detect → lock |
| DeviceMigrationTest | Activate device A → reset → activate device B |

### 12.5 Licensing Simulation

```bash
# Manual smoke test script
# 1. Create product
curl -X POST $SERVER/api/v1/admin/products \
  -H "Authorization: Bearer $ADMIN_TOKEN" \
  -d '{"name":"Test App","slug":"test-app"}'

# 2. Create license
curl -X POST $SERVER/api/v1/admin/licenses \
  -H "Authorization: Bearer $ADMIN_TOKEN" \
  -d '{"product_id":1,"user_id":1,"max_devices":3}'

# 3. Activate device (from client)
curl -X POST $SERVER/api/v1/activate \
  -H "X-API-Key: $CLIENT_KEY" \
  -H "X-Request-Signature: $HMAC" \
  -d '{"license_key":"XXXX-XXXX-XXXX-XXXX","device":{"fingerprint":"abc...","name":"Test PC","platform":"linux"}}'

# 4. Approve (admin)
curl -X POST $SERVER/api/v1/admin/activation-requests/1/approve \
  -H "Authorization: Bearer $ADMIN_TOKEN"

# 5. Validate
curl -X POST $SERVER/api/v1/validate \
  -H "X-API-Key: $CLIENT_KEY" \
  -H "X-Request-Signature: $HMAC" \
  -d '{"license_key":"XXXX-XXXX-XXXX-XXXX","device":{"fingerprint":"abc..."}}'
```

---

## 13. DEPLOYMENT PLAN

### 13.1 Development Setup

```
Developer workstation:
├── PHP 8.3 + Composer
├── SQLite (development database)
├── Laravel Valet / Sail
├── Redis (optional for dev)
└── Node.js + npm (for frontend build)

Commands:
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
npm install && npm run build
php artisan serve
```

### 13.2 Staging Setup

```
Single VPS (DigitalOcean $12/mo):
├── 1 vCPU, 2GB RAM, 50GB SSD
├── Ubuntu 24.04 LTS
├── PHP 8.3 + FPM
├── MySQL 8.0
├── Redis 7
├── Nginx
├── Supervisor (queue worker)
└── GitHub Actions deploy

.env differences:
APP_ENV=staging
APP_DEBUG=true
DB_CONNECTION=mysql
CACHE_STORE=redis
QUEUE_CONNECTION=redis

Deploy: GitHub Actions → rsync/Deployer → php artisan migrate
```

### 13.3 Production Setup

```
Single VPS (DigitalOcean $24/mo):
├── 2 vCPU, 4GB RAM, 80GB SSD
├── Ubuntu 24.04 LTS
├── PHP 8.3 + FPM (opcache enabled)
├── MySQL 8.0 (with daily backup)
├── Redis 7 (cache + queue)
├── Nginx
├── Supervisor (queue worker: 2 processes)
├── Fail2ban
├── UFW (only 80, 443, SSH)
├── Let's Encrypt SSL
└── Monitoring: Laravel Pulse / Sentry

.env differences:
APP_ENV=production
APP_DEBUG=false
DB_CONNECTION=mysql
CACHE_STORE=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis

Backup:
- Daily database backup (automated)
- Weekly full VPS snapshot
- Backup retention: 30 days

Scaling triggers:
- > 5000 devices: Upgrade to $48/mo (4 vCPU, 8GB RAM)
- > 20000 devices: Add read replica MySQL
- > 100 clients: Re-evaluate architecture
```

### 13.4 Docker Compose (Optional)

```yaml
# docker-compose.yml
services:
  app:
    image: adptra01/license-monitor:latest
    ports:
      - "8080:80"
    environment:
      - APP_ENV=production
      - DB_CONNECTION=mysql
      - CACHE_STORE=redis
    depends_on:
      - mysql
      - redis
    volumes:
      - storage:/var/www/storage

  queue:
    image: adptra01/license-monitor:latest
    command: php artisan queue:work --tries=3
    depends_on:
      - app
      - redis

  mysql:
    image: mysql:8.0
    volumes:
      - mysql-data:/var/lib/mysql

  redis:
    image: redis:7-alpine
```

---

## 14. SECURITY ROADMAP

### 14.1 Immediate Fixes (Phase 1, Week 1-2)

| # | Fix | Effort | Impact | Complexity |
|:-:|-----|:------:|:------:|:----------:|
| 1 | HMAC API authentication | 3h | 🔴 Critical | Easy — middleware pattern |
| 2 | Brute force rate limiting | 2h | 🔴 Critical | Easy — cache-based counter |
| 3 | Rate limit 60/min per API key | 2h | 🟡 High | Easy — Laravel throttle |
| 4 | Remove debug logging from API responses | 0.5h | 🟡 High | Trivial |
| 5 | Add `.env` to `.gitignore` protection | 0.5h | 🔴 Critical | Trivial |
| 6 | CORS restrict to known origins | 1h | 🟡 High | Easy — config/cors.php |

### 14.2 Medium-Term (Phase 2, Week 5-6)

| # | Fix | Effort | Impact | Complexity |
|:-:|-----|:------:|:------:|:----------:|
| 7 | RSA signed offline tokens | 7h | 🔴 Critical | Medium — OpenSSL |
| 8 | Replay attack protection | 2h | 🟡 High | Easy — nonce + timestamp |
| 9 | AES-256-GCM encrypted local storage | 3h | 🟡 High | Medium — sodium extension |
| 10 | Clock tampering detection | 2h | 🟡 High | Medium — multi-checkpoint |
| 11 | API key rotation | 2h | 🟢 Medium | Easy — regenerate + grace |
| 12 | IP whitelist per client | 2h | 🟢 Medium | Easy — middleware check |

### 14.3 Advanced Hardening (Phase 3, Week 7-8)

| # | Fix | Effort | Impact | Complexity |
|:-:|-----|:------:|:------:|:----------:|
| 13 | Security headers (CSP, HSTS) | 1h | 🟢 Medium | Easy — middleware |
| 14 | Audit log retention policy | 1h | 🟢 Low | Easy — scheduler |
| 15 | Rate limit monitoring + alerting | 2h | 🟢 Medium | Medium |
| 16 | Composer dependency audit (regular) | 1h/mo | 🟡 High | Easy — `composer audit` |
| 17 | Fail2ban integration for API | 2h | 🟢 Medium | Medium — nginx level |

---

## 15. FINAL RECOMMENDATION

### 15.1 Realistic Timeline

| Scenario | Timeline | Notes |
|----------|:--------:|-------|
| Solo dev + AI, part-time | 12-16 weeks | 10-15 hours/week |
| Solo dev + AI, full-time | 6-8 weeks | 40 hours/week |
| 2 devs + AI, full-time | 4-6 weeks | Parallel backend + SDK |
| Dev + PM + QA, full-time | 3-4 weeks | Dedicated roles |

**Recommended:** Solo dev + AI, ~8 minggu full-time.

### 15.2 Technical Warnings

1. **Don't over-engineer crypto.** RSA-2048 + SHA-256 is plenty. Don't implement your own cipher.
2. **Don't accept AI-generated crypto code blindly.** Always review. One `==` instead of `hash_equals()` and your security is gone.
3. **Don't micro-optimize before Phase 3.** SQLite is fine for dev. 1000 concurrent is fine for MySQL.
4. **Don't build features nobody asked for.** Webhooks, admin API, metrics — wait for customer demand.
5. **Don't rewrite existing code that works.** The existing admin panel is fine. Focus on SDK + security.

### 15.3 Scaling Advice

```
Small (< 1000 devices) ─── DigitalOcean $12/mo ─── No changes needed
Medium (< 10000 devices) ── DO $24/mo ─── Add Redis, queue workers
Large (< 50000 devices) ─── DO $48/mo ─── Read replica, optimize queries
Enterprise (50000+) ──────── Dedicated infra ─── Re-evaluate architecture
```

**Jangan pikirkan scaling sebelum mencapai 1000 devices aktif.**

### 15.4 Anti-Overengineering Advice

| ❌ Jangan | ✅ Lakukan |
|-----------|-----------|
| Service mesh / Istio | Docker Compose |
| Event sourcing / CQRS | Simple events + listeners |
| GraphQL | REST API v1 |
| Kubernetes | Single VPS |
| Multi-tenant data isolation | Single-tenant with field filter |
| WebSocket real-time | Periodic polling |
| Redis Cluster | Single Redis instance |
| Microservices | Laravel monolith |
| Custom encryption algorithm | OpenSSL + sodium |
| White-label SDK | Open source package |

### 15.5 AI-Assisted Development Advice

**Do give AI:**
- ✅ Boilerplate: migrations, factories, config files
- ✅ CRUD: controllers, models with fillable
- ✅ Tests: especially repetitive test patterns
- ✅ Blade views: wizard UI, admin panels
- ✅ Service scaffolding: class structure, method signatures

**Don't give AI:**
- ❌ Security-critical: HMAC, RSA signing, encryption
- ❌ Business logic: licensing rules, activation flow
- ❌ Complex state machines: activation state transitions
- ❌ Schema design: database relationships
- ❌ Package architecture: SDK structure, middleware design

**AI prompt template for this project:**
```
[CONTEXT]
- Laravel 13 application
- PHP 8.3
- Licensing server

[TASK]
Create a [class/trait/middleware] that [specific function].

[CONSTRAINTS]
- Must use [specific Laravel feature]
- Must handle [specific edge case]
- Output PHP with strict types, return types, PHPDoc
- Do NOT use [avoided pattern]

[TESTS]
Generate PHPUnit test that covers:
- Happy path
- [specific failure mode]
- Edge case: [edge case]
```

### 15.6 What Success Looks Like

**After Phase 1 (Week 4):**
```
1. Admin creates product "MyApp" via UI
2. Admin creates license key for client
3. Client installs `composer require adptra01/laravel-license-client`
4. Client configures middleware + .env
5. Client visits app → sees bootstrap wizard
6. Client enters license key → fingerprint collected
7. Server registers device → pending approval
8. Admin approves via UI
9. Client app unlocks → full access
10. 7 days offline → grace countdown visible
11. Client syncs → token refreshed
```

That's the MVP. Everything else is polish.

---

## 16. APPENDIX

### 16.1 Risk Register

| ID | Risk | Prob | Impact | Mitigation | Owner |
|:--:|------|:----:|:------:|------------|:-----:|
| R01 | Client SDK breaking Laravel app | Low | Critical | Extensive testing before publish |
| R02 | Crypto implementation bug | Medium | Critical | Manual review + test vectors |
| R03 | Fingerprint collision | Low | Medium | Multi-factor + partial matching |
| R04 | License key brute force | Medium | High | Rate limit + lockout + monitoring |
| R05 | Token forgery in transit | Low | Critical | HMAC + RSA signing |
| R06 | Database migration fails in production | Low | High | Test on staging first |
| R07 | Client SDK incompatible with PHP 8.1 | Medium | Medium | CI test matrix |
| R08 | AI generates vulnerable code | High | Critical | Manual review for all security code |
| R09 | Grace period calculation off by one | Medium | Medium | +1 day buffer, test clock boundaries |
| R10 | Admin panel broken after refactor | Medium | High | Regression test suite |

### 16.2 Effort Summary by Epic

| Epic | Hours | Sprints | Complexity |
|------|:-----:|:-------:|:----------:|
| E-01 Licensing Core | 14h | S1 | Medium |
| E-02 Activation Flow | 10h | S1-S2 | High |
| E-03 Device Management | 8h | S2, S4 | Medium |
| E-04 REST API v1 | 18h | S1-S2, S5-S6 | High |
| E-05 Admin Panel | 8h | S1-S2 | Low |
| E-06 Client SDK | 23h | S3-S4 | High |
| E-07 Bootstrap Wizard | 10h | S3-S4 | Medium |
| E-08 Offline Token System | 16h | S5 | High |
| E-09 Security | 18h | S2, S5-S6 | High |
| E-10 Monitoring & Infra | 16h | S7 | Medium |
| Documentation | 12h | S8 | Low |
| Testing (cross-cutting) | 22h | All | Medium |
| **Total** | **~175h** | **8 sprints** | |

### 16.3 Glossary

| Term | Definition |
|------|-----------|
| License Server | Server yang menerbitkan dan memvalidasi lisensi |
| License Client | Aplikasi client yang menggunakan lisensi |
| Activation | Proses mengikat lisensi ke perangkat tertentu |
| Grace Period | Masa tenggang offline setelah aktivasi berhasil |
| Offline Token | Data terenkripsi + signed yang membuktikan lisensi valid |
| Fingerprint | Hash unik dari hardware client (CPU, MAC, hostname, dll) |
| HMAC | Hash-based Message Authentication Code |
| RSA | Asymmetric encryption algorithm untuk signing token |
| Device Cooldown | Jeda waktu minimal antar device reset |
| Heartbeat | Request periodik client ke server untuk sync status |
| Readonly Mode | Mode terbatas setelah grace period habis (bisa lihat data, tidak bisa ubah) |
| Lock Mode | Mode full block setelah grace period habis total |
