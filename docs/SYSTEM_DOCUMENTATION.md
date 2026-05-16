# System Documentation — License Monitor

> Dokumentasi sistem lengkap untuk aplikasi manajemen lisensi perangkat lunak berbasis Laravel.

---

## 1. Ringkasan Sistem

**License Monitor** adalah aplikasi web untuk mengelola lisensi perangkat lunak, aktivasi perangkat, langganan (subscription), dan sinkronisasi produk dari repositori GitHub. Dibangun di atas Laravel 13 dengan Livewire 4, Volt 1, Flux UI 2, Folio, dan Fortify.

**Tujuan utama:**
- Menerbitkan dan mengelola lisensi perangkat lunak (online/offline/semi-online)
- Melacak aktivasi perangkat dengan fingerprint hardware
- Mengelola paket langganan dan pembayaran (Stripe-ready)
- Import otomatis produk dari repositori GitHub
- Role-based access control dengan Spatie Permission

---

## 2. Arsitektur Aplikasi

### 2.1 Stack Teknologi

| Layer | Teknologi |
|-------|-----------|
| **Framework** | Laravel 13 |
| **PHP** | ^8.3 |
| **Database** | SQLite (dev), MySQL/PostgreSQL (production) |
| **Frontend** | Livewire 4 + Volt 1 + Flux UI 2 |
| **Routing** | Laravel Folio (file-based pages) |
| **Auth** | Laravel Fortify (login, register, 2FA, password reset) |
| **Roles & Permissions** | Spatie Laravel Permission v7 |
| **Styling** | Tailwind CSS 4 |
| **Build** | Vite |
| **Testing** | PHPUnit 12 + Playwright (E2E) |
| **Cache** | Database driver |

### 2.2 Direktori Struktur

```
app/
├── Actions/Fortify/         # Fortify actions: CreateNewUser, ResetUserPassword
├── Console/Commands/        # Artisan commands (3 commands)
├── Enums/                   # PHP 8 enums (4 enums)
├── Http/
│   ├── Controllers/
│   │   └── Api/             # REST API controllers (3 controllers)
│   ├── Middleware/           # CheckAdminMiddleware
│   ├── Requests/            # Form request validation (3 requests)
│   └── Responses/           # Fortify response contracts (3 responses)
├── Livewire/Actions/        # Livewire action components
├── Models/                  # Eloquent models (8 models)
├── Notifications/           # (empty, placeholder)
├── Policies/                # (empty, placeholder)
├── Providers/               # Service providers (3 providers)
└── Services/                # Business logic services (3 services)

config/                      # 11 config files
database/
├── factories/               # 8 factories
├── migrations/              # 19 migrations
└── seeders/                 # 3 seeders

resources/views/
├── components/              # Blade components (6)
├── layouts/                 # Layout files (app sidebar)
├── pages/                   # Folio pages (~34 file routes)
├── partials/                # Partials
└── flux/                    # Flux overrides

routes/
├── web.php                  # Web routes
├── api.php                  # API routes (v1)
├── console.php              # Artisan & schedule
└── settings.php             # Settings routes

tests/
├── Feature/                 # Feature tests (~15)
├── Unit/                    # Unit tests (~3)
└── e2e/                     # Playwright E2E tests (~5)
```

---

## 3. Entity Relationship Diagram

```
User ──1:N──> License ──N:1──> Product
 │              │                │
 │              │                │
 │              ├──1:N──> Device │
 │              │                │
 │              ├──1:N──> ActivationRequest
 │              │
 │              └──1:N──> Subscription ──N:1──> SubscriptionPlan
 │                                            │
 └──1:N──> Subscription ──────────────────────┘

AuditLog (polymorphic audit trail, linked to any entity via entity_type/entity_id)
```

---

## 4. Database Schema

### 4.1 `products`
Menyimpan produk perangkat lunak yang dilisensikan.

| Column | Type | Notes |
|--------|------|-------|
| id | bigint AI | Primary key |
| name | string(255) | Nama produk |
| slug | string(255) | Unique slug |
| description | text, nullable | Deskripsi produk (dari README GitHub jika sync) |
| is_active | boolean(1) | Status aktif/nonaktif |
| github_repo_id | bigint, nullable | GitHub repo ID |
| github_repo_full_name | string(255), nullable | Format: `owner/repo` |
| github_repo_url | string(255), nullable | URL GitHub |
| github_repo_description | text, nullable | Deskripsi dari GitHub |
| github_default_branch | string(255), nullable | Branch default (`main`) |
| created_at | timestamp | |
| updated_at | timestamp | |

