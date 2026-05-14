# Product Requirements Document (PRD)

## Laravel Licensing Server System

---

# 1. Overview

## 1.1 Product Name

Monitoring Licensing Server

---

## 1.2 Purpose

Membangun centralized licensing server untuk aplikasi Laravel yang disewakan/subscription-based.

Server ini akan menjadi pusat:

* pembuatan lisensi
* validasi lisensi
* aktivasi device
* approval perpindahan device
* suspend/revoke lisensi
* subscription management
* monitoring client

Sistem dirancang:

* semi-online
* sederhana
* scalable
* mudah di-maintain
* reusable untuk banyak aplikasi Laravel

---

# 2. Goals

## 2.1 Primary Goals

* Membuat sistem licensing sederhana tetapi production-ready
* Mencegah lisensi dipindahkan bebas antar device
* Mengontrol masa aktif aplikasi client
* Memungkinkan aplikasi client berjalan semi-offline
* Menyediakan admin panel untuk monitoring lisensi

---

## 2.2 Non Goals

Versi awal TIDAK mencakup:

* DRM enterprise
* anti reverse engineering
* anti tampering hardcore
* cryptographic offline signing
* blockchain licensing
* distributed licensing
* reseller marketplace
* machine fingerprint kompleks

---

# 3. High Level Architecture

```text
┌────────────────────────────┐
│ Monitoring License Server  │
│ monitor.test               │
│                            │
│ - License API              │
│ - Admin Panel              │
│ - Device Management        │
│ - Subscription Control     │
│ - Audit Logs               │
└──────────────┬─────────────┘
               │ HTTPS API
               │
┌──────────────▼─────────────┐
│ Client Application         │
│ laravel-pos.test           │
│                            │
│ - License Middleware       │
│ - Local Cache              │
│ - Device UUID              │
└────────────────────────────┘
```

---

# 4. Technology Stack

## 4.1 Backend

| Component   | Technology          |
| -------------| ---------------------|
| Framework   | Laravel 12/13       |
| PHP         | PHP 8.3+            |
| Database    | MySQL / MariaDB     |
| Cache       | Redis (optional)    |
| Queue       | Redis Queue         |
| HTTP API    | Laravel API         |
| Auth        | Laravel Sanctum     |
| Admin Panel | Breeze and Livewire |
| Logging     | Laravel Log         |
| Scheduler   | Laravel Scheduler   |

---

## 4.2 Development Environment

