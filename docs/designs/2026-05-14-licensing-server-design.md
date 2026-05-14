# Design Document: Monitoring Licensing Server

**Date:** 2026-05-14
**Status:** Draft
**PRD Reference:** `docs/prd-server-web.md`

---

## Table of Contents

1. [Feature Overview](#1-feature-overview)
2. [System Architecture](#2-system-architecture)
3. [Authentication Flow](#3-authentication-flow)
4. [Database Schema](#4-database-schema)
5. [Models & Relationships](#5-models--relationships)
6. [API Contract](#6-api-contract)
7. [Business Logic & Workflow](#7-business-logic--workflow)
8. [Client Lifecycle Flow](#8-client-lifecycle-flow)
9. [Failure Handling](#9-failure-handling)
10. [Security Rules](#10-security-rules)
11. [Admin Panel](#11-admin-panel)
12. [Admin Workflow](#12-admin-workflow)
13. [Testing Strategy](#13-testing-strategy)
14. [Development Milestone](#14-development-milestone)
15. [Design Decisions](#15-design-decisions)

---

## 1. Feature Overview

Centralized licensing server untuk aplikasi Laravel berbasis subscription. Sistem mengelola license creation, device activation & binding, subscription periods, dan validasi semi-offline dengan grace period via cache.

**Scope Phase 1:**
- Multi-product support
- License CRUD dengan multiple device binding
- Subscription management (manual billing)
- Device validation & activation API
- Device migration dengan approval flow
- Semi-offline cache validation (7 hari grace period)
- Admin panel (Fortify + Livewire + Volt) untuk monitoring & approval
- Audit logging

**Key Constraints:**
- Semi-online: client bisa offline hingga 7 hari
- 1 license bisa dipasang di multiple devices (max_devices per license)
- License key format: `LIC-XXXX-XXXX` (random readable)
- Manual billing — no payment gateway integration
- Admin menggunakan Fortify auth yang sudah terinstall + Livewire admin
- Admin panel via Folio pages at `/admin`
- Client apps consume REST API tanpa auth token — validasi via license_key

---

## 2. System Architecture

### 2.1 High-Level Architecture

```
┌──────────────────────────────────────────────────────────────┐
│                    Monitoring License Server                  │
│                     monitor.test (Laravel 12)                 │
│                                                              │
│  ┌─────────────────────────────────────────────────────┐    │
│  │           Admin Panel (Livewire + Volt + Folio)      │    │
│  │           /admin/*                                   │    │
│  │                                                      │    │
│  │  ┌──────────┐ ┌──────────┐ ┌──────────────────┐    │    │
│  │  │ Dashboard│ │ Products │ │  Subscription    │    │    │
│  │  │ (Volt)   │ │ (Volt)   │ │  Plans (Volt)    │    │    │
│  │  └──────────┘ └──────────┘ └──────────────────┘    │    │
│  │  ┌──────────┐ ┌──────────┐ ┌──────────────────┐    │    │
│  │  │ Licenses │ │ Devices  │ │ Activation Req   │    │    │
│  │  │ (Volt)   │ │ (Volt)   │ │ (Volt)           │    │    │
│  │  └──────────┘ └──────────┘ └──────────────────┘    │    │
│  │  ┌──────────┐                                       │    │
│  │  │Audit Logs│                                       │    │
│  │  │ (Volt)   │     Fortify sidebar layout             │    │
│  │  └──────────┘                                       │    │
│  └─────────────────────────────────────────────────────┘    │
│  └─────────────────────┘                                      │
│                                                              │
│  ┌──────────────────────────────────────────────────────┐    │
│  │              Licensing API (no auth)                  │    │
│  │  POST /api/license/validate  ← Rate limited 60/min   │    │
│  │  POST /api/license/activate  ← Rate limited 30/min   │    │
│  │  POST /api/license/check-update                      │    │
│  └────────────────────────┬─────────────────────────────┘    │
│                           │                                   │
│  ┌────────────────────────▼─────────────────────────────┐    │
│  │              Service Layer                            │    │
│  │  LicenseService  │  LicenseKeyService                 │    │
│  └────────────────────────┬─────────────────────────────┘    │
│                           │                                   │
│  ┌────────────────────────▼─────────────────────────────┐    │
│  │              Database (MySQL/MariaDB)                 │    │
│  │  products │ licenses │ devices │ activation_requests  │    │
│  │  subscriptions │ subscription_plans │ audit_logs      │    │
│  └──────────────────────────────────────────────────────┘    │
└──────────────────────────────────────────────────────────────┘
                           │ HTTPS API
                           │
┌──────────────────────────▼───────────────────────────────────┐
│                   Client Application                          │
│                   laravel-pos.test                            │
│                                                               │
│  ┌──────────────────────────────────────────────────────┐    │
│  │              LicenseService (client-side)             │    │
│  │  - activate(license_key, device_id)                   │    │
│  │  - validate() → caches result locally                 │    │
│  │  - isLicenseValid() → checks local cache first        │    │
│  └──────────────────────────────────────────────────────┘    │
│                                                               │
│  ┌──────────────────────────────────────────────────────┐    │
│  │              License Middleware                       │    │
│  │  - intercepts every request (except login)           │    │
│  │  - checks local cache validity                       │    │
│  │  - if cache expired → call validation API            │    │
│  │  - if invalid → block access / force logout          │    │
│  └──────────────────────────────────────────────────────┘    │
│                                                               │
│  ┌──────────────────────────────────────────────────────┐    │
│  │              Local Cache (file-based)                 │    │
│  │  stores: status, expired_at, cache_until, device_id   │    │
│  └──────────────────────────────────────────────────────┘    │
└──────────────────────────────────────────────────────────────┘
```

### 2.2 Component Diagram

```
┌────────────────────────────┐
│      HTTP Request          │
│  (from client app)         │
└────────────┬───────────────┘
             │
┌────────────▼───────────────┐
│  Rate Limiter Middleware    │
│  throttle:60,1 (validate)  │
│  throttle:30,1 (activate)  │
└────────────┬───────────────┘
             │
┌────────────▼───────────────┐
│  Form Request Validation   │
│  ValidateLicenseRequest    │
│  ActivateLicenseRequest    │
└────────────┬───────────────┘
             │
┌────────────▼───────────────┐
│  Invokable Controller      │
│  (thin, delegates to       │
│   service layer)           │
└────────────┬───────────────┘
             │
┌────────────▼───────────────┐
│  LicenseService            │
│  - validate()              │
│  - activate()              │
│  - checkUpdate()           │
└────────────┬───────────────┘
             │
┌────────────▼───────────────┐
│  Eloquent Models           │
│  License :: Device :: etc  │
└────────────┬───────────────┘
             │
┌────────────▼───────────────┐
│  MySQL Database            │
└────────────────────────────┘
```

### 2.3 Trust Boundary

```
                    UNTRUSTED                          TRUSTED
┌──────────────────────────────┐    ┌──────────────────────────────┐
│       Client App             │    │     License Server           │
│  (can be modified by user)   │    │  (under admin control)       │
│                              │    │                              │
│  - NEVER trust client data   │───▶│  - Authoritative source      │
│  - Client can lie about:     │    │  - All decisions here        │
│    * license status          │    │  - Validate everything       │
│    * device_id uniqueness    │    │  - Rate limit per IP         │
│    * cache contents          │    │  - Log every request         │
│    * app_version             │    │                              │
└──────────────────────────────┘    └──────────────────────────────┘

PRINCIPLE: Server is the single source of truth for ALL license decisions.
Client-side validation is a CONVENIENCE, not a security measure.
```

---

## 3. Authentication Flow

### 3.1 Client API (no-auth)

Public API endpoint tidak menggunakan API token/auth. Validasi dilakukan via kombinasi:
- `license_key` — unique per license
- `device_id` — unique per device
- Rate limiting per IP — mencegah brute force
- Throttling per license key — mencegah abuse

**Tidak memerlukan Sanctum token untuk public API.**

### 3.2 Admin Authentication

```
Admin membuka /admin
    ↓
Cek session login
    ├── Sudah login → tampilkan dashboard
    ↓ Belum login → redirect ke /login
Tampilkan form login (Fortify)
    ↓
Admin input email + password
    ↓
Verifikasi credentials
    ├── Gagal → kembali ke login dengan error
    ↓ Sukses
Cek is_admin flag
    ├── false → "Unauthorized" (bukan admin)
    ↓ true
Redirect ke /admin/dashboard
```

Admin authentication menggunakan Fortify yang sudah terinstall. Perbedaan:
- Semua user terdaftar via `/register` — hanya admin yang create
- Hanya user dengan `is_admin = true` yang bisa akses `/admin/*`
- User biasa (`is_admin = false`) akan ditolak oleh middleware di Folio

### 3.3 Admin Panel Authentication

Admin panel menggunakan Folio layout dengan middleware:

```php
// resources/views/pages/admin.blade.php — layout utama admin
// Semua halaman di folder admin/ akan inherit layout ini

// app/Providers/FolioServiceProvider.php
Folio::path(resource_path('views/pages'))
    ->middleware(['auth', 'verified', 'check.admin']);
```

Middleware `check.admin` akan memverifikasi `is_admin` sebelum mengizinkan akses ke halaman `/admin/*`.

---

## 4. Database Schema

### 4.1 Entity Relationship Diagram

```
┌──────────────┐       ┌──────────────────┐
│    users     │       │    products       │
│──────────────│       │──────────────────│
│ id           │       │ id               │
│ name         │       │ name             │
│ email        │       │ slug (UNIQUE)    │
│ password     │       │ description      │
│ is_admin     │◄──────│ is_active        │
│ ...          │  FK   │ created_at       │
└──────────────┘       │ updated_at       │
        │              └────────┬─────────┘
        │ FK                    │
        │              ┌────────▼─────────┐
        │              │subscription_plans│
        │              │──────────────────│
        │              │ id               │
        │              │ product_id (FK)  │
        │              │ name             │
        │              │ duration_days    │
        │              │ price            │
        │              │ is_active        │
        │              └────────┬─────────┘
        │                       │
        │              ┌────────▼─────────┐
        │              │    licenses       │
        │              │──────────────────│
        │              │ id               │
        │              │ product_id (FK)  │
        │              │ customer_name    │
        │              │ customer_email   │
        │              │ license_key (UQ) │
        │              │ status           │
        │              │ max_devices      │
        │              │ started_at       │
        │              │ expired_at       │
        │              │ activated_at     │
        │              │ notes            │
        │              └────┬────────┬────┘
        │                   │        │
        │            ┌──────▼──┐ ┌───▼──────────┐
        │            │ devices │ │ subscriptions │
        │            │─────────│ │───────────────│
        │            │ id      │ │ id            │
        │            │licenseid│ │ license_id FK │
        │            │device_id│ │ plan_id FK    │
        │            │dev_name │ │ status        │
        │            │ip_addr  │ │ starts_at     │
        │            │act_at   │ │ ends_at       │
        │            │seen_at  │ │ renewed_at    │
        │            │is_active│ │ notes         │
        │            └─────────┘ └───────────────┘
        │
        │              ┌──────────────────┐
        │              │activation_reqs   │
        │              │──────────────────│
        │              │ id               │
        │              │ license_id (FK)  │
        │              │ old_device_id    │
        │              │ new_device_id    │
        │              │ new_device_name  │
        │              │ ip_address       │
        │              │ status           │
        │              │ requested_at     │
        │              │ handled_at       │
        │◄─────────────│ handled_by (FK)  │
        │  FK          └──────────────────┘
        │
        │              ┌──────────────────┐
        │              │   audit_logs      │
        │              │──────────────────│
        │              │ id               │
        │◄─────────────│ user_id (FK)     │
        │  FK          │ license_id (FK)  │
        │              │ action           │
        │              │ payload (JSON)   │
        │              │ ip_address       │
        │              │ created_at       │
        └──────────────┴──────────────────┘
```

### 4.2 Table: products

| Column       | Type             | Constraints          | Notes                         |
|-------------|------------------|----------------------|-------------------------------|
| id          | bigint unsigned  | PK, AI              |                               |
| name        | varchar(255)     | NOT NULL             |                               |
| slug        | varchar(255)     | NOT NULL, UNIQUE     | Auto-generated from name      |
| description | text             | NULLABLE             |                               |
| is_active   | tinyint(1)       | NOT NULL, DEFAULT 1  |                               |
| created_at  | timestamp        | NULLABLE             |                               |
| updated_at  | timestamp        | NULLABLE             |                               |

**Indexes:**
- `PRIMARY` (`id`)
- `UNIQUE` (`slug`)

### 4.3 Table: subscription_plans

| Column        | Type             | Constraints          | Notes                         |
|---------------|------------------|----------------------|-------------------------------|
| id            | bigint unsigned  | PK, AI              |                               |
| product_id    | bigint unsigned  | NOT NULL, FK         | `REFERENCES products(id)`     |
| name          | varchar(255)     | NOT NULL             | e.g., "Monthly", "Yearly"     |
| duration_days | int              | NOT NULL             | 30, 365, dst.                 |
| price         | decimal(12,2)    | NOT NULL             |                               |
| is_active     | tinyint(1)       | NOT NULL, DEFAULT 1  |                               |
| created_at    | timestamp        | NULLABLE             |                               |
| updated_at    | timestamp        | NULLABLE             |                               |

**Indexes:**
- `PRIMARY` (`id`)
- `INDEX` (`product_id`)
- `FK` `product_id` → `products(id)` ON DELETE CASCADE

### 4.4 Table: licenses

| Column             | Type             | Constraints          | Notes                                |
|--------------------|------------------|----------------------|--------------------------------------|
| id                 | bigint unsigned  | PK, AI              |                                      |
| product_id         | bigint unsigned  | NOT NULL, FK         | `REFERENCES products(id)`            |
| customer_name      | varchar(255)     | NOT NULL             |                                      |
| customer_email     | varchar(255)     | NOT NULL             |                                      |
| license_key        | varchar(36)      | NOT NULL, UNIQUE     | Format: `LIC-XXXXXXXX-XXXXXXXX`      |
| status             | varchar(20)      | NOT NULL, DEFAULT 'active' | `active`, `suspended`, `revoked`, `expired` |
| max_devices        | int              | NOT NULL, DEFAULT 1  | Max device aktif bersamaan           |
| started_at         | date             | NOT NULL             |                                      |
| expired_at         | date             | NOT NULL             |                                      |
| activated_at       | datetime         | NULLABLE             | Diisi saat aktivasi pertama          |
| notes              | text             | NULLABLE             | Catatan internal admin               |
| created_at         | timestamp        | NULLABLE             |                                      |
| updated_at         | timestamp        | NULLABLE             |                                      |

**Indexes:**
- `PRIMARY` (`id`)
- `UNIQUE` (`license_key`)
- `INDEX` (`product_id`)
- `INDEX` (`status`)
- `INDEX` (`expired_at`)
- `INDEX` `status_expired` (`status`, `expired_at`) — composite untuk scheduler
- `FK` `product_id` → `products(id)`

### 4.5 Table: devices

| Column        | Type             | Constraints          | Notes                              |
|---------------|------------------|----------------------|------------------------------------|
| id            | bigint unsigned  | PK, AI              |                                    |
| license_id    | bigint unsigned  | NOT NULL, FK         | `REFERENCES licenses(id)`          |
| device_id     | varchar(255)     | NOT NULL             | Client-generated identifier        |
| device_name   | varchar(255)     | NOT NULL             | Human-readable ("Kasir Toko 1")    |
| ip_address    | varchar(45)      | NULLABLE             | IP terakhir terdeteksi             |
| activated_at  | datetime         | NOT NULL             | Waktu aktivasi                     |
| last_seen_at  | datetime         | NULLABLE             | Diupdate setiap validate           |
| is_active     | tinyint(1)       | NOT NULL, DEFAULT 1  | Soft deactivate                    |
| created_at    | timestamp        | NULLABLE             |                                    |
| updated_at    | timestamp        | NULLABLE             |                                    |

**Indexes:**
- `PRIMARY` (`id`)
- `UNIQUE` `device_per_license` (`license_id`, `device_id`)
- `INDEX` (`device_id`)
- `FK` `license_id` → `licenses(id)` ON DELETE CASCADE

### 4.6 Table: activation_requests

| Column          | Type             | Constraints          | Notes                              |
|-----------------|------------------|----------------------|------------------------------------|
| id              | bigint unsigned  | PK, AI              |                                    |
| license_id      | bigint unsigned  | NOT NULL, FK         | `REFERENCES licenses(id)`          |
| old_device_id   | varchar(255)     | NULLABLE             | Null jika first activation         |
| new_device_id   | varchar(255)     | NOT NULL             |                                    |
| new_device_name | varchar(255)     | NOT NULL             |                                    |
| ip_address      | varchar(45)      | NULLABLE             |                                    |
| status          | varchar(20)      | NOT NULL, DEFAULT 'pending' | `pending`, `approved`, `rejected` |
| requested_at    | datetime         | NOT NULL             |                                    |
| handled_at      | datetime         | NULLABLE             |                                    |
| handled_by      | bigint unsigned  | NULLABLE, FK         | `REFERENCES users(id)`             |
| created_at      | timestamp        | NULLABLE             |                                    |
| updated_at      | timestamp        | NULLABLE             |                                    |

**Indexes:**
- `PRIMARY` (`id`)
- `INDEX` (`license_id`)
- `INDEX` (`status`)
- `INDEX` (`handled_by`)
- `FK` `license_id` → `licenses(id)` ON DELETE CASCADE
- `FK` `handled_by` → `users(id)` ON DELETE SET NULL

### 4.7 Table: subscriptions

| Column     | Type             | Constraints          | Notes                              |
|------------|------------------|----------------------|------------------------------------|
| id         | bigint unsigned  | PK, AI              |                                    |
| license_id | bigint unsigned  | NOT NULL, FK         | `REFERENCES licenses(id)`          |
| plan_id    | bigint unsigned  | NOT NULL, FK         | `REFERENCES subscription_plans(id)`|
| status     | varchar(20)      | NOT NULL, DEFAULT 'active' | `active`, `cancelled`, `expired` |
| starts_at  | date             | NOT NULL             |                                    |
| ends_at    | date             | NOT NULL             |                                    |
| renewed_at | datetime         | NULLABLE             |                                    |
| notes      | text             | NULLABLE             |                                    |
| created_at | timestamp        | NULLABLE             |                                    |
| updated_at | timestamp        | NULLABLE             |                                    |

**Indexes:**
- `PRIMARY` (`id`)
- `INDEX` (`license_id`)
- `INDEX` (`plan_id`)
- `INDEX` (`status`)
- `FK` `license_id` → `licenses(id)` ON DELETE CASCADE
- `FK` `plan_id` → `subscription_plans(id)`

### 4.8 Table: audit_logs

| Column     | Type             | Constraints          | Notes                              |
|------------|------------------|----------------------|------------------------------------|
| id         | bigint unsigned  | PK, AI              |                                    |
| user_id    | bigint unsigned  | NULLABLE, FK         | `REFERENCES users(id)`             |
| license_id | bigint unsigned  | NULLABLE, FK         | `REFERENCES licenses(id)`          |
| action     | varchar(100)     | NOT NULL             | e.g., `license.created`            |
| payload    | json             | NULLABLE             | Contextual data                    |
| ip_address | varchar(45)      | NULLABLE             |                                    |
| created_at | timestamp        | NOT NULL             |                                    |

**Catatan:** Tabel ini **tidak memiliki** `updated_at` — append-only.

**Indexes:**
- `PRIMARY` (`id`)
- `INDEX` (`user_id`)
- `INDEX` (`license_id`)
- `INDEX` (`action`)
- `INDEX` (`created_at`)
- `FK` `user_id` → `users(id)` ON DELETE SET NULL
- `FK` `license_id` → `licenses(id)` ON DELETE SET NULL

### 4.9 Modification to users table

| Column    | Type             | Constraints          | Notes                              |
|-----------|------------------|----------------------|------------------------------------|
| is_admin  | tinyint(1)       | NOT NULL, DEFAULT 0  | Ditambahkan via migration baru     |

---

## 5. Models & Relationships

### 5.1 Enums

```php
enum LicenseStatus: string
{
    case Active = 'active';
    case Suspended = 'suspended';
    case Revoked = 'revoked';
    case Expired = 'expired';
}

enum ActivationRequestStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';
}

enum SubscriptionStatus: string
{
    case Active = 'active';
    case Cancelled = 'cancelled';
    case Expired = 'expired';
}

enum AuditAction: string
{
    case LicenseCreated = 'license.created';
    case LicenseActivated = 'license.activated';
    case LicenseValidated = 'license.validated';
    case LicenseRevoked = 'license.revoked';
    case LicenseSuspended = 'license.suspended';
    case LicenseExpired = 'license.expired';
    case DeviceBound = 'device.bound';
    case ActivationApproved = 'activation.approved';
    case ActivationRejected = 'activation.rejected';
    case ActivationRequested = 'activation.requested';
    case SubscriptionCreated = 'subscription.created';
    case SubscriptionRenewed = 'subscription.renewed';
    case DevicesForceReset = 'devices.force_reset';
}
```

### 5.2 Product

```php
class Product extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
        'is_active',
    ];

    public function subscriptionPlans(): HasMany
    {
        return $this->hasMany(SubscriptionPlan::class);
    }

    public function licenses(): HasMany
    {
        return $this->hasMany(License::class);
    }

    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }
}
```

### 5.3 SubscriptionPlan

```php
class SubscriptionPlan extends Model
{
    protected $fillable = [
        'product_id',
        'name',
        'duration_days',
        'price',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }
}
```

### 5.4 License

```php
class License extends Model
{
    protected $fillable = [
        'product_id',
        'customer_name',
        'customer_email',
        'license_key',
        'status',
        'max_devices',
        'started_at',
        'expired_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'date:Y-m-d',
            'expired_at' => 'date:Y-m-d',
            'activated_at' => 'datetime',
            'max_devices' => 'integer',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function devices(): HasMany
    {
        return $this->hasMany(Device::class);
    }

    public function activeDevices(): HasMany
    {
        return $this->hasMany(Device::class)->where('is_active', true);
    }

    public function activationRequests(): HasMany
    {
        return $this->hasMany(ActivationRequest::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function currentSubscription(): HasOne
    {
        return $this->hasOne(Subscription::class)
            ->where('status', 'active')
            ->latestOfMany();
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }

    // Scopes
    public function scopeActive(Builder $query): void
    {
        $query->where('status', LicenseStatus::Active);
    }

    public function scopeExpired(Builder $query): void
    {
        $query->where('status', LicenseStatus::Expired);
    }

    public function scopeSuspended(Builder $query): void
    {
        $query->where('status', LicenseStatus::Suspended);
    }

    public function scopeRevoked(Builder $query): void
    {
        $query->where('status', LicenseStatus::Revoked);
    }

    public function scopeExpiringSoon(Builder $query, int $days = 7): void
    {
        $query->where('status', LicenseStatus::Active)
            ->whereBetween('expired_at', [now(), now()->addDays($days)]);
    }

    public function scopeWhereKey(Builder $query, string $licenseKey): void
    {
        $query->where('license_key', $licenseKey);
    }

    // Helpers
    public function isActive(): bool
    {
        return $this->status === LicenseStatus::Active
            && $this->expired_at->isFuture();
    }

    public function isExpiringSoon(int $days = 7): bool
    {
        return $this->isActive()
            && $this->expired_at->isFuture()
            && $this->expired_at->lte(now()->addDays($days));
    }

    public function canActivateDevice(): bool
    {
        return $this->isActive()
            && $this->activeDevices()->count() < $this->max_devices;
    }

    public function isDeviceBound(string $deviceId): bool
    {
        return $this->devices()
            ->where('device_id', $deviceId)
            ->where('is_active', true)
            ->exists();
    }

    public function activeDeviceCount(): int
    {
        return $this->activeDevices()->count();
    }
}
```

### 5.5 Device

```php
class Device extends Model
{
    protected $fillable = [
        'license_id',
        'device_id',
        'device_name',
        'ip_address',
        'activated_at',
        'last_seen_at',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'activated_at' => 'datetime',
            'last_seen_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    public function license(): BelongsTo
    {
        return $this->belongsTo(License::class);
    }
}
```

### 5.6 ActivationRequest

```php
class ActivationRequest extends Model
{
    protected $fillable = [
        'license_id',
        'old_device_id',
        'new_device_id',
        'new_device_name',
        'ip_address',
        'status',
        'requested_at',
        'handled_at',
        'handled_by',
    ];

    protected function casts(): array
    {
        return [
            'requested_at' => 'datetime',
            'handled_at' => 'datetime',
        ];
    }

    public function license(): BelongsTo
    {
        return $this->belongsTo(License::class);
    }

    public function handledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handled_by');
    }

    public function scopePending(Builder $query): void
    {
        $query->where('status', ActivationRequestStatus::Pending);
    }

    public function approve(int $adminId): void
    {
        $this->update([
            'status' => ActivationRequestStatus::Approved,
            'handled_at' => now(),
            'handled_by' => $adminId,
        ]);

        // Deactivate old device if exists
        if ($this->old_device_id) {
            $this->license->devices()
                ->where('device_id', $this->old_device_id)
                ->update(['is_active' => false]);
        }

        // Bind new device
        $this->license->devices()->create([
            'device_id' => $this->new_device_id,
            'device_name' => $this->new_device_name,
            'activated_at' => now(),
            'last_seen_at' => now(),
        ]);
    }

    public function reject(int $adminId): void
    {
        $this->update([
            'status' => ActivationRequestStatus::Rejected,
            'handled_at' => now(),
            'handled_by' => $adminId,
        ]);
    }
}
```

### 5.7 Subscription

```php
class Subscription extends Model
{
    protected $fillable = [
        'license_id',
        'plan_id',
        'status',
        'starts_at',
        'ends_at',
        'renewed_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'date:Y-m-d',
            'ends_at' => 'date:Y-m-d',
            'renewed_at' => 'datetime',
        ];
    }

    public function license(): BelongsTo
    {
        return $this->belongsTo(License::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class);
    }
}
```

### 5.8 AuditLog

```php
class AuditLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'license_id',
        'action',
        'payload',
        'ip_address',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function license(): BelongsTo
    {
        return $this->belongsTo(License::class);
    }

    // Helper untuk logging
    public static function log(
        string $action,
        ?array $payload = null,
        ?License $license = null,
        ?User $user = null,
        ?string $ipAddress = null,
    ): self {
        return static::create([
            'action' => $action,
            'payload' => $payload,
            'license_id' => $license?->id,
            'user_id' => $user?->id,
            'ip_address' => $ipAddress ?? request()->ip(),
            'created_at' => now(),
        ]);
    }
}
```

### 5.9 User (modified)

```php
// app/Models/User.php — tambahkan:
class User extends Authenticatable
{
    // ... existing code ...

    protected $fillable = [
        'name',
        'email',
        'password',
        'is_admin',  // ADD
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',  // ADD
        ];
    }

    // ADD
    public function isAdmin(): bool
    {
        return $this->is_admin;
    }

    public function handledRequests(): HasMany
    {
        return $this->hasMany(ActivationRequest::class, 'handled_by');
    }
}
```

---

## 6. API Contract

### 6.1 Base Configuration

- **Base URL:** `https://monitor.test/api`
- **Content-Type:** `application/json`
- **Format:** All responses use JSON
- **Charset:** UTF-8
- **HTTP Method:** POST (semua public API)
- **Rate Limit Headers:** `X-RateLimit-Limit`, `X-RateLimit-Remaining`, `X-RateLimit-Reset`
- **Timeouts:** Server response < 300ms target, client timeout 10s

### 6.2 Endpoint: Validate License

`POST /api/license/validate`

Validates a license key against a device. This is the core endpoint called periodically by client apps.

**Request:**

```json
{
  "license_key": "LIC-A1B2-C3D4",
  "device_id": "a1b2c3d4-e5f6-7890-abcd-ef1234567890",
  "app_version": "1.0.0"
}
```

**Field Validation:**

| Field         | Type   | Required | Rules                                      |
|---------------|--------|----------|--------------------------------------------|
| license_key   | string | Yes      | Format: `/^LIC-[A-Z0-9]{4}-[A-Z0-9]{4}$/` |
| device_id     | string | Yes      | Min: 1, Max: 255                           |
| app_version   | string | No       | Semantic version (optional)                |

**Response Success (HTTP 200):**

```json
{
  "valid": true,
  "status": "active",
  "expired_at": "2026-12-01",
  "cache_until": "2026-05-21",
  "cache_ttl_seconds": 604800,
  "server_time": "2026-05-14T10:30:00Z",
  "message": "License valid"
}
```

**Response Failed — Expired (HTTP 200):**

```json
{
  "valid": false,
  "status": "expired",
  "cache_until": null,
  "cache_ttl_seconds": 0,
  "server_time": "2026-05-14T10:30:00Z",
  "message": "License has expired. Please renew your subscription."
}
```

**Response Failed — Revoked (HTTP 200):**

```json
{
  "valid": false,
  "status": "revoked",
  "cache_until": null,
  "cache_ttl_seconds": 0,
  "server_time": "2026-05-14T10:30:00Z",
  "message": "License has been revoked. Contact support."
}
```

**Response Failed — Suspended (HTTP 200):**

```json
{
  "valid": false,
  "status": "suspended",
  "cache_until": null,
  "cache_ttl_seconds": 0,
  "server_time": "2026-05-14T10:30:00Z",
  "message": "License is suspended. Contact support."
}
```

**Response Failed — Device Mismatch (HTTP 200):**

```json
{
  "valid": false,
  "status": "device_mismatch",
  "cache_until": null,
  "cache_ttl_seconds": 0,
  "server_time": "2026-05-14T10:30:00Z",
  "message": "Device not registered with this license. Please activate first."
}
```

**Response Error — Invalid Key (HTTP 422):**

```json
{
  "message": "The license key format is invalid.",
  "errors": {
    "license_key": [
      "Format license key harus LIC-XXXX-XXXX"
    ]
  }
}
```

**Response Error — Rate Limited (HTTP 429):**

```json
{
  "message": "Too many requests. Please try again later.",
  "retry_after_seconds": 60
}
```

### 6.3 Endpoint: Activate License

`POST /api/license/activate`

Activates a license key on a specific device. Called during initial setup or after device migration approval.

**Request:**

```json
{
  "license_key": "LIC-A1B2-C3D4",
  "device_id": "a1b2c3d4-e5f6-7890-abcd-ef1234567890",
  "device_name": "Kasir Toko 1"
}
```

**Field Validation:**

| Field       | Type   | Required | Rules                                      |
|-------------|--------|----------|--------------------------------------------|
| license_key | string | Yes      | Format: `/^LIC-[A-Z0-9]{4}-[A-Z0-9]{4}$/` |
| device_id   | string | Yes      | Min: 1, Max: 255                           |
| device_name | string | Yes      | Min: 1, Max: 255                           |

**Response Success — First Activation (HTTP 200):**

```json
{
  "success": true,
  "status": "active",
  "message": "Device activated successfully",
  "expired_at": "2026-12-01"
}
```

**Response Success — Same Device (idempotent) (HTTP 200):**

```json
{
  "success": true,
  "status": "active",
  "message": "Device already activated",
  "expired_at": "2026-12-01"
}
```

**Response Success — New Device (under max_devices) (HTTP 200):**

```json
{
  "success": true,
  "status": "active",
  "message": "New device activated",
  "expired_at": "2026-12-01"
}
```

**Response Pending Approval (HTTP 200):**

```json
{
  "success": false,
  "status": "pending_approval",
  "message": "Device limit reached. Activation request sent to admin.",
  "activation_request_id": 5
}
```

**Response Failed — Expired (HTTP 200):**

```json
{
  "success": false,
  "status": "expired",
  "message": "License has expired. Renewal required."
}
```

**Response Failed — Revoked/Suspended (HTTP 200):**

```json
{
  "success": false,
  "status": "revoked",
  "message": "License is revoked. Contact support."
}
```

**Response Error — Validation Failed (HTTP 422):**

```json
{
  "message": "Validation failed.",
  "errors": {
    "device_name": [
      "Device name is required"
    ]
  }
}
```

**Response Error — Rate Limited (HTTP 429):**

```json
{
  "message": "Too many activation attempts. Try again later.",
  "retry_after_seconds": 120
}
```

### 6.4 Endpoint: Check Update

`POST /api/license/check-update`

Checks if a newer version of the client application is available.

**Request:**

```json
{
  "license_key": "LIC-A1B2-C3D4",
  "current_version": "1.0.0"
}
```

**Response — No Update (HTTP 200):**

```json
{
  "update_available": false,
  "latest_version": "1.0.0",
  "download_url": null,
  "message": "You are using the latest version",
  "release_notes": null
}
```

**Response — Update Available (HTTP 200):**

```json
{
  "update_available": true,
  "latest_version": "1.2.0",
  "download_url": "https://dl.monitor.test/apps/pos-v1.2.0.zip",
  "message": "New version available",
  "release_notes": "- Bug fixes\n- Performance improvements"
}
```

### 6.5 Error Code Reference

| HTTP Status | Code              | Description                        | Client Action                     |
|-------------|-------------------|------------------------------------|-----------------------------------|
| 200         | `expired`         | License expired                    | Tampilkan pesan, block fitur      |
| 200         | `revoked`         | License revoked                    | Force logout, block app           |
| 200         | `suspended`       | License suspended                  | Tampilkan pesan, block app        |
| 200         | `device_mismatch` | Device not registered              | Arahkan ke aktivasi               |
| 200         | `pending_approval`| Menunggu approval admin            | Tampilkan pesan tunggu            |
| 422         | `validation_error`| Input tidak valid                  | Perbaiki input                    |
| 429         | `rate_limited`    | Too many requests                  | Tunggu sebelum retry              |
| 500         | `server_error`    | Internal server error              | Fallback ke cache, retry later    |

### 6.6 Rate Limiting Configuration

```php
// bootstrap/app.php
->withRouting(
    api: __DIR__.'/../routes/api.php',
    // ...
)
->withMiddleware(function (Middleware $middleware) {
    $middleware->api(prepend: [
        \Illuminate\Routing\Middleware\ThrottleRequests::class.':60,1',
    ]);

    $middleware->throttleApi('60,1');
})

// app/Providers/AppServiceProvider.php
RateLimiter::for('license:validate', function (Request $request) {
    return Limit::perMinute(60)->by($request->ip());
});

RateLimiter::for('license:activate', function (Request $request) {
    return Limit::perMinute(30)->by($request->ip());
});

RateLimiter::for('license:update', function (Request $request) {
    return Limit::perMinute(30)->by($request->ip());
});
```

---

## 7. Business Logic & Workflow

### 7.1 Service Layer

#### LicenseKeyService

```php
class LicenseKeyService
{
    /**
     * Generate license key in format: LIC-XXXXXXXX-XXXXXXXX
     * 16 alphanumeric characters, split into 2 groups of 8.
     */
    public function generate(): string
    {
        $prefix = 'LIC';
        $segment1 = strtoupper(Str::random(8));
        $segment2 = strtoupper(Str::random(8));

        return "{$prefix}-{$segment1}-{$segment2}";
    }

    /**
     * Validate license key format.
     */
    public function validateFormat(string $key): bool
    {
        return (bool) preg_match('/^LIC-[A-Z0-9]{8}-[A-Z0-9]{8}$/', $key);
    }
}
```

#### LicenseService

```php
class LicenseService
{
    public function __construct(
        private LicenseKeyService $licenseKeyService,
    ) {}

    /**
     * Validate a license for a given device.
     */
    public function validate(
        string $licenseKey,
        string $deviceId,
        ?string $appVersion = null,
    ): array {
        $license = License::query()
            ->where('license_key', $licenseKey)
            ->first();

        if (! $license) {
            return $this->invalidResponse('license_not_found', 'License key not found');
        }

        // Check status
        if ($license->status === LicenseStatus::Revoked) {
            return $this->invalidResponse('revoked', 'License has been revoked');
        }

        if ($license->status === LicenseStatus::Suspended) {
            return $this->invalidResponse('suspended', 'License is suspended');
        }

        if ($license->status === LicenseStatus::Expired || $license->expired_at->isPast()) {
            return $this->invalidResponse('expired', 'License has expired');
        }

        // Check device binding
        if (! $license->isDeviceBound($deviceId)) {
            return $this->invalidResponse(
                'device_mismatch',
                'Device is not registered with this license'
            );
        }

        // Update last_seen
        $license->devices()
            ->where('device_id', $deviceId)
            ->update([
                'last_seen_at' => now(),
                'ip_address' => request()->ip(),
            ]);

        // Calculate cache TTL
        $cacheUntil = now()->addDays(7);
        $cacheTtl = now()->diffInSeconds($cacheUntil);

        // Log audit
        AuditLog::log(
            action: AuditAction::LicenseValidated->value,
            payload: ['device_id' => $deviceId, 'app_version' => $appVersion],
            license: $license,
            ipAddress: request()->ip(),
        );

        return [
            'valid' => true,
            'status' => LicenseStatus::Active->value,
            'expired_at' => $license->expired_at->format('Y-m-d'),
            'cache_until' => $cacheUntil->format('Y-m-d'),
            'cache_ttl_seconds' => $cacheTtl,
            'server_time' => now()->toIso8601String(),
            'message' => 'License valid',
        ];
    }

    /**
     * Activate a license for a given device.
     */
    public function activate(
        string $licenseKey,
        string $deviceId,
        string $deviceName,
    ): array {
        $license = License::query()
            ->where('license_key', $licenseKey)
            ->first();

        if (! $license) {
            return [
                'success' => false,
                'status' => 'not_found',
                'message' => 'License key not found',
            ];
        }

        // Check license is active
        if (! $license->isActive()) {
            $status = $license->status === LicenseStatus::Expired || $license->expired_at->isPast()
                ? 'expired'
                : $license->status;

            return [
                'success' => false,
                'status' => $status,
                'message' => match ($status) {
                    'revoked' => 'License is revoked',
                    'suspended' => 'License is suspended',
                    'expired' => 'License has expired. Renewal required.',
                    default => 'License is not active',
                },
            ];
        }

        // Check if device already bound
        $existingDevice = $license->devices()
            ->where('device_id', $deviceId)
            ->first();

        if ($existingDevice) {
            if ($existingDevice->is_active) {
                // Idempotent — same device
                $existingDevice->update([
                    'last_seen_at' => now(),
                    'ip_address' => request()->ip(),
                ]);

                AuditLog::log(
                    action: AuditAction::LicenseActivated->value,
                    payload: ['device_id' => $deviceId, 'device_name' => $deviceName],
                    license: $license,
                    ipAddress: request()->ip(),
                );

                return [
                    'success' => true,
                    'status' => 'active',
                    'message' => 'Device already activated',
                    'expired_at' => $license->expired_at->format('Y-m-d'),
                ];
            }

            // Reactivate soft-deactivated device
            $existingDevice->update([
                'is_active' => true,
                'last_seen_at' => now(),
                'ip_address' => request()->ip(),
            ]);

            return [
                'success' => true,
                'status' => 'active',
                'message' => 'Device reactivated',
                'expired_at' => $license->expired_at->format('Y-m-d'),
            ];
        }

        // Check device limit
        if ($license->canActivateDevice()) {
            $license->devices()->create([
                'device_id' => $deviceId,
                'device_name' => $deviceName,
                'ip_address' => request()->ip(),
                'activated_at' => now(),
                'last_seen_at' => now(),
            ]);

            if (! $license->activated_at) {
                $license->update(['activated_at' => now()]);
            }

            AuditLog::log(
                action: AuditAction::DeviceBound->value,
                payload: ['device_id' => $deviceId, 'device_name' => $deviceName],
                license: $license,
                ipAddress: request()->ip(),
            );

            return [
                'success' => true,
                'status' => 'active',
                'message' => 'Device activated successfully',
                'expired_at' => $license->expired_at->format('Y-m-d'),
            ];
        }

        // Device limit reached — create activation request
        $existingDeviceIds = $license->activeDevices()
            ->pluck('device_id')
            ->toArray();

        $request = $license->activationRequests()->create([
            'old_device_id' => $existingDeviceIds[0] ?? null,
            'new_device_id' => $deviceId,
            'new_device_name' => $deviceName,
            'ip_address' => request()->ip(),
            'status' => ActivationRequestStatus::Pending,
            'requested_at' => now(),
        ]);

        AuditLog::log(
            action: AuditAction::ActivationRequested->value,
            payload: [
                'activation_request_id' => $request->id,
                'old_device_id' => $request->old_device_id,
                'new_device_id' => $deviceId,
            ],
            license: $license,
            ipAddress: request()->ip(),
        );

        return [
            'success' => false,
            'status' => 'pending_approval',
            'message' => 'Device limit reached. Activation request sent to admin.',
            'activation_request_id' => $request->id,
        ];
    }

    /**
     * Check for app updates.
     */
    public function checkUpdate(
        string $licenseKey,
        string $currentVersion,
    ): array {
        // Verify license exists and is active (silent check)
        $license = License::query()
            ->where('license_key', $licenseKey)
            ->where('status', LicenseStatus::Active)
            ->where('expired_at', '>', now())
            ->exists();

        if (! $license) {
            return [
                'update_available' => false,
                'latest_version' => $currentVersion,
                'download_url' => null,
                'message' => 'Unable to check updates',
                'release_notes' => null,
            ];
        }

        // TODO: Implement version checking against app_version table
        // For now, returns no update
        return [
            'update_available' => false,
            'latest_version' => $currentVersion,
            'download_url' => null,
            'message' => 'You are using the latest version',
            'release_notes' => null,
        ];
    }

    private function invalidResponse(string $status, string $message): array
    {
        return [
            'valid' => false,
            'status' => $status,
            'cache_until' => null,
            'cache_ttl_seconds' => 0,
            'server_time' => now()->toIso8601String(),
            'message' => $message,
        ];
    }
}
```

### 7.2 Activation Flow (Detailed)

```
CLIENT                                   SERVER
  │                                        │
  │  POST /api/license/activate            │
  │  { license_key, device_id, name }      │
  │───────────────────────────────────────▶│
  │                                        │
  │               [Rate Limiter Check]     │
  │               [Form Validation]        │
  │               [Service: LicenseService→activate()]
  │                                        │
  │               ┌─── License exists? ───┐
  │               │   NO                  │ YES
  │               │                      │
  │               │ NOT_FOUND            │
  │               │                      ├── License active & not expired?
  │               │                      │   NO → REVOKED/SUSPENDED/EXPIRED
  │               │                      │   YES
  │               │                      │
  │               │                      ├── Device already bound?
  │               │                      │   YES (active) → SUCCESS (idempotent)
  │               │                      │   YES (inactive) → REACTIVATED
  │               │                      │   NO
  │               │                      │
  │               │                      ├── Device count < max_devices?
  │               │                      │   YES → BIND DEVICE → SUCCESS
  │               │                      │   NO
  │               │                      │
  │               │                      └──→ CREATE ACTIVATION REQUEST
  │               │                           (status: pending)
  │               │                           → PENDING_APPROVAL
  │               │
  │  ◀──────────────────────────────────────┐
  │  { success, status, message }           │
  │                                        │
```

### 7.3 Validation Flow (Detailed)

```
CLIENT                                   SERVER
  │                                        │
  │  POST /api/license/validate            │
  │  { license_key, device_id, version }   │
  │───────────────────────────────────────▶│
  │                                        │
  │               [Rate Limiter Check]     │
  │               [Form Validation]        │
  │               [Service: LicenseService→validate()]
  │                                        │
  │               ┌─── License found? ────┐
  │               │   NO                  │ YES
  │               │                      │
  │               │ NOT_FOUND            │
  │               │                      ├── Status check:
  │               │                      │   REVOKED → INVALID
  │               │                      │   SUSPENDED → INVALID
  │               │                      │   EXPIRED → INVALID
  │               │                      │   ACTIVE
  │               │                      │
  │               │                      ├── Device bound?
  │               │                      │   NO → DEVICE_MISMATCH
  │               │                      │   YES
  │               │                      │
  │               │                      ├── Update last_seen_at
  │               │                      ├── Calculate cache_until
  │               │                      └── Log audit
  │               │                      │
  │               │                      └──→ SUCCESS {valid: true}
  │               │
  │  ◀──────────────────────────────────────┐
  │  { valid, status, cache_until, ... }    │
  │                                        │
```

### 7.4 Subscription Flow (Manual Billing)

```
ADMIN (via Livewire Panel)             SYSTEM
  │                                        │
  │  Create License                        │
  │  → Select product                     │
  │  → Fill customer data                 │
  │  → Select subscription plan           │
  │  → Set started_at                     │
  │──────────────────────────────────────▶│
  │                                        │
  │               Generate license_key     │
  │               Set expired_at from plan │
  │               Create License record    │
  │               Create Subscription rec  │
  │               Log audit                │
  │                                        │
  │  ◀── License created successfully      │
  │                                        │
  │  [Payment received from customer]      │
  │                                        │
  │  Renew Subscription                    │
  │  → Select license                     │
  │  → Select plan (same or upgrade)      │
  │  → Set new start/end dates            │
  │──────────────────────────────────────▶│
  │                                        │
  │               Set current sub expired  │
  │               Create new Subscription  │
  │               Update license expired_at│
  │               Log audit                │
  │                                        │
  │  ◀── License renewed successfully      │
  │                                        │
```

### 7.5 Cache Strategy

**Cache Calculation:**

```php
$cacheUntil = now()->addDays(7); // Always 7 days from now
$cacheTtl = now()->diffInSeconds($cacheUntil);
```

**Client Behavior:**
1. Store `cache_until` locally after each successful validation
2. Before each request, check if `now() < cache_until`
3. If cache valid → skip validation, allow access
4. If cache expired → call validation API
5. If server unreachable AND cache not yet expired → allow (grace period)
6. If server unreachable AND cache expired → block access

**Cache File Format (client-side):**

```json
{
  "license_key": "LIC-A1B2-C3D4",
  "device_id": "uuid-here",
  "status": "active",
  "expired_at": "2026-12-01",
  "cache_until": "2026-05-21",
  "last_validated": "2026-05-14T10:30:00Z"
}
```

**No Server-Side Caching:**
- Validation API always queries the database
- No Redis/memcached for validation responses
- Keeps logic simple and data always fresh
- 7-day cache is client's responsibility

---

## 8. Client Lifecycle Flow

### 8.1 Complete Lifecycle

```
INSTALL APPLICATION
    │
    ▼
┌─────────────────────────────────────────────────────────────┐
│                 INITIAL ACTIVATION                          │
│                                                             │
│  App starts → check local cache exists?                     │
│  ├── YES → skip to VALIDATION LOOP                          │
│  └── NO                                                      │
│       → Show activation form                                │
│       → User enters license_key                             │
│       → Generate device_id (UUID)                           │
│       → POST /api/license/activate                          │
│                                                             │
│  Response:                                                  │
│  ├── success: true                                          │
│  │   → Save license_key + device_id locally                 │
│  │   → Create local cache (valid for 7 days)                │
│  │   → Redirect to app                                      │
│  │                                                          │
│  ├── pending_approval                                       │
│  │   → Show "Menunggu persetujuan admin"                    │
│  │   → Poll activation_request status periodically          │
│  │   → When approved → retry activation → success           │
│  │                                                          │
│  ├── expired/revoked/suspended                              │
│  │   → Show error message                                   │
│  │   → Block access permanently until resolved              │
│  │                                                          │
│  └── network_error                                          │
│      → Show "Gagal terhubung ke server"                     │
│      → Retry button                                         │
│                                                             │
└─────────────────────────────────────────────────────────────┘
    │
    ▼
┌─────────────────────────────────────────────────────────────┐
│                 DAILY VALIDATION LOOP                       │
│                                                             │
│  Every request goes through middleware:                     │
│                                                             │
│  ┌──────────────────────────────────────────────┐          │
│  │  MIDDLEWARE CHECK                              │          │
│  │                                                │          │
│  │  Is there a local cache file?                  │          │
│  │  ├── NO → redirect to activation               │          │
│  │  │                                                │          │
│  │  Is cache_until > now()?                       │          │
│  │  ├── YES → allow request (skip validation)     │          │
│  │  └── NO                                         │          │
│  │       → POST /api/license/validate             │          │
│  │                                                │          │
│  │       Response:                                │          │
│  │       ├── valid: true                          │          │
│  │       │   → Update local cache                 │          │
│  │       │   → Allow request                      │          │
│  │       │                                                │          │
│  │       ├── valid: false, status: expired        │          │
│  │       │   → Clear cache                        │          │
│  │       │   → Show "License expired"             │          │
│  │       │   → Block access                       │          │
│  │       │                                                │          │
│  │       ├── valid: false, status: revoked        │          │
│  │       │   → Clear cache                        │          │
│  │       │   → Force logout                       │          │
│  │       │   → Show "License revoked"             │          │
│  │       │                                                │          │
│  │       └── network_error                        │          │
│  │           → Cache still valid?                  │          │
│  │             ├── YES → allow (grace period)      │          │
│  │             └── NO → block "Tidak ada koneksi"  │          │
│  └──────────────────────────────────────────────┘          │
│                                                             │
└─────────────────────────────────────────────────────────────┘
    │
    ▼
┌─────────────────────────────────────────────────────────────┐
│                 DEVICE MIGRATION                            │
│                                                             │
│  User installs app on new device:                           │
│  → Enter license_key                                        │
│  → POST /api/license/activate                              │
│  → Server detects: new device, max_devices reached          │
│  → Returns: pending_approval                                │
│  → Show "Menunggu approval admin" screen                   │
│                                                             │
│  Meanwhile, admin receives notification:                    │
│  → Opens admin panel → Activation Requests                     │
│  → Reviews request (old device info, new device info)       │
│  → Clicks Approve                                           │
│                                                             │
│  Client polls status (or user clicks "Cek Status"):        │
│  → POST /api/license/activate again                        │
│  → Server checks: is there an approved request?             │
│  → YES → bind device → success                             │
│  → NO → still pending                                       │
│                                                             │
└─────────────────────────────────────────────────────────────┘
    │
    ▼
┌─────────────────────────────────────────────────────────────┐
│                 EXPIRY & RENEWAL                            │
│                                                             │
│  Daily, server runs: licenses:check-expired                 │
│  → Marks expired licenses                                   │
│                                                             │
│  Client next validation:                                    │
│  → POST /api/license/validate                              │
│  → Response: valid: false, status: expired                  │
│  → Client shows "License expired" screen                    │
│  → Provides contact info for renewal                        │
│                                                             │
│  Admin renews manually via Livewire panel:                        │
│  → Creates new subscription                                 │
│  → Updates license expired_at                               │
│                                                             │
│  Client retries validation:                                 │
│  → POST /api/license/validate                              │
│  → valid: true → app resumes                                │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

---

## 9. Failure Handling

### 9.1 Error Matrix

| Kondisi                          | Deteksi                     | Response Server              | Client Action                              |
|----------------------------------|-----------------------------|------------------------------|--------------------------------------------|
| License key not found            | DB lookup                   | `valid: false, not_found`    | Tampilkan "Lisensi tidak dikenal"          |
| License expired                  | `expired_at` < today        | `valid: false, expired`      | Tampilkan masa aktif habis, blokir akses   |
| License revoked                  | `status = revoked`          | `valid: false, revoked`      | Force logout, blokir akses                 |
| License suspended                | `status = suspended`        | `valid: false, suspended`    | Tampilkan pesan, blokir akses              |
| Device mismatch                  | `device_id` not in devices  | `valid: false, mismatch`     | Arahkan ke aktivasi ulang                  |
| Device limit reached             | count >= max_devices        | `pending_approval`           | Tampilkan "menunggu approval"              |
| Invalid license key format       | Regex validation            | HTTP 422                     | Tampilkan error format key                 |
| Rate limit exceeded              | Throttle middleware          | HTTP 429                     | Tunggu, retry dengan backoff               |
| Server unreachable               | Connection timeout          | N/A                          | Gunakan cache jika valid, blokir jika tidak|
| Server error (500)               | Exception                   | HTTP 500                     | Fallback ke cache, retry later             |
| Database connection failure      | DB exception                | HTTP 500                     | Fallback ke cache, retry later             |

### 9.2 Client-Side Failure Handling

```php
// Pseudocode untuk client-side LicenseService

class LicenseService
{
    private string $cachePath;
    private string $serverUrl;

    public function isLicenseValid(): bool
    {
        $cache = $this->readCache();

        if (! $cache) {
            return false; // Belum pernah aktivasi
        }

        // Check local cache TTL
        if (now() < $cache['cache_until']) {
            return true; // Cache masih valid, skip API call
        }

        // Cache expired — try validation API
        try {
            $response = Http::timeout(10)
                ->post("{$this->serverUrl}/api/license/validate", [
                    'license_key' => $cache['license_key'],
                    'device_id' => $cache['device_id'],
                ]);

            if ($response->successful()) {
                $data = $response->json();

                if ($data['valid']) {
                    $this->updateCache($data);
                    return true;
                }

                // License invalid — clear cache
                $this->clearCache();
                return false;
            }

            // HTTP error — fallback to cache
            // Check if expired_at is still in the future
            if ($cache['expired_at'] > now()) {
                return true; // Grace period, allow access
            }

            return false;

        } catch (ConnectionException $e) {
            // Server unreachable
            // Check if expired_at is still in the future
            if ($cache['expired_at'] > now()) {
                return true; // Allow with existing cache
            }

            return false; // License actually expired, block
        }
    }
}
```

### 9.3 Client-Side Error Display

| Screen          | Trigger                    | Message                               | Action Available          |
|-----------------|----------------------------|---------------------------------------|---------------------------|
| Activation Form | First run / cache cleared  | "Masukkan license key Anda"           | Input field + Activate    |
| License Expired | `valid: false, expired`    | "Lisensi telah habis. Hubungi admin." | Contact info              |
| License Revoked | `valid: false, revoked`    | "Lisensi dicabut. Hubungi support."   | Force logout              |
| License Suspended| `valid: false, suspended` | "Lisensi ditangguhkan."               | Contact info              |
| Pending Approval| `pending_approval`         | "Menunggu persetujuan admin."         | Cek Status button         |
| No Connection   | Network error + cache expired | "Tidak dapat terhubung ke server." | Retry button              |
| Invalid Key     | HTTP 422                   | "Format license key salah."           | Correct input             |

### 9.4 Admin Notification on Failure

Saat ada activation request baru:
- Muncul di admin dashboard sebagai widget
- Admin melihat jumlah pending requests
- Bisa approve/reject langsung dari panel

(Future: email notification ke admin saat ada request baru)

---

## 10. Security Rules

### 10.1 Trust Model

```
┌─────────────────────────────────────────────────────┐
│                   TRUST BOUNDARY                     │
│                                                      │
│  TRUSTED (Server)           UNTRUSTED (Client)       │
│  ┌─────────────────┐       ┌──────────────────┐     │
│  │ Database         │       │ Client App       │     │
│  │ LicenseService   │       │ License Key      │     │
│  │ Admin Panel      │       │ Device ID        │     │
│  │ Audit Logs       │       │ App Version      │     │
│  │ Scheduler        │       │ Local Cache      │     │
│  └─────────────────┘       └──────────────────┘     │
│                                                      │
│  RULES:                                              │
│  1. Server is ALWAYS the source of truth             │
│  2. Client data is NEVER trusted without validation  │
│  3. All license decisions happen on server           │
│  4. Client cache is for convenience, not security    │
│  5. Rate limiting protects server from abuse         │
│  6. Every validation is logged                       │
└─────────────────────────────────────────────────────┘
```

### 10.2 Security Rules (Explicit)

| #  | Rule                                                  | Rationale                                    |
|----|-------------------------------------------------------|----------------------------------------------|
| 1  | Server is the single source of truth for licensing    | Client can be modified/hacked                |
| 2  | Never trust client-reported status/expiry             | Client can lie to bypass validation          |
| 3  | Validate all input on server (format, length, type)   | Prevent injection, malformed data            |
| 4  | Rate limit all public API endpoints per IP            | Prevent brute force, DoS                     |
| 5  | Log every license validation and activation           | Audit trail for abuse investigation          |
| 6  | Return 200 with JSON body for all business responses  | Don't leak internal status via HTTP codes    |
| 7  | Never expose database errors or stack traces          | Information disclosure prevention            |
| 8  | License key format must be validated server-side      | Prevent invalid data in queries              |
| 9  | Device binding is enforced server-side                | Client cannot self-register devices          |
| 10 | Admin panel protected by session auth + is_admin gate | Prevent unauthorized access                  |
| 11 | All admin actions are logged with user identity       | Accountability                               |
| 12 | Scheduler commands run with minimal permissions       | Principle of least privilege                 |

### 10.3 What We Are NOT Doing (Phase 1)

- **No JWT/PASETO** — not needed, API is validated by license key
- **No request signing** — not needed for Phase 1, future option
- **No client-side encryption** — server is trusted, client is not
- **No hardware fingerprinting** — simple UUID from client is sufficient
- **No anti-tamper** — out of scope, client obfuscation is future work
- **No OAuth/SSO** — simple admin auth with Fortify is sufficient

### 10.4 Rate Limiting Configuration

```php
// app/Providers/AppServiceProvider.php

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

public function boot(): void
{
    RateLimiter::for('license:validate', function (Request $request) {
        return Limit::perMinute(60)->by($request->ip());
    });

    RateLimiter::for('license:activate', function (Request $request) {
        return Limit::perMinute(30)->by($request->ip());
    });

    RateLimiter::for('license:update', function (Request $request) {
        return Limit::perMinute(30)->by($request->ip());
    });

    // Per-license-key throttling (prevent abuse per license)
    RateLimiter::for('license:per-key', function (Request $request) {
        $key = $request->input('license_key', 'unknown');
        return Limit::perMinute(300)->by("license-key:{$key}");
    });
}
```

---

## 11. Admin Panel

### 11.1 Technology Stack

Sudah terinstall dari Fortify (Livewire stack):
- **Laravel Fortify** — authentication scaffold (login, register, password reset)
- **Livewire v3** — reactive UI components
- **Volt v1** — single-file Livewire components (functional API)
- **Laravel Folio** — file-based routing untuk halaman admin
- **Tailwind CSS** — styling
- **Alpine.js** — client-side interactivity

### 11.2 Folder Structure

```
resources/views/
├── components/          # Shared UI components (already from Fortify)
│   ├── admin-layout.blade.php    # Admin layout (extends Fortify)
│   ├── admin-nav.blade.php       # Admin sidebar navigation
│   └── ...
│
├── layouts/
│   └── admin.blade.php           # Admin layout wrapper
│
└── pages/
    ├── admin/                    # Folio route: /admin
    │   ├── index.blade.php       # Dashboard (Volt)
    │   ├── products/
    │   │   ├── index.blade.php   # List products (Volt)
    │   │   ├── create.blade.php  # Create product (Volt)
    │   │   └── [product]/
    │   │       └── edit.blade.php    # Edit product (Volt)
    │   ├── plans/
    │   │   ├── index.blade.php   # List plans (Volt)
    │   │   ├── create.blade.php  # Create plan (Volt)
    │   │   └── [plan]/
    │   │       └── edit.blade.php    # Edit plan (Volt)
    │   ├── licenses/
    │   │   ├── index.blade.php   # List licenses (Volt)
    │   │   ├── create.blade.php  # Create license (Volt)
    │   │   └── [license]/
    │   │       └── edit.blade.php    # Edit license (Volt)
    │   ├── devices/
    │   │   └── index.blade.php   # List devices (Volt, read-only)
    │   ├── activation-requests/
    │   │   └── index.blade.php   # List + approve/reject (Volt)
    │   └── audit-logs/
    │       └── index.blade.php   # List audit logs (Volt, read-only)
    │
    └── ... (existing Fortify pages: dashboard, profile, etc.)
```

### 11.3 Custom Middleware: CheckAdmin

```php
// app/Http/Middleware/CheckAdminMiddleware.php

class CheckAdminMiddleware
{
    public function handle(Request $request, Closure $next): mixed
    {
        if (! $request->user()?->isAdmin()) {
            abort(403, 'Unauthorized access. Admin only.');
        }

        return $next($request);
    }
}
```

Registered via `bootstrap/app.php`:

```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->alias([
        'check.admin' => \App\Http\Middleware\CheckAdminMiddleware::class,
    ]);
})
```

### 11.4 Folio Routing Configuration

```php
// app/Providers/FolioServiceProvider.php

Folio::path(resource_path('views/pages'))
    ->middleware(['auth', 'verified', 'check.admin']);
```

### 11.5 Admin Layout

```blade
{{-- resources/views/layouts/admin.blade.php --}}
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ $header ?? 'Admin Panel' }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    {{ $slot }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
```

### 11.6 Modules (Volt Components)

#### a) Dashboard (`/admin`)

```blade
{{-- resources/views/pages/admin/index.blade.php --}}
<?php

use App\Models\License;
use App\Models\ActivationRequest;
use App\Models\Device;
use Carbon\Carbon;

$stats = [
    'active_licenses' => License::where('status', 'active')->count(),
    'expired_today' => License::whereDate('expired_at', today())->count(),
    'pending_activations' => ActivationRequest::where('status', 'pending')->count(),
    'active_devices' => Device::where('is_active', true)->count(),
];

$recentRequests = ActivationRequest::with('license')
    ->where('status', 'pending')
    ->latest()
    ->take(5)
    ->get();

$expiringSoon = License::query()
    ->where('status', 'active')
    ->whereBetween('expired_at', [now(), now()->addDays(7)])
    ->take(5)
    ->get();
?>

<x-layouts.admin>
    <x-slot:header>Dashboard</x-slot:header>

    {{-- Stats cards --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
        <div class="bg-blue-50 p-4 rounded-lg">
            <div class="text-blue-600 text-2xl font-bold">{{ $stats['active_licenses'] }}</div>
            <div class="text-blue-800 text-sm">Active Licenses</div>
        </div>
        <div class="bg-red-50 p-4 rounded-lg">
            <div class="text-red-600 text-2xl font-bold">{{ $stats['expired_today'] }}</div>
            <div class="text-red-800 text-sm">Expired Today</div>
        </div>
        <div class="bg-yellow-50 p-4 rounded-lg">
            <div class="text-yellow-600 text-2xl font-bold">{{ $stats['pending_activations'] }}</div>
            <div class="text-yellow-800 text-sm">Pending Activations</div>
        </div>
        <div class="bg-green-50 p-4 rounded-lg">
            <div class="text-green-600 text-2xl font-bold">{{ $stats['active_devices'] }}</div>
            <div class="text-green-800 text-sm">Active Devices</div>
        </div>
    </div>

    {{-- Pending requests table --}}
    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-semibold mb-4">Pending Activation Requests</h3>
        @if($recentRequests->isEmpty())
            <p class="text-gray-500">No pending requests</p>
        @else
            <table class="w-full">
                {{-- ...table rows... --}}
            </table>
        @endif
    </div>
</x-layouts.admin>
```

#### b) Products CRUD (`/admin/products/*`)

```blade
{{-- resources/views/pages/admin/products/index.blade.php --}}
<?php

use App\Models\Product;
use App\Livewire\Forms\ProductForm;
use function Livewire\Volt\{state, computed};

$products = Product::withCount('subscriptionPlans', 'licenses')
    ->latest()
    ->get();

$deleteProduct = function (Product $product) {
    $product->delete();
    session()->flash('success', 'Product deleted.');
};
?>

<x-layouts.admin>
    <x-slot:header>Products</x-slot:header>

    <div class="flex justify-end mb-4">
        <x-primary-button onclick="window.location.href='/admin/products/create'">
            Create Product
        </x-primary-button>
    </div>

    <table class="w-full">
        <thead>
            <tr>
                <th>Name</th>
                <th>Slug</th>
                <th>Plans</th>
                <th>Licenses</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach($products as $product)
            <tr>
                <td>{{ $product->name }}</td>
                <td>{{ $product->slug }}</td>
                <td>{{ $product->subscription_plans_count }}</td>
                <td>{{ $product->licenses_count }}</td>
                <td>
                    <span @class([
                        'px-2 py-1 rounded text-sm',
                        'bg-green-100 text-green-800' => $product->is_active,
                        'bg-red-100 text-red-800' => !$product->is_active,
                    ])>
                        {{ $product->is_active ? 'Active' : 'Inactive' }}
                    </span>
                </td>
                <td class="space-x-2">
                    <a href="/admin/products/{{ $product->id }}/edit" class="text-blue-600">Edit</a>
                    <button wire:click="deleteProduct({{ $product->id }})" class="text-red-600"
                            onclick="return confirm('Delete product?')">Delete</button>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</x-layouts.admin>
```

```blade
{{-- resources/views/pages/admin/products/create.blade.php --}}
<?php

use App\Models\Product;
use Illuminate\Support\Str;
use function Livewire\Volt\{state, rules};

state([
    'name' => '',
    'description' => '',
    'is_active' => true,
]);

$slug = fn() => Str::slug($this->name);

$rules = [
    'name' => 'required|max:255',
    'description' => 'nullable',
    'is_active' => 'boolean',
];

$save = function () {
    $this->validate();

    Product::create([
        'name' => $this->name,
        'slug' => Str::slug($this->name),
        'description' => $this->description,
        'is_active' => $this->is_active,
    ]);

    session()->flash('success', 'Product created.');
    $this->redirect('/admin/products');
};
?>

<x-layouts.admin>
    <x-slot:header>Create Product</x-slot:header>

    <form wire:submit="save" class="max-w-lg space-y-4">
        <div>
            <x-input-label for="name" value="Name" />
            <x-text-input wire:model="name" id="name" class="w-full" />
            <x-input-error for="name" />
            <p class="text-sm text-gray-500">Slug: {{ $slug }}</p>
        </div>

        <div>
            <x-input-label for="description" value="Description" />
            <textarea wire:model="description" id="description"
                      class="w-full rounded-md border-gray-300 shadow-sm"></textarea>
            <x-input-error for="description" />
        </div>

        <div>
            <label class="flex items-center gap-2">
                <input type="checkbox" wire:model="is_active" class="rounded">
                Active
            </label>
        </div>

        <div class="flex gap-4">
            <x-primary-button>Save</x-primary-button>
            <x-secondary-button onclick="window.location.href='/admin/products'">
                Cancel
            </x-secondary-button>
        </div>
    </form>
</x-layouts.admin>
```

Konsep yang sama untuk halaman CRUD lainnya:
- `plans/index.blade.php` — list + create inline
- `plans/[plan]/edit.blade.php` — edit plan
- `licenses/index.blade.php` — list + search + filter
- `licenses/create.blade.php` — create license with auto-generated key
- `licenses/[license]/edit.blade.php` — edit + actions (suspend/revoke/reset)

#### c) Activation Requests (`/admin/activation-requests`)

```blade
{{-- resources/views/pages/admin/activation-requests/index.blade.php --}}
<?php

use App\Models\ActivationRequest;
use function Livewire\Volt\{state, computed};

state(['filter' => 'pending']);

$requests = fn() => ActivationRequest::with('license')
    ->when($this->filter !== 'all', fn($q) => $q->where('status', $this->filter))
    ->latest()
    ->get();

$approve = function (ActivationRequest $request) {
    $request->approve(auth()->id());
    session()->flash('success', 'Activation approved.');
};

$reject = function (ActivationRequest $request) {
    $request->reject(auth()->id());
    session()->flash('success', 'Activation rejected.');
};
?>

<x-layouts.admin>
    <x-slot:header>Activation Requests</x-slot:header>

    <div class="mb-4">
        <select wire:model="filter" class="rounded border-gray-300">
            <option value="pending">Pending</option>
            <option value="approved">Approved</option>
            <option value="rejected">Rejected</option>
            <option value="all">All</option>
        </select>
    </div>

    <table class="w-full">
        <thead>
            <tr>
                <th>License</th>
                <th>Old Device</th>
                <th>New Device</th>
                <th>Requested</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach($requests as $req)
            <tr>
                <td>{{ $req->license->license_key }}</td>
                <td>{{ $req->old_device_id ?? 'N/A' }}</td>
                <td>{{ $req->new_device_name }} ({{ $req->new_device_id }})</td>
                <td>{{ $req->requested_at->diffForHumans() }}</td>
                <td>
                    <span @class([
                        'px-2 py-1 rounded text-sm',
                        'bg-yellow-100 text-yellow-800' => $req->status === 'pending',
                        'bg-green-100 text-green-800' => $req->status === 'approved',
                        'bg-red-100 text-red-800' => $req->status === 'rejected',
                    ])>{{ $req->status }}</span>
                </td>
                <td>
                    @if($req->status === 'pending')
                        <button wire:click="approve({{ $req->id }})"
                                class="text-green-600 hover:underline">Approve</button>
                        <button wire:click="reject({{ $req->id }})"
                                class="text-red-600 hover:underline ml-2">Reject</button>
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</x-layouts.admin>
```

#### d) Audit Logs (`/admin/audit-logs`)

```blade
{{-- resources/views/pages/admin/audit-logs/index.blade.php --}}
<?php

use App\Models\AuditLog;
use function Livewire\Volt\{state};

state(['actionFilter' => '', 'search' => '']);

$logs = fn() => AuditLog::with('user', 'license')
    ->when($this->actionFilter, fn($q) => $q->where('action', $this->actionFilter))
    ->latest()
    ->paginate(50);
?>

<x-layouts.admin>
    {{-- Filters + paginated table --}}
</x-layouts.admin>
```

### 11.7 Navigation

Admin sidebar navigation component menggunakan Fortify's existing `navigation` slot:

```blade
{{-- resources/views/components/admin-nav.blade.php --}}
<nav class="space-y-1">
    <x-nav-link :href="route('admin.dashboard')" :active="request()->is('admin')">
        {{ __('Dashboard') }}
    </x-nav-link>
    <x-nav-link :href="url('/admin/licenses')" :active="request()->is('admin/licenses*')">
        {{ __('Licenses') }}
    </x-nav-link>
    <x-nav-link :href="url('/admin/products')" :active="request()->is('admin/products*')">
        {{ __('Products') }}
    </x-nav-link>
    <x-nav-link :href="url('/admin/plans')" :active="request()->is('admin/plans*')">
        {{ __('Subscription Plans') }}
    </x-nav-link>
    <x-nav-link :href="url('/admin/activation-requests')" :active="request()->is('admin/activation-requests*')">
        {{ __('Activation Requests') }}
    </x-nav-link>
    <x-nav-link :href="url('/admin/devices')" :active="request()->is('admin/devices*')">
        {{ __('Devices') }}
    </x-nav-link>
    <x-nav-link :href="url('/admin/audit-logs')" :active="request()->is('admin/audit-logs*')">
        {{ __('Audit Logs') }}
    </x-nav-link>
</nav>
```

### 11.8 Route Registration

Folio akan auto-register semua route berdasarkan struktur folder `resources/views/pages/admin/`. Tidak perlu manual route registration untuk halaman admin.

Fortify auth routes (login, register, dll) tetap menggunakan `routes/auth.php`.

```php
// routes/web.php — hanya untuk redirect atau route bantuan
Route::get('/', function () {
    return view('welcome');
});

Route::redirect('/admin', '/admin/licenses');
```

### 11.9 Admin Panel Summary

| Module              | Path                            | Type          | Features                          |
|---------------------|---------------------------------|---------------|-----------------------------------|
| Dashboard           | `/admin`                        | Volt          | Stats cards, pending requests     |
| Products            | `/admin/products`               | Volt CRUD     | Create, Edit, Delete, List        |
| Subscription Plans  | `/admin/plans`                  | Volt CRUD     | Per-product plans                 |
| Licenses            | `/admin/licenses`               | Volt CRUD     | Search, filter, suspend, revoke   |
| Devices             | `/admin/devices`                | Volt (RO)     | Read-only monitoring              |
| Activation Requests | `/admin/activation-requests`    | Volt          | Approve/Reject actions            |
| Audit Logs          | `/admin/audit-logs`             | Volt (RO)     | Read-only, paginated, filterable  |

Keuntungan pakai Livewire + Volt:
- Zero additional package install (Fortify sudah include)
- Reactive UI without JavaScript
- Single-file components (logic + template in one `.blade.php`)
- Folio auto-routing — no route registration
- Menggunakan Flux UI components yang sudah ada
- Konsisten dengan stack yang sudah terinstall

---

## 12. Admin Workflow

### 12.1 Creating a New License

```
1. Admin login via Fortify → buka `/admin/licenses`
2. Klik "Create License" → `/admin/licenses/create`
3. Isi form (Livewire Volt component):
   - Product: pilih dari dropdown
   - Customer Name: input text
   - Customer Email: input text
   - Max Devices: number (default 1)
   - Started At: date picker
   - Subscription Plan: select (dependent on product selection)
   - Notes: textarea
4. System auto-generate license_key via `LicenseKeyService`
5. System set expired_at berdasarkan started_at + plan duration
6. System create Subscription record (active)
7. System log audit: "License created for {customer}"
8. Redirect ke `/admin/licenses` dengan flash message sukses
9. Admin salin license_key untuk diberikan ke customer
```

### 12.2 Renewing a License

```
1. Admin login → `/admin/licenses`
2. Klik license → `/admin/licenses/{id}/edit`
3. Klik "Renew" (button dalam Volt component)
4. Pilih plan baru dari dropdown (bisa sama atau upgrade)
5. System suggest: starts_at = next day after current expired_at
6. Klik "Renew License" → konfirmasi
7. System:
   - Set current subscription status = expired
   - Create new Subscription record (active)
   - Update license.expired_at = new end date
   - Keep license status = active
   - Log audit: "License renewed for {plan}"
8. Flash message sukses + redirect ke list
```

### 12.3 Handling Device Migration (Approve/Reject)

```
1. Customer request pindah device via client app → API create pending request
2. Admin login → `/admin` → dashboard shows "Pending Activations" stat card
3. Klik "Activation Requests" → `/admin/activation-requests`
4. Table (Livewire Volt) shows pending requests:
   - License key (linked)
   - Old device info
   - New device name + ID
   - Requested at (relative time)
5. Admin reviews the request:
   - Check if old device is legitimately being replaced
   - Check customer identity via license detail
6. Klik "Approve" atau "Reject" (inline Livewire action):
   ✅ Approve
      → `ActivationRequest::approve(auth()->id())`
      → Deactivates old device
      → Creates new device record
      → Logs audit
      → Flash message sukses
      → Client next activation = success

   ❌ Reject
      → `ActivationRequest::reject(auth()->id())`
      → Status = rejected, handled_by = admin
      → Logs audit
      → Flash message
      → Client gets "rejected" on next activation
```

### 12.4 Suspending/Revoking a License

```
SUSPEND (temporary):
1. Admin buka `/admin/licenses/{id}/edit`
2. Ada tombol "Suspend" di dalam Volt component
3. Confirm dengan JavaScript `confirm()` + Livewire action
4. System:
   - Set status = suspended
   - Log audit: "License suspended by {admin}"
5. Flash message: "License suspended"
6. Effect: Client next validation returns suspended block
7. Unsuspend: klik "Activate" → status kembali ke active
   - Note: Hanya bisa unsuspend jika tidak expired

REVOKE (permanent):
1. Admin buka `/admin/licenses/{id}/edit`
2. Tombol "Revoke" dengan styling merah (danger)
3. Modal konfirmasi: "PERMANENT: Revoke license {key}?" (with input type 'REVOKE')
4. System:
   - Set status = revoked
   - Deactivate all devices
   - Log audit: "License revoked by {admin}"
5. Flash message: "License revoked permanently"
6. Effect: Client next validation returns revoked → force logout
7. Cannot undo — revoked is final
```

### 12.5 Force Reset Devices

```
Use case: Customer lost all devices, needs to start fresh.

1. Admin buka `/admin/licenses/{id}/edit`
2. Tombol "Force Reset Devices" di bagian bawah form
3. Confirm modal dengan informasi:
   "This will deactivate ALL {n} device(s).
    Customer will need to reactivate from scratch. Proceed?"
4. System (via Livewire action):
   - Set ALL devices to is_active = false
   - Clear all pending activation requests (set to rejected)
   - Set license activated_at = null
   - Log audit: "Devices force reset by {admin}"
5. Flash message: "All devices reset. Customer can reactivate."
6. Effect: Customer installs app → activate → first device bound (since 0 active)
```

---

## 13. Testing Strategy

### 13.1 Test Organization

```
tests/
├── Unit/
│   ├── Services/
│   │   ├── LicenseKeyServiceTest.php
│   │   └── LicenseServiceTest.php
│   └── Enums/
│       └── LicenseStatusTest.php
│
├── Feature/
│   ├── Api/
│   │   ├── LicenseValidationTest.php
│   │   ├── LicenseActivationTest.php
│   │   └── LicenseUpdateTest.php
│   │
│   └── Console/
│       └── LicensesCheckExpiredTest.php
│
└── Factories/
    ├── ProductFactory.php
    ├── SubscriptionPlanFactory.php
    ├── LicenseFactory.php
    ├── DeviceFactory.php
    ├── ActivationRequestFactory.php
    └── SubscriptionFactory.php
```

### 13.2 Factory Definitions

```php
class ProductFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->words(2, true),
            'slug' => fn(array $a) => Str::slug($a['name']),
            'description' => fake()->sentence(),
            'is_active' => true,
        ];
    }
}

class LicenseFactory extends Factory
{
    public function definition(): array
    {
        return [
            'product_id' => ProductFactory::new(),
            'customer_name' => fake()->name(),
            'customer_email' => fake()->email(),
            'license_key' => 'LIC-' . strtoupper(Str::random(8)) . '-' . strtoupper(Str::random(8)),
            'status' => LicenseStatus::Active,
            'max_devices' => 1,
            'started_at' => now()->subMonth(),
            'expired_at' => now()->addYear(),
        ];
    }

    public function active(): static
    {
        return $this->state(fn() => [
            'status' => LicenseStatus::Active,
            'expired_at' => now()->addYear(),
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn() => [
            'status' => LicenseStatus::Expired,
            'expired_at' => now()->subDay(),
        ]);
    }

    public function suspended(): static
    {
        return $this->state(fn() => [
            'status' => LicenseStatus::Suspended,
        ]);
    }

    public function revoked(): static
    {
        return $this->state(fn() => [
            'status' => LicenseStatus::Revoked,
        ]);
    }

    public function withDevices(int $count = 1): static
    {
        return $this->has(Device::factory()->count($count));
    }

    public function withMaxDevices(int $max = 3): static
    {
        return $this->state(fn() => ['max_devices' => $max]);
    }
}

class DeviceFactory extends Factory
{
    public function definition(): array
    {
        return [
            'device_id' => (string) Str::uuid(),
            'device_name' => fake()->word() . ' ' . fake()->randomNumber(3),
            'activated_at' => now(),
            'last_seen_at' => now(),
            'is_active' => true,
        ];
    }
}

class ActivationRequestFactory extends Factory
{
    public function definition(): array
    {
        return [
            'new_device_id' => (string) Str::uuid(),
            'new_device_name' => fake()->word(),
            'status' => ActivationRequestStatus::Pending,
            'requested_at' => now(),
        ];
    }

    public function pending(): static
    {
        return $this->state(fn() => ['status' => ActivationRequestStatus::Pending]);
    }

    public function approved(): static
    {
        return $this->state(fn() => [
            'status' => ActivationRequestStatus::Approved,
            'handled_at' => now(),
            'handled_by' => UserFactory::new(),
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn() => [
            'status' => ActivationRequestStatus::Rejected,
            'handled_at' => now(),
            'handled_by' => UserFactory::new(),
        ]);
    }
}
```

### 13.3 Feature Test Examples

```php
// tests/Feature/Api/LicenseValidationTest.php

class LicenseValidationTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_can_validate_active_license_with_bound_device()
    {
        $license = LicenseFactory::new()
            ->active()
            ->withDevices(1)
            ->create();

        $device = $license->devices->first();

        $response = $this->postJson('/api/license/validate', [
            'license_key' => $license->license_key,
            'device_id' => $device->device_id,
        ]);

        $response->assertOk();
        $response->assertJson([
            'valid' => true,
            'status' => 'active',
        ]);
        $response->assertJsonStructure([
            'valid', 'status', 'expired_at',
            'cache_until', 'cache_ttl_seconds',
            'server_time', 'message',
        ]);
    }

    public function test_cannot_validate_expired_license()
    {
        $license = LicenseFactory::new()->expired()->create();
        $device = DeviceFactory::new()->create(['license_id' => $license->id]);

        $response = $this->postJson('/api/license/validate', [
            'license_key' => $license->license_key,
            'device_id' => $device->device_id,
        ]);

        $response->assertOk();
        $response->assertJson([
            'valid' => false,
            'status' => 'expired',
        ]);
    }

    public function test_validation_updates_device_last_seen()
    {
        $license = LicenseFactory::new()->active()->withDevices(1)->create();
        $device = $license->devices->first();
        $originalSeen = $device->last_seen_at;

        $this->travel(1)->hour();

        $this->postJson('/api/license/validate', [
            'license_key' => $license->license_key,
            'device_id' => $device->device_id,
        ]);

        $device->refresh();
        $this->assertTrue($device->last_seen_at->gt($originalSeen));
    }

    public function test_validation_fails_on_device_mismatch()
    {
        $license = LicenseFactory::new()->active()->create();

        $response = $this->postJson('/api/license/validate', [
            'license_key' => $license->license_key,
            'device_id' => 'unregistered-device-id',
        ]);

        $response->assertOk();
        $response->assertJson([
            'valid' => false,
            'status' => 'device_mismatch',
        ]);
    }

    public function test_cannot_validate_revoked_license()
    {
        $license = LicenseFactory::new()->revoked()->withDevices(1)->create();
        $device = $license->devices->first();

        $response = $this->postJson('/api/license/validate', [
            'license_key' => $license->license_key,
            'device_id' => $device->device_id,
        ]);

        $response->assertOk();
        $response->assertJson([
            'valid' => false,
            'status' => 'revoked',
        ]);
    }

    public function test_cannot_validate_with_invalid_key_format()
    {
        $response = $this->postJson('/api/license/validate', [
            'license_key' => 'INVALID-KEY',
            'device_id' => 'some-device',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['license_key']);
    }
}
```

```php
// tests/Feature/Api/LicenseActivationTest.php

class LicenseActivationTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_can_activate_first_device()
    {
        $license = LicenseFactory::new()->active()->create();

        $response = $this->postJson('/api/license/activate', [
            'license_key' => $license->license_key,
            'device_id' => (string) Str::uuid(),
            'device_name' => 'Kasir Utama',
        ]);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'status' => 'active',
        ]);

        $this->assertEquals(1, $license->fresh()->activeDeviceCount());
        $this->assertNotNull($license->fresh()->activated_at);
    }

    public function test_activation_is_idempotent_for_same_device()
    {
        $license = LicenseFactory::new()->active()->withDevices(1)->create();
        $device = $license->devices->first();

        $response = $this->postJson('/api/license/activate', [
            'license_key' => $license->license_key,
            'device_id' => $device->device_id,
            'device_name' => $device->device_name,
        ]);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'message' => 'Device already activated',
        ]);

        $this->assertEquals(1, $license->fresh()->activeDeviceCount());
    }

    public function test_can_activate_multiple_devices_up_to_max()
    {
        $license = LicenseFactory::new()
            ->active()
            ->withMaxDevices(3)
            ->create();

        $deviceIds = [];

        for ($i = 0; $i < 3; $i++) {
            $deviceId = (string) Str::uuid();
            $deviceIds[] = $deviceId;

            $response = $this->postJson('/api/license/activate', [
                'license_key' => $license->license_key,
                'device_id' => $deviceId,
                'device_name' => "Device {$i}",
            ]);

            $response->assertOk();
            $response->assertJsonPath('success', true);
        }

        $this->assertEquals(3, $license->fresh()->activeDeviceCount());
    }

    public function test_device_limit_creates_pending_request()
    {
        $license = LicenseFactory::new()
            ->active()
            ->withMaxDevices(1)
            ->withDevices(1)
            ->create();

        $response = $this->postJson('/api/license/activate', [
            'license_key' => $license->license_key,
            'device_id' => (string) Str::uuid(),
            'device_name' => 'New Device',
        ]);

        $response->assertOk();
        $response->assertJson([
            'success' => false,
            'status' => 'pending_approval',
        ]);

        $this->assertDatabaseHas('activation_requests', [
            'license_id' => $license->id,
            'status' => 'pending',
        ]);
    }

    public function test_cannot_activate_expired_license()
    {
        $license = LicenseFactory::new()->expired()->create();

        $response = $this->postJson('/api/license/activate', [
            'license_key' => $license->license_key,
            'device_id' => (string) Str::uuid(),
            'device_name' => 'Test Device',
        ]);

        $response->assertOk();
        $response->assertJson([
            'success' => false,
            'status' => 'expired',
        ]);
    }

    public function test_cannot_activate_with_invalid_key()
    {
        $response = $this->postJson('/api/license/activate', [
            'license_key' => 'INVALID',
            'device_id' => (string) Str::uuid(),
            'device_name' => 'Test',
        ]);

        $response->assertStatus(422);
    }
}
```

### 13.4 Console Command Test

```php
// tests/Feature/Console/LicensesCheckExpiredTest.php

class LicensesCheckExpiredTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_expired_licenses_are_marked_expired()
    {
        LicenseFactory::new()->active()->create([
            'expired_at' => now()->subDay(),
        ]);

        $this->artisan('licenses:check-expired')
            ->expectsOutputToContain('expired')
            ->assertSuccessful();

        $this->assertDatabaseHas('licenses', [
            'status' => 'expired',
        ]);
    }

    public function test_active_licenses_are_not_marked_expired()
    {
        LicenseFactory::new()->active()->create([
            'expired_at' => now()->addMonth(),
        ]);

        $this->artisan('licenses:check-expired')
            ->assertSuccessful();

        $this->assertDatabaseMissing('licenses', [
            'status' => 'expired',
        ]);
    }
}
```

### 13.5 Test Configuration

```php
// phpunit.xml — pastikan sudah menggunakan LazilyRefreshDatabase di base TestCase

// tests/TestCase.php
abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;
    use LazilyRefreshDatabase;

    // Default setup
    protected function setUp(): void
    {
        parent::setUp();

        // Prevent stray HTTP requests in tests
        Http::preventStrayRequests();
    }
}
```

---

## 14. Development Milestone

### 14.1 Phase Breakdown

```
PRD ✅ (done)
  │
  ▼
ARCHITECTURE & DESIGN ✅ (this document)
  │
  ▼
┌──────────────────────────────────────────────────────────────────┐
│  MILESTONE 1: Foundation                                         │
│  Target: 1 session                                               │
│                                                                  │
│  [ ] No additional install (Fortify + Livewire + Volt ready)     │
│  [ ] Create migrations (8 files)                                 │
│  [ ] Create models (7 files)                                     │
│  [ ] Create enums (4 files)                                      │
│  [ ] Create factories (6 files)                                  │
│  [ ] Run migrations                                              │
│  [ ] Add is_admin to users table                                 │
│  [ ] Run pint                                                    │
│                                                                  │
│  DELIVERABLE: Database + models ready, can seed test data        │
└──────────────────────────────────────────────────────────────────┘
    │
    ▼
┌──────────────────────────────────────────────────────────────────┐
│  MILESTONE 2: Core Business Logic                                │
│  Target: 1 session                                               │
│                                                                  │
│  [ ] Create LicenseKeyService                                    │
│  [ ] Create LicenseService (validate + activate + checkUpdate)   │
│  [ ] Create audit helper for AuditLog::log()                    │
│  [ ] Unit tests for LicenseKeyService                           │
│  [ ] Unit tests for LicenseService                              │
│  [ ] Run pint                                                    │
│                                                                  │
│  DELIVERABLE: Business logic working, tested                     │
└──────────────────────────────────────────────────────────────────┘
    │
    ▼
┌──────────────────────────────────────────────────────────────────┐
│  MILESTONE 3: API Endpoints                                      │
│  Target: 1 session                                               │
│                                                                  │
│  [ ] Create Form Requests (3 files)                             │
│  [ ] Create Controllers (3 invokable)                           │
│  [ ] Create API routes                                           │
│  [ ] Configure rate limiting                                     │
│  [ ] Configure throttle middleware on API                        │
│  [ ] Feature tests for all 3 endpoints                          │
│  [ ] Manual test with curl/Postman                               │
│  [ ] Run pint                                                    │
│                                                                  │
│  DELIVERABLE: API endpoints working + tested                     │
└──────────────────────────────────────────────────────────────────┘
    │
    ▼
┌──────────────────────────────────────────────────────────────────┐
│  MILESTONE 4: Admin Panel (Livewire + Volt + Folio)              │
│  Target: 1 session                                               │
│                                                                  │
│  [ ] Create CheckAdminMiddleware                                 │
│  [ ] Register middleware in bootstrap/app.php                    │
│  [ ] Create admin layout (layouts/admin.blade.php)               │
│  [ ] Create admin navigation component                           │
│  [ ] Register Folio middleware for admin pages                   │
│  [ ] Create Dashboard page (Volt stats + pending requests)       │
│  [ ] Create Products CRUD pages (index + create + edit)          │
│  [ ] Create Plans CRUD pages (index + create + edit)             │
│  [ ] Create Licenses CRUD pages (index + create + edit)          │
│  [ ] Add Suspend/Revoke actions to license edit                  │
│  [ ] Add Force Reset Devices action to license edit              │
│  [ ] Create Devices page (read-only list)                        │
│  [ ] Create Activation Requests page (approve/reject)            │
│  [ ] Create Audit Logs page (read-only, paginated)               │
│  [ ] Run pint                                                    │
│                                                                  │
│  DELIVERABLE: Full admin panel at /admin/*                       │
└──────────────────────────────────────────────────────────────────┘
    │
    ▼
┌──────────────────────────────────────────────────────────────────┐
│  MILESTONE 5: Scheduler & Automation                             │
│  Target: 1 session                                               │
│                                                                  │
│  [ ] Create LicensesCheckExpired command                         │
│  [ ] Create LicensesNotifyExpiring command                       │
│  [ ] Register scheduled tasks in routes/console.php              │
│  [ ] Feature tests for scheduler commands                       │
│  [ ] Run pint                                                    │
│                                                                  │
│  DELIVERABLE: Auto-expiry, ready for cron setup                  │
└──────────────────────────────────────────────────────────────────┘
    │
    ▼
┌──────────────────────────────────────────────────────────────────┐
│  MILESTONE 6: Verification                                       │
│  Target: 1 session                                               │
│                                                                  │
│  [ ] Run full test suite (php artisan test)                      │
│  [ ] Run pint --test                                             │
│  [ ] Manual end-to-end flow test:                                │
│      - Create product → plan → license                           │
│      - Activate device via API                                   │
│      - Validate device via API                                   │
│      - Verify expiry via scheduler                               │
│      - Test activation request flow                              │
│      - Test approve/reject from admin                            │
│  [ ] Fix any issues                                              │
│                                                                  │
│  DELIVERABLE: Production-ready system                            │
└──────────────────────────────────────────────────────────────────┘
```

### 14.2 Build Order Summary

```
Milestone 1: Foundation    (DB + Models)        → 1 session
Milestone 2: Core Logic    (Services)            → 1 session
Milestone 3: API           (Endpoints + Tests)   → 1 session
Milestone 4: Admin Panel   (Livewire + Volt)     → 1 session
Milestone 5: Automation    (Scheduler)           → 1 session
Milestone 6: Verification  (Testing)             → 1 session
                                        TOTAL: ~6 sessions
```

### 14.3 What NOT to Build (Phase 1)

- ❌ App version management (no app_versions table — placeholder in check-update)
- ❌ Email notifications (admin dashboard is notification for now)
- ❌ Payment gateway integration
- ❌ API tokens / JWT / PASETO
- ❌ Machine fingerprinting (simple UUID is enough)
- ❌ Distributed licensing / multi-server
- ❌ Reseller support
- ❌ Usage analytics dashboard

---

## 15. Design Decisions

| # | Decision | Choice | Alternatives Considered | Rationale |
|---|----------|--------|------------------------|-----------|
| 1 | License key format | `LIC-XXXXXXXX-XXXXXXXX` (readable) | UUID, Base64 encoded | Human-readable, easy to type/communicate, enough entropy (128-bit) |
| 2 | Device identifier | Client-generated UUID | Hardware fingerprint, MAC-based | Simple, no privacy concerns, client can regenerate |
| 3 | Max devices | Field on license (default 1) | Fixed 1 device | Flexible pricing: 1 for basic, multiple for enterprise |
| 4 | Device migration flow | Pending → Admin approve/reject | Auto-approve with device limit | Security: prevents unauthorized device hopping |
| 5 | Cache validity period | 7 days fixed | Configurable per license, 30 days | Balances security with offline usability |
| 6 | Cache ownership | Client-side (file/db) | Server-side Redis | Client tetap jalan saat server offline, no single point of failure |
| 7 | Server-side caching for validation | None | Redis cache with TTL | Always return fresh data, no staleness issues, simple |
| 8 | API authentication | None (validated by license_key) | Sanctum tokens, HMAC signature | License_key IS the credential for this use case |
| 9 | Audit logging | Custom `audit_logs` table | Spatie Activitylog, Laravel Telescope | Lightweight, no extra deps, full control |
| 10 | Admin permissions | `is_admin` boolean + Gate + CheckAdminMiddleware | Spatie Permission, Filament Shield | Simple for current needs (single admin team) |
| 11 | Subscription billing | Manual (admin creates record) | Stripe, Midtrans, Laravel Cashier | Phase 1: no payment gateway; add later |
| 12 | Admin panel | Fortify + Livewire + Volt + Folio | Filament, Nova, Custom Blade | Zero additional install, Fortify already included, reactive UI |
| 13 | Controllers | Invokable single-action | Resource controllers | Single responsibility, thin controllers |
| 14 | Business logic | Service class (LicenseService) | Repository pattern, Action classes | Balances simplicity with testability |
| 15 | Status values | Native PHP enums | Database enum, constant strings | Type-safe, IDE-friendly, serializable |
| 16 | Form validation | Form Request classes | Inline in controller | Separation of concerns, reusable, testable |
| 17 | Testing approach | Feature tests (HTTP) + Unit tests | Pure unit tests | Feature tests cover real request/response flow |
| 18 | Offline abuse prevention | Cache expiration (7 days) | Cryptographic tokens, online-only | Simple, no complex crypto, adequate for Phase 1 |

---

## 16. Environment Variables

```env
# App
APP_NAME=Monitoring
APP_URL=https://monitor.test

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=monitoring
DB_USERNAME=root
DB_PASSWORD=

# Cache & Queue (optional, for future use)
CACHE_DRIVER=file        # file is fine for Phase 1
QUEUE_CONNECTION=sync    # sync is fine for Phase 1

# Session
SESSION_DRIVER=file
SESSION_LIFETIME=120

# Sanctum (for future admin API)
SANCTUM_STATEFUL_DOMAINS=monitor.test
```

---

## 17. Future Phases (Post-Phase 1)

### Phase 2: Enhancement
- Subscription auto-renewal
- Email notifications (expiry, activation request)
- Usage analytics dashboard
- App version management (app_versions table)
- Client-side update download

### Phase 3: Scale
- Signed tokens (JWT/PASETO)
- Offline cryptographic validation
- Multi-server support
- Redis cache for validation
- API tokens for third-party integrations

### Phase 4: Monetization
- Payment gateway integration (Stripe/Midtrans)
- Self-service customer portal
- Reseller support with commission
- Marketplace licensing
- Promo/discount system

---

## Implementation Status

| Milestone | Status | Files |
|-----------|--------|-------|
| Milestone 1: Foundation | ✅ Completed | 8 migrations, 7 models, 4 enums, 6 factories |
| Milestone 2: Core Business Logic | ✅ Completed | 2 services (LicenseKeyService, LicenseService) |
| Milestone 3: API Endpoints | ✅ Completed | 3 form requests, 3 controllers, 3 routes, rate limiting |
| Milestone 4: Admin Panel | ✅ Completed | 1 middleware, 2 layouts, 13 Volt Folio pages |
| Milestone 5: Scheduler & Automation | ✅ Completed | 2 commands, schedule registration |
| Milestone 6: Verification | ✅ Completed | 77 tests (221 assertions), E2E flow test, pint passed |

**Total test suite:** 77 passed, 0 failed