**Index:** `slug` unique

### 4.2 `subscription_plans`
Paket langganan untuk setiap produk.

| Column | Type | Notes |
|--------|------|-------|
| id | bigint AI | |
| product_id | bigint FK | BelongsTo Product |
| name | string(255) | Nama paket (Starter, Professional, Enterprise) |
| slug | string(255) | Unique |
| description | text, nullable | |
| monthly_price | decimal(8,2) | Harga bulanan |
| yearly_price | decimal(8,2) | Harga tahunan |
| stripe_price_id_monthly | string, nullable | Stripe price ID |
| stripe_price_id_yearly | string, nullable | Stripe price ID |
| max_devices | integer | Batas perangkat |
| features | json, nullable | Daftar fitur |
| is_active | boolean(1) | |
| is_default | boolean(1) | Default plan |
| timestamps | | |

### 4.3 `licenses`
Lisensi perangkat lunak yang diterbitkan ke user.

| Column | Type | Notes |
|--------|------|-------|
| id | bigint AI | |
| product_id | bigint FK | |
| user_id | bigint FK | |
| subscription_plan_id | bigint FK, nullable | |
| key | string(255) | Unique, format: `XXXX-XXXX-XXXX-XXXX` |
| status | string(20) | Enum: active, suspended, expired, revoked |
| mode | string(20) | Enum: online, offline, semi_online |
| max_devices | integer | Default 1 |
| expires_at | datetime, nullable | |
| activated_at | datetime, nullable | |
| metadata | json, nullable | Data tambahan |
| notes | text, nullable | |
| timestamps | | |

### 4.4 `devices`
Perangkat yang terdaftar pada lisensi.

| Column | Type | Notes |
|--------|------|-------|
| id | bigint AI | |
| license_id | bigint FK | |
| name | string(255), nullable | Nama perangkat |
| fingerprint | string(64) | Hardware fingerprint (SHA-256 hash) |
| platform | string(50), nullable | OS (windows, macos, linux, ios, android) |
| platform_version | string(50), nullable | |
| app_version | string(50), nullable | |
| ip_address | string(45), nullable | |
| last_seen_at | datetime, nullable | |
| activated_at | datetime, nullable | |
| is_active | boolean(1) | |
| timestamps | | |

### 4.5 `activation_requests`
Permintaan aktivasi manual untuk lisensi online/semi-online.

| Column | Type | Notes |
|--------|------|-------|
| id | bigint AI | |
| license_id | bigint FK | |
| device_id | bigint FK, nullable | |
| code | string(20) | Kode aktivasi 8 karakter |
| status | string(20) | Enum: pending, approved, rejected, expired |
| expires_at | datetime | Masa berlaku 30 menit |
| activated_at | datetime, nullable | |
| rejection_reason | text, nullable | |
| timestamps | | |

### 4.6 `subscriptions`
Langganan user ke plan.

| Column | Type | Notes |
|--------|------|-------|
| id | bigint AI | |
| user_id | bigint FK | |
| subscription_plan_id | bigint FK | |
| license_id | bigint FK, nullable | |
| stripe_subscription_id | string, nullable | |
| stripe_customer_id | string, nullable | |
| status | string(20) | Enum Stripe: active, past_due, canceled, trialing, incomplete |
| current_period_start_at | datetime, nullable | |
| current_period_end_at | datetime, nullable | |
| cancels_at | datetime, nullable | |
| timestamps | | |

### 4.7 `audit_logs`
Audit trail untuk semua perubahan licensing.

| Column | Type | Notes |
|--------|------|-------|
| id | bigint AI | |
| action | string(100) | created, suspended, revoked, activated, dll |
| entity_type | string(255) | Class name |
| entity_id | bigint | |
| user_id | bigint FK, nullable | |
| old_values | json, nullable | |
| new_values | json, nullable | |
| ip_address | string(45), nullable | |
| user_agent | text, nullable | |
| created_at | datetime | Manual timestamp (timestamps=false) |

**Note:** Tidak memiliki kolom `updated_at` — menggunakan `$timestamps = false`.

### 4.8 `users`