| Component      | Tool                                                 |
| -------------- | ---------------------------------------------------- |
| Local Domain   | Herd                                                 |
| License Server | [https://monitor.test](https://monitor.test)         |
| Client App     | [https://laravel-pos.test](https://laravel-pos.test) |

---

# 5. Core Concepts

---

# 5.1 License

License adalah izin penggunaan aplikasi.

Setiap license memiliki:

* unique key
* expiration date
* status
* device binding
* subscription state

---

# 5.2 Device Binding

Setiap license hanya boleh aktif pada device tertentu.

Jika aplikasi dipindahkan:

* perlu approval admin
* atau reset activation

---

# 5.3 Semi Offline Validation

Client tidak perlu selalu online.

Client akan:

* sync berkala ke server
* menyimpan cache validasi lokal
* tetap berjalan beberapa hari saat offline

---

# 6. Functional Requirements

---

# 6.1 License Management

## Features

* Create license
* Edit license
* Suspend license
* Revoke license
* Renew license
* Expire license otomatis
* Search license
* Filter license

---

## License Fields

| Field             | Type      | Description              |
| ----------------- | --------- | ------------------------ |
| id                | bigint    | Primary ID               |
| product_id        | bigint    | Relasi product           |
| customer_name     | string    | Nama customer            |
| customer_email    | string    | Email customer           |
| license_key       | string    | Unique license key       |
| status            | enum      | active/suspended/revoked |
| expired_at        | timestamp | Masa aktif               |
| activated_at      | timestamp | Waktu aktivasi           |
| current_device_id | string    | Device aktif             |
| max_devices       | integer   | Maksimal device          |
| notes             | text      | Catatan admin            |

---

# 6.2 Product Management

Sistem harus support multi product.

Contoh:

* POS
* ERP
* HRIS

---

## Product Fields

| Field       | Type    |
| ----------- | ------- |
| id          | bigint  |
| name        | string  |
| slug        | string  |
| description | text    |
| status      | boolean |

---

# 6.3 Device Management

## Features

* Simpan device aktif
* Deteksi device mismatch
* Device approval
* Force reset device
* View device history

---

## Device Fields

| Field        | Type      |
| ------------ | --------- |
| id           | bigint    |
| license_id   | bigint    |
| device_id    | string    |
| device_name  | string    |
| ip_address   | string    |
| activated_at | timestamp |
| last_seen_at | timestamp |
| status       | enum      |

---

# 6.4 Activation Request

Jika device berbeda mencoba login:

* buat activation request
* status pending
* admin approve/reject

---

## Activation Request Fields

| Field         | Type      |
| ------------- | --------- |
| id            | bigint    |
| license_id    | bigint    |
| old_device_id | string    |
| new_device_id | string    |
| status        | enum      |
| requested_at  | timestamp |
| approved_at   | timestamp |
| approved_by   | bigint    |

---

# 6.5 Validation API

---

## Endpoint

```http
POST /api/license/validate
```

---

## Request

```json
{
  "license_key": "ABC-123",
  "device_id": "DEVICE-001",
  "app_version": "1.0.0"
}
```

---

## Response Success

```json
{
  "valid": true,
  "status": "active",
  "expired_at": "2026-12-01",
  "cache_until": "2026-05-20",
  "message": "License valid"
}
```

---

## Response Failed

```json
{
  "valid": false,
  "status": "expired",
  "message": "License expired"
}
```

---

# 6.6 Activation API

---

## Endpoint

```http
POST /api/license/activate
```

---

## Request

```json
{
  "license_key": "ABC-123",
  "device_id": "DEVICE-001",
  "device_name": "Kasir Toko 1"
}
```

---

## Activation Logic

### Jika belum ada device

* bind device
* activation success

### Jika device sama

* success

### Jika device berbeda

* pending approval
* reject activation

---

# 6.7 License Cache Strategy

Server mengirim:

```json
{
  "cache_until": "2026-05-20"
}
```

Client boleh offline hingga tanggal tersebut.

---

# 6.8 Admin Panel

Admin panel menggunakan Breeze and Livewire.

---

## Modules

### Dashboard

* total active licenses
* expired licenses
* suspended licenses
* activation requests
* online devices

---

### Licenses

CRUD lisensi.

---

### Products

CRUD produk.

---

### Devices

Monitoring device.

---

### Activation Requests

Approve/reject perpindahan device.

---

### Logs

Audit logs.

---

# 6.9 Audit Logging

Semua aktivitas dicatat.

---

## Log Activities

* create license
* activate device
* validate license
* revoke license
* suspend license
* approve activation
* reject activation

---

# 7. Non Functional Requirements

---

# 7.1 Performance

| Requirement           | Target       |
| --------------------- | ------------ |
| Validation API        | < 300ms      |
| Admin panel response  | < 1 sec      |
| Concurrent validation | 1000 req/min |

---

# 7.2 Security

---

## Requirements

* HTTPS only
* API token protection
* Rate limiting
* Validation throttling
* Request logging
* Device binding
* License revocation

---

## Optional Future Security

* Signed token
* JWT/PASETO
* Request signature
* Offline cryptographic validation

---

# 7.3 Availability

Client tetap dapat berjalan:

* hingga 7 hari tanpa internet
* selama cache valid

---

# 8. Database Design

---

# 8.1 Tables

## products

## licenses

## devices

## activation_requests

## audit_logs

## users

---

# 9. API Design

---

# 9.1 Public API

| Endpoint                  | Method |
| ------------------------- | ------ |
| /api/license/validate     | POST   |
| /api/license/activate     | POST   |
| /api/license/check-update | POST   |

---

# 9.2 Admin API

Protected using Sanctum.

---

# 10. Client Workflow

---

# 10.1 Initial Activation

```text
Install aplikasi
    ↓
Input license key
    ↓
Call activation API
    ↓
Server bind device
    ↓
License activated
```

---

# 10.2 Daily Validation

```text
Middleware check
    ↓
Cache masih valid?
    ↓ YES
Lanjut aplikasi
    ↓ NO
Call validation API
```

---

# 10.3 Device Migration

```text
Device baru login
    ↓
Server detect mismatch
    ↓
Create activation request
    ↓
Admin approve
    ↓
Device replaced
```

---

# 11. Error Handling

| Error              | Handling         |
| ------------------ | ---------------- |
| License expired    | Block app        |
| Device mismatch    | Request approval |
| Server unreachable | Use cache        |
| Invalid key        | Reject           |
| Revoked license    | Force logout     |

---

# 12. Environment Variables

```env
APP_NAME=Monitoring
APP_URL=https://monitor.test

DB_DATABASE=monitoring

CACHE_DRIVER=redis
QUEUE_CONNECTION=redis

SANCTUM_STATEFUL_DOMAINS=monitor.test
```

---

# 13. Future Roadmap

---

# Phase 1

* basic licensing
* device binding
* validation API
* admin panel
* local cache

---

# Phase 2

* subscription billing
* auto renewal
* notifications
* usage analytics
* update delivery

---

# Phase 3

* signed tokens
* offline cryptographic validation
* multi tenant
* reseller support
* marketplace licensing

---

# 14. Risks

| Risk                   | Mitigation           |
| ---------------------- | -------------------- |
| User bypass middleware | Obfuscate logic      |
| Shared license         | Device binding       |
| Offline abuse          | Cache expiration     |
| Server downtime        | Offline grace period |
| Duplicate activation   | Approval flow        |

---

# 15. Recommended Development Order

---

# Step 1

* products table
* licenses table
* basic CRUD

---

# Step 2

* validation API
* activation API
* middleware testing

---

# Step 3

* device binding
* activation request

---

# Step 4

* admin panel
* monitoring dashboard

---

# Step 5

* local cache strategy
* offline support

---

# Step 6

* optimization
* logging
* hardening

---

# 16. Final Recommendation

Sistem harus tetap:

* sederhana
* maintainable
* reusable
* scalable

Hindari overengineering pada fase awal.

Fokus utama:

* stabilitas licensing flow
* device binding
* admin control
* subscription management
* semi-offline usability