| Column | Type | Notes |
|--------|------|-------|
| id | bigint AI | |
| name | string(255) | |
| email | string(255) | Unique |
| password | string(255) | Hashed |
| is_admin | boolean(1) | Flag admin cepat |
| two_factor_secret | text, nullable | Fortify 2FA |
| two_factor_recovery_codes | text, nullable | |
| two_factor_confirmed_at | datetime, nullable | |
| email_verified_at | datetime, nullable | |
| remember_token | string(100), nullable | |
| timestamps | | |

**Relasi:** Spatie `model_has_roles`, `model_has_permissions`.

---

## 5. Enums

### `LicenseStatus`
- `Active` → label: *Aktif*
- `Suspended` → *Ditangguhkan*
- `Expired` → *Kedaluwarsa*
- `Revoked` → *Dicabut*
- Method: `isValid()` (return `true` hanya jika `Active`)

### `LicenseMode`
- `Online` → membutuhkan aktivasi online
- `Offline` → tanpa aktivasi
- `SemiOnline` → aktivasi opsional
- Method: `requiresActivation()` (return `true` untuk Online, SemiOnline)

### `ActivationRequestStatus`
- `Pending` → *Tertunda*
- `Approved` → *Disetujui*
- `Rejected` → *Ditolak*
- `Expired` → *Kedaluwarsa*
- Methods: `isPending()`, `isTerminal()`

### `SubscriptionStatus`
- `Active` → *Aktif*
- `PastDue` → *Jatuh Tempo*
- `Canceled` → *Dibatalkan*
- `Trialing` → *Masa Percobaan*
- `Incomplete`, `IncompleteExpired`
- Method: `isActive()` (return `true` untuk Active, Trialing)

---

## 6. REST API v1

**Base URL:** `/api/v1`

**Rate limit:** 60 request per menit

### 6.1 Aktivasi Perangkat

**`POST /api/v1/activate`**
Mendaftarkan perangkat baru pada lisensi.

```json
// Request
{
  "license_key": "ABCD-EFGH-IJKL-MNOP",
  "device": {
    "fingerprint": "sha256hash...",
    "name": "PC Dev",
    "platform": "windows",
    "platform_version": "10.0.0",
    "app_version": "1.0.0"
  }
}

// Response (approval needed)
{
  "success": true,
  "message": "Perangkat terdaftar, aktivasi diperlukan",
  "data": {
    "device_id": 1,
    "requires_approval": true,
    "activation_code": "A1B2C3D4",
    "expires_at": "2026-05-15T14:00:00+00:00"
  }
}

// Response (auto-activated - offline mode or within limit)
{
  "success": true,
  "message": "Perangkat berhasil diaktifkan",
  "data": {
    "device_id": 1,
    "offline_until": "2026-05-22T14:00:00+00:00"
  }
}
```

### 6.2 Verifikasi Aktivasi

**`GET /api/v1/verify/{key}/{fingerprint}`**
Verifikasi kode aktivasi untuk perangkat.

| Parameter | Type | Description |
|-----------|------|-------------|
| key | string | License key (format: `[A-Z0-9-]+`) |
| fingerprint | string | Hardware fingerprint |

**Query Params:** `?code=ACTIVATION_CODE`

Response `offline_until` dihitung dari `activated_at + 7 days` atau `last_seen_at + 7 days`.

### 6.3 Status Lisensi

**`GET /api/v1/status/{key}/{fingerprint}`**
Cek status lisensi dan aktivasi perangkat.

### 6.4 Validasi Lisensi

**`POST /api/v1/validate`**
Validasi lisensi untuk perangkat terdaftar.

```json
// Request
{
  "license_key": "ABCD-EFGH-IJKL-MNOP",
  "device": {
    "fingerprint": "sha256hash..."
  }
}

// Response
{
  "success": true,
  "message": "Lisensi valid",
  "data": {
    "valid": true,
    "status": "active",
    "license_key": "ABCD-****-****-MNOP",
    "product": "Laravel Monitor Pro",
    "expires_at": "2026-06-15",
    "cache_until": "2026-05-22"
  }
}
```

### 6.5 Cek Update

**`POST /api/v1/check-update`**
Periksa versi terbaru (placeholder — selalu return no update available).

```json
// Request
{
  "license_key": "ABCD-EFGH-IJKL-MNOP",
  "current_version": "1.0.0"
}
```

---

## 7. Services

### `GitHubService`
- **`fetchRepos()`** — Ambil semua repo dari `GITHUB_USERNAMES` (multiple users) via GitHub API, paginated 100/page
- **`listRepos()`** — Sama dengan fetchRepos tapi dengan cache 1 jam
- **`fetchReadme(string $fullName)`** — Ambil RAW README, bersihkan heading/badges/HTML, truncate 2000 chars
- **`syncProductMetadata(Product $product)`** — Update metadata GitHub dari API
- **`clearCache()`** — Hapus cache `github_repos`

### `LicenseKeyService`
- **`generate()`** — Generate key format `XXXX-XXXX-XXXX-XXXX`
- **`validateFormat(string $key)`** — Validasi format key
- **`mask(string $key)`** — Mask key: `ABCD-****-****-MNOP`

### `LicenseService`
- **`create(array $data)`** — Buat lisensi baru + audit log
- **`validate(License $license)`** — Validasi status & expired
- **`registerDevice(License $license, array $deviceData)`** — Daftarkan perangkat
- **`checkDeviceLimit(License $license)`** — Cek batas perangkat
- **`createActivationRequest(Device $device)`** — Buat kode aktivasi (30 menit)
- **`approveActivationRequest()`, `rejectActivationRequest()`** — Approve/reject
- **`suspend(License)`, `revoke(License)`, `restore(License)`** — Manajemen status
- **`verifyActivation(Device $device, string $code)`** — Verifikasi kode, fallback ke `last_seen_at` + 7 hari

---

## 8. Artisan Commands

| Command | Schedule | Description |
|---------|----------|-------------|
| `licenses:check-expired` | `->daily()` | Tandai lisensi kedaluwarsa + activation request expired |
| `licenses:notify-expiring` | `->daily()` | Log lisensi yang akan kedaluwarsa (default: 7 hari) |
| `github:sync-products` | `->daily()` | Sinkronisasi repositori GitHub → produk |

### `github:sync-products`
- Fetch semua repositori dari GitHub
- Auto-create produk baru untuk repo belum terdaftar
- Update deskripsi produk dengan konten README
- Update metadata GitHub (branch, URL, deskripsi)

---

## 9. Folio Pages (Frontend)

Semua halaman admin menggunakan Folio (file-based routing) + Volt (Livewire single-file components).

### 9.1 Route Map

| URL | Nama Route | Middleware | Deskripsi |
|-----|-----------|-----------|-----------|
| `/` | `home` | - | Welcome page |
| `/dashboard` | `dashboard` | auth | Dashboard stats |
| `/products` | `products.index` | check.admin | CRUD produk |
| `/products/create` | `products.create` | check.admin | Buat produk |
| `/products/{product}/edit` | `products.edit` | check.admin | Edit produk |
| `/plans` | `plans.index` | check.admin | CRUD paket |
| `/plans/create` | `plans.create` | check.admin | Buat paket |
| `/plans/{plan}/edit` | `plans.edit` | check.admin | Edit paket |
| `/licenses` | `licenses.index` | check.admin | Daftar lisensi |
| `/licenses/create` | `licenses.create` | check.admin | Buat lisensi |
| `/licenses/{license}/edit` | `licenses.edit` | check.admin | Edit lisensi |
| `/activation-requests` | `activation-requests.index` | check.admin | Approve/reject aktivasi |
| `/devices` | `devices.index` | check.admin | Daftar perangkat |
| `/users` | `users.index` | check.admin | Manajemen user |
| `/users/create` | - | check.admin | Buat user |
| `/users/{user}/edit` | - | check.admin | Edit user |
| `/roles` | `roles.index` | check.admin | Manajemen role |
| `/roles/create` | - | check.admin | Buat role |
| `/roles/{role}/edit` | - | check.admin | Edit role |
| `/audit-logs` | `audit-logs.index` | check.admin | Audit trail |
| `/settings/profile` | `profile.edit` | auth | Edit profil |
| `/settings/appearance` | `appearance.edit` | auth, verified | Tema |
| `/settings/security` | `security.edit` | auth, verified | 2FA & password |

### 9.2 Auth Pages (Fortify Views)
Semua halaman auth menggunakan Fortify views: login, register, forgot-password, reset-password, confirm-password, verify-email, two-factor-challenge.

### 9.3 Dashboard
- **Statistik:** Total products, total licenses, active licenses, pending activations, total devices
- **Quick Links:** Manage Products, View Licenses, Pending Requests

### 9.4 Products Page
- **Fitur:** Search, sort, pagination (10/page), status toggle (Active/Inactive), GitHub sync button, detail modal, delete confirmation
- **Sync GitHub** → auto-create products dari repositori + fetch README sebagai description

### 9.5 Licenses Page
- **Fitur:** Search by key, filter by status, pagination (15/page)
- **Kolom:** License Key, Product, User, Status (badge color), Devices (count/max), Expires At

### 9.6 Activation Requests Page
- **Fitur:** Filter by status, approve/reject pending requests
- **Kolom:** ID, License, Device, Status, Code, Actions (check/x-mark buttons)

---

## 10. Roles & Permissions

### 10.1 Roles

| Role | Description | Permissions |
|------|-------------|-------------|
| **admin** | Full akses | Semua permissions (15+) |
| **manager** | Manajemen operasional | view/create/edit licenses, products, devices, subscriptions, activation requests, users, reports |
| **support** | Support & monitoring | view/edit licenses, view devices, activation requests approval, view audit logs |
| **user** | User biasa | view licenses, view products |

### 10.2 Permissions (15 total)

| Permission | Admin | Manager | Support | User |
|-----------|:-----:|:-------:|:-------:|:----:|
| view licenses | ✓ | ✓ | ✓ | ✓ |
| create licenses | ✓ | ✓ | - | - |
| edit licenses | ✓ | ✓ | ✓ | - |
| delete licenses | ✓ | - | - | - |
| view products | ✓ | ✓ | - | ✓ |
| create products | ✓ | ✓ | - | - |
| edit products | ✓ | ✓ | - | - |
| delete products | ✓ | - | - | - |
| view devices | ✓ | ✓ | ✓ | - |
| view subscriptions | ✓ | ✓ | - | - |
| view activation requests | ✓ | ✓ | ✓ | - |
| approve activation requests | ✓ | ✓ | ✓ | - |
| view audit logs | ✓ | - | ✓ | - |
| view users | ✓ | ✓ | - | - |
| manage users | ✓ | - | - | - |
| manage roles | ✓ | - | - | - |
| view reports | ✓ | ✓ | - | - |
| manage settings | ✓ | - | - | - |

---

## 11. GitHub Integration

### 11.1 Konfigurasi
```env
GITHUB_TOKEN=ghp_xxxxx
GITHUB_USERNAMES=user1,user2   # comma-separated
```

### 11.2 Alur Sync
1. User klik "Sync GitHub" di halaman Products
2. `GitHubService::fetchRepos()` GET `/users/{username}/repos` untuk setiap username
3. Pagination 100 repo/halaman
4. Untuk setiap repo, `fetchReadme()` GET RAW README via `Accept: application/vnd.github.raw`
5. Auto-create `Product` untuk repo yang belum terdaftar
6. Gunakan konten README sebagai `description` (fallback: `repo['description']`)
7. Refresh cache

### 11.3 One-to-One Mapping
- 1 product = 1 repositori GitHub
- Mapping via `github_repo_id` (unique)
- Produk non-GitHub tetap bisa dibuat manual (tanpa github fields)

---

## 12. License Activation Flow

### Online Mode
```
Device → POST /api/v1/activate → Activation Request (Pending)
                                      ↓
                              Admin approves via UI
                                      ↓
                              Device → GET /api/v1/verify/{key}/{fp}?code=XXXX
                                      ↓
                              Offline grace period: 7 hari
```

### Semi-Online Mode
Sama seperti Online, tapi aktivasi tidak wajib — device bisa berfungsi dalam grace period 7 hari.

### Offline Mode
```
Device → POST /api/v1/activate → Instant activation (no approval)
                              ↓
                      Offline grace: 7 hari
```

### Grace Period
- Setelah aktivasi berhasil: `device.activated_at + 7 days` = offline_until
- Jika device sudah pernah terlihat: `device.last_seen_at + 7 days` (fallback)
- Setelah grace period habis: device harus re-activate

---

## 13. Audit Trail

Semua operasi licensing dicatat ke `audit_logs`:

| Action | Trigger |
|--------|---------|
| `created` | LicenseService::create() |
| `device_registered` | LicenseService::registerDevice() |
| `activation_request_created` | LicenseService::createActivationRequest() |
| `activation_approved` | LicenseService::approveActivationRequest() |
| `activation_rejected` | LicenseService::rejectActivationRequest() |
| `suspended` | LicenseService::suspend() |
| `revoked` | LicenseService::revoke() |
| `restored` | LicenseService::restore() |

Audit log menyimpan: action, entity_type, entity_id, user_id, old_values (json), new_values (json), ip_address, user_agent, created_at.

---

## 14. Seeders

### `RolePermissionSeeder`
- 15+ permissions
- 4 roles: admin, manager, support, user
- 1 super admin user: `admin@testing.com` / `password`

### `LicenseSeeder`
- 4 users (1 admin, 3 regular)
- 3 products (Laravel Monitor Pro, Analytics Dashboard, API Gateway) dengan GitHub repo data
- 6 subscription plans (Starter/Professional/Enterprise/LocalBasic/Premium/Team)
- Licenses dengan status & mode bervariasi
- Devices dengan berbagai platform
- Activation requests (pending + approved)
- Subscriptions dengan Stripe mock IDs

---

## 15. Security & Middleware

### Middleware Stack
- **Fortify:** Auth, verified, password.confirm
- **CheckAdminMiddleware:** Cek `$user->isAdmin()` → abort 403 jika bukan admin
- **Folio:** Default middleware `['auth', 'verified']` untuk semua halaman admin
- **Admin pages:** Tambahan `check.admin` middleware

### Rate Limiting
- Login: 5 request/menit per IP+email
- 2FA: 5 request/menit
- API: 60 request/menit

### Two-Factor Authentication
- Fortify TOTP (Google Authenticator)
- Recovery codes
- Confirm password untuk manage 2FA

---

## 16. Testing

### PHPUnit (21 file)

**Unit Tests:**
- `LicenseServiceTest` — Validasi logika license
- `LicenseKeyServiceTest` — Generate, format, mask
- `ExampleTest` — Placeholder

**Feature Tests:**
- Auth: Authentication, Registration, PasswordReset, PasswordConfirmation, EmailVerification, TwoFactorChallenge
- Settings: ProfileUpdate, Security
- API: LicenseValidation, LicenseActivation
- Teams: Team, TeamMember, TeamInvitation
- Integration: Dashboard, EndToEndFlow
- Products: ProductCreate

### Playwright E2E (5 spec files)
- Browser-based testing

### Menjalankan Tests
```bash
php artisan test --compact                          # Semua
php artisan test --compact tests/Feature/ProductCreateTest.php  # Satu file
php artisan test --compact --filter=test_name       # Filter
```

---

## 17. Environment Configuration

```env
APP_LOCALE=id                    # Bahasa Indonesia
APP_FALLBACK_LOCALE=id
APP_FAKER_LOCALE=id_ID

DB_CONNECTION=sqlite             # Development
SESSION_DRIVER=database          # Database session
CACHE_STORE=database             # Database cache
QUEUE_CONNECTION=database        # Database queue
```

---

## 18. GitHub Repositories Sync (Product List)

Repositori dari akun `devWebs01` yang di-sync sebagai produk:

| Repo | Product Name |
|------|-------------|
| devWebs01/laravel13-pos | Laravel Monitor Pro |
| devWebs01/NW-Coffe | Analytics Dashboard |
| *(plus 20+ repositori lain via sync)* | |

Semua repositori publik dari akun GitHub yang dikonfigurasi akan auto-import sebagai produk dengan deskripsi dari README.

---

## 19. Dependencies

### Production
- `laravel/framework` ^13.7
- `laravel/fortify` ^1.34 (auth)
- `laravel/folio` * (file-based routing)
- `livewire/livewire` ^4.1
- `livewire/volt` ^1.10 (single-file components)
- `livewire/flux` ^2 (UI components)
- `spatie/laravel-permission` ^7.4 (RBAC)
- `laravel/tinker` ^3.0

### Development
- `phpunit/phpunit` ^12.5
- `laravel/pint` ^1.27 (code style)
- `laravel/sail` ^1.53
- `mockery/mockery` ^1.6
- `fakerphp/faker` ^1.24
- `barryvdh/laravel-ide-helper` ^3.7
- `fruitcake/laravel-debugbar` ^4.2
- `laravel/pail` ^1.2.5 (log viewer)
- `laravel/boost` ^2.2
