# Laravel POS - Dokumentasi Sistem

**Versi:** 1.0.0  
**Framework:** Laravel 13  
**Stack:** Livewire 4 + Volt 1 + Flux UI 2 + TailwindCSS 4  
**Database:** MySQL (development), SQLite (default)  
**Timezone:** Asia/Jakarta  
**Lisensi:** MIT

---

## Daftar Isi

1. [Ikhtisar Sistem](#1-ikhtisar-sistem)
2. [Persyaratan Sistem](#2-persyaratan-sistem)
3. [Instalasi & Setup](#3-instalasi--setup)
4. [Struktur Proyek](#4-struktur-proyek)
5. [Arsitektur](#5-arsitektur)
6. [Database Schema](#6-database-schema)
7. [Model (Eloquent ORM)](#7-model-eloquent-orm)
8. [Autentikasi & Keamanan (Fortify)](#8-autentikasi--keamanan-fortify)
9. [Role & Permission (Spatie)](#9-role--permission-spatie)
10. [Routing (Folio + Web)](#10-routing-folio--web)
11. [Komponen Volt / Livewire](#11-komponen-volt--livewire)
12. [Halaman & Fitur](#12-halaman--fitur)
13. [Frontend Stack](#13-frontend-stack)
14. [Testing](#14-testing)
15. [Licensing (laravel-licensing-client)](#15-licensing)
16. [Environment Variables](#16-environment-variables)
17. [Seeding Data](#17-seeding-data)
18. [Panduan Pengguna](#18-panduan-pengguna)
19. [Pengembangan & Kontribusi](#19-pengembangan--kontribusi)

---

## 1. Ikhtisar Sistem

Laravel POS adalah sistem Point of Sale berbasis web untuk kedai kopi/cafe dengan fitur:

- **Manajemen Produk** - CRUD produk dengan kategori, stok, SKU, dan upload gambar
- **Kategori Produk** - Pengelompokan produk (Kopi & Espresso, Non-Coffee, Makanan, Minuman)
- **POS / Transaksi** - Keranjang belanja interaktif, multi-item, berbagai metode pembayaran
- **Laporan & Analitik** - Grafik penjualan harian, breakdown metode pembayaran, performa kategori, produk terlaris
- **Manajemen Pengguna** - Multi-level user (Super Admin, Pemilik, Kasir)
- **Role & Permission** - Kontrol akses berbasis peran
- **Setting Toko** - Konfigurasi profil toko
- **Cetak Struk** - Tampilan receipt ramah thermal printer
- **Dark Mode** - Tema light/dark/system

### Aktor Sistem

| Role | Deskripsi |
|------|-----------|
| **Super Admin** | Akses penuh ke semua fitur termasuk manajemen role & permission |
| **Pemilik** | Akses ke produk, kategori, transaksi, laporan, pengguna, dan settings. Tidak bisa mengelola role/permission |
| **Kasir** | Hanya bisa membuat transaksi dan mengedit profil sendiri |

---

## 2. Persyaratan Sistem

### Production
- PHP 8.3+
- Composer 2.x
- Database: MySQL 8.0+ / MariaDB 10.6+ / PostgreSQL 15+
- Node.js 20+ (untuk build asset)
- NPM 10+
- Web Server: Nginx / Apache

### Development (via Laravel Sail / Lerd)
- Docker Desktop / OrbStack
- PHP 8.3+
- Node.js 20+

### Daftar Dependency

#### Production Dependencies
| Package | Version | Fungsi |
|---------|---------|--------|
| `php` | ^8.3 | Runtime |
| `laravel/framework` | ^13.7 | Core Laravel |
| `laravel/folio` | ^1.1 | Page-based routing |
| `laravel/fortify` | ^1.34 | Authentication backend |
| `laravel/tinker` | ^3.0 | Artisan REPL |
| `livewire/livewire` | ^4.1 | Reactive components |
| `livewire/volt` | ^1.10 | Single-file components |
| `livewire/flux` | ^2.13.1 | UI component library |
| `spatie/laravel-permission` | ^7.4 | RBAC |
| `masterix21/laravel-licensing-client` | ^2.0 | License management |

#### Dev Dependencies
| Package | Version | Fungsi |
|---------|---------|--------|
| `phpunit/phpunit` | ^12.5 | Testing framework |
| `laravel/pint` | ^1.27 | Code style fixer |
| `laravel/sail` | ^1.53 | Docker dev env |
| `fakerphp/faker` | ^1.24 | Data faker |
| `mockery/mockery` | ^1.6 | Mocking |
| `nunomaduro/collision` | ^8.9 | Error handler |
| `laravel/pail` | ^1.2.5 | Log viewer |
| `laravel/boost` | ^2.2 | MCP server |
| `laravel/pao` | ^1.0.6 | Performance analyzer |

---

## 3. Instalasi & Setup

### 3.1 Clone & Install

```bash
git clone https://github.com/devWebs01/laravel13-pos laravel-pos
cd laravel-pos
composer install
npm install
```

### 3.2 Environment

```bash
cp .env.example .env
php artisan key:generate
```

### 3.3 Database

```bash
# SQLite (default)
touch database/database.sqlite
php artisan migrate --seed

# MySQL
# Edit .env: DB_CONNECTION=mysql, DB_HOST, DB_PORT, DB_DATABASE, DB_USERNAME, DB_PASSWORD
php artisan migrate --seed
```

### 3.4 Storage Link

```bash
php artisan storage:link
```

### 3.5 Build Assets

```bash
npm run build
# atau untuk development:
composer run dev
```

### 3.6 Licensing Setup

```bash
php artisan vendor:publish --tag="licensing-client-config"
```

Tambahkan ke `.env`:

```env
LICENSING_SERVER_URL=https://monitor.test
LICENSING_PUBLIC_KEY=your-base64-ed25519-public-key
LICENSING_KEY=LIC-XXXX-XXXX-XXXX-XXXX
```

### 3.7 Development Server

```bash
composer run dev
```

Perintah ini menjalankan secara bersamaan: `php artisan serve` + queue worker + log viewer (pail) + Vite dev server.

### 3.8 User Default (Seeder)

| Nama         | Email               | Password | Role                  |
| --------------| ---------------------| ----------| -----------------------|
| Admin POS    | admin@testing.com   | password | super-admin + pemilik |
| pemilik Toko | pemilik@testing.com | password | pemilik               |
| Kasir Toko   | kasir@testing.com   | password | kasir                 |

---

## 4. Struktur Proyek

```
laravel-pos/
├── app/
│   ├── Actions/
│   │   └── Fortify/
│   │       ├── CreateNewUser.php        # Validasi & create user
│   │       └── ResetUserPassword.php    # Reset password
│   ├── Concerns/
│   │   ├── PasswordValidationRules.php  # Aturan validasi password
│   │   └── ProfileValidationRules.php   # Aturan validasi profil
│   ├── Http/
│   │   └── Controllers/
│   │       └── Controller.php           # Base controller (abstrak)
│   ├── Livewire/
│   │   └── Actions/
│   │       └── Logout.php              # Aksi logout
│   ├── Models/
│   │   ├── Category.php                # Kategori produk
│   │   ├── Product.php                 # Produk
│   │   ├── Setting.php                 # Setting toko
│   │   ├── Transaction.php             # Transaksi
│   │   ├── TransactionItem.php         # Item transaksi
│   │   └── User.php                    # Pengguna
│   └── Providers/
│       ├── AppServiceProvider.php
│       ├── FolioServiceProvider.php    # Registrasi Folio pages + middleware
│       ├── FortifyServiceProvider.php  # Konfigurasi Fortify
│       └── VoltServiceProvider.php     # Registrasi Volt components
├── config/
│   ├── app.php
│   ├── auth.php
│   ├── fortify.php
│   ├── licensing-client.php            # Konfigurasi licensing
│   └── permission.php                  # Konfigurasi Spatie Permission
├── database/
│   ├── factories/                       # Model factories
│   ├── migrations/                      # Database migrations (13 file)
│   └── seeders/                         # Database seeders (6 file)
├── resources/
│   ├── views/
│   │   ├── components/                  # Blade components (6)
│   │   ├── flux/                        # Flux UI overrides (21)
│   │   ├── layouts/                     # Layouts (5)
│   │   ├── pages/                       # Folio pages (all business logic)
│   │   ├── partials/                    # Partial views (2)
│   │   ├── dashboard.blade.php
│   │   └── welcome.blade.php
├── routes/
│   ├── console.php
│   ├── settings.php                     # Settings routes
│   └── web.php                          # Web routes
├── tests/
│   ├── Feature/                         # Feature tests (14 file)
│   └── Unit/                            # Unit tests (1 file)
├── composer.json
├── package.json
├── vite.config.js
└── pint.json
```

---

## 5. Arsitektur

### 5.1 Pola Arsitektur

Sistem menggunakan arsitektur **Livewire-First** dengan routing berbasis file (Folio).

```
[Browser] ←→ [Livewire (Volt)] ←→ [Eloquent Models] ←→ [Database]
                     ↓
              [Fortify Auth]
              [Spatie Permission]
```

**Karakteristik:**
- **Tidak ada controller tradisional** - Semua logika bisnis ada di Volt components dalam file Folio
- **Single-File Components** - PHP logic + Blade template dalam satu file (Volt)
- **File-Based Routing** - URL ditentukan oleh struktur folder `resources/views/pages/`
- **Stateful UI** - Livewire menjaga state di server, UI reaktif via AJAX

### 5.2 Alur Request

```
Request Masuk
    ↓
Folio Routing (cocokkan file di pages/)
    ↓
Middleware: auth, verified, license
    ↓
Volt Component (logic + render)
    ↓
Blade Template (Flux UI + Tailwind)
    ↓
Response HTML
```

### 5.3 Pola Data Flow

```
User Action (click/input)
    ↓
wire:click / wire:model
    ↓
Volt Action (PHP method)
    ↓
Eloquent Query / Mutation
    ↓
Database
    ↓
Re-render (Livewire)
    ↓
DOM Update (partial)
```

---

## 6. Database Schema

### 6.1 Entity Relationship Diagram (Text)

```
users
  ├── id (PK)
  ├── name
  ├── email (unique)
  ├── password
  ├── two_factor_secret
  ├── two_factor_recovery_codes
  └── two_factor_confirmed_at

categories
  ├── id (PK)
  ├── name (unique)
  ├── slug (unique, indexed)
  └── description

products
  ├── id (PK)
  ├── category_id (FK → categories.id)
  ├── name
  ├── slug (unique)
  ├── sku (unique, indexed)
  ├── price (decimal 12,2)
  ├── stock (integer)
  ├── is_unlimited_stock (boolean)
  ├── image (nullable)
  ├── description
  └── is_active (boolean, indexed)

transactions
  ├── id (PK)
  ├── user_id (FK → users.id)
  ├── customer
  ├── invoice_number (unique)
  ├── total_amount (decimal 12,2)
  ├── paid_amount (decimal 12,2)
  ├── change_amount (decimal 12,2)
  ├── payment_method
  ├── notes
  └── created_at (indexed)

transaction_items
  ├── id (PK)
  ├── transaction_id (FK → transactions.id, cascade)
  ├── product_id (FK → products.id, cascade)
  ├── quantity
  ├── unit_price (decimal 12,2)
  └── subtotal (decimal 12,2)

settings
  ├── id (PK)
  ├── store_name
  ├── store_address
  ├── store_phone
  ├── store_email
  └── receipt_footer

--- Spatie Permission Tables ---
permissions
  ├── id (PK)
  ├── name (unique per guard)
  └── guard_name

roles
  ├── id (PK)
  ├── name (unique per guard)
  └── guard_name

model_has_roles
  ├── role_id (FK)
  ├── model_type
  └── model_id

model_has_permissions
  ├── permission_id (FK)
  ├── model_type
  └── model_id

role_has_permissions
  ├── permission_id (FK)
  └── role_id (FK)
```

### 6.2 Detail Table

#### `users`
| Kolom | Type | Keterangan |
|-------|------|------------|
| id | bigint unsigned, PK | Auto increment |
| name | varchar(255) | Nama lengkap |
| email | varchar(255) | Email, unique |
| email_verified_at | timestamp, nullable | Verifikasi email |
| password | varchar(255) | Hash password |
| two_factor_secret | text, nullable | Secret 2FA |
| two_factor_recovery_codes | text, nullable | Recovery codes 2FA |
| two_factor_confirmed_at | timestamp, nullable | Konfirmasi 2FA |
| remember_token | varchar(100), nullable | Remember me |
| timestamps | created_at, updated_at | |

#### `categories`
| Kolom | Type | Keterangan |
|-------|------|------------|
| id | bigint unsigned, PK | |
| name | varchar(100), unique | Nama kategori |
| slug | varchar(120), unique, indexed | Slug untuk URL |
| description | text, nullable | Deskripsi |
| timestamps | | |

#### `products`
| Kolom | Type | Keterangan |
|-------|------|------------|
| id | bigint unsigned, PK | |
| category_id | bigint unsigned, FK | Relasi ke categories.id |
| name | varchar(200) | Nama produk |
| slug | varchar(220), unique | Slug untuk URL |
| sku | varchar(50), unique, indexed | Stock Keeping Unit |
| price | decimal(12,2), default 0 | Harga jual |
| stock | integer, default 0 | Stok |
| is_unlimited_stock | boolean, default false | Stok tak terbatas |
| image | varchar(255), nullable | Path gambar |
| description | text, nullable | Deskripsi produk |
| is_active | boolean, default true, indexed | Status aktif |
| timestamps | | |

#### `transactions`
| Kolom | Type | Keterangan |
|-------|------|------------|
| id | bigint unsigned, PK | |
| user_id | bigint unsigned, FK | Relasi ke users.id |
| customer | varchar(255) | Nama pelanggan |
| invoice_number | varchar(50), unique | Nomor invoice (auto) |
| total_amount | decimal(12,2) | Total transaksi |
| paid_amount | decimal(12,2) | Jumlah dibayar |
| change_amount | decimal(12,2), default 0 | Kembalian |
| payment_method | varchar(20), default 'cash' | Cash/Transfer/Debit Card/Credit Card |
| notes | text, nullable | Catatan |
| timestamps | created_at indexed | |

#### `transaction_items`
| Kolom | Type | Keterangan |
|-------|------|------------|
| id | bigint unsigned, PK | |
| transaction_id | bigint unsigned, FK (cascade) | Relasi ke transactions.id |
| product_id | bigint unsigned, FK (cascade) | Relasi ke products.id |
| quantity | integer | Jumlah |
| unit_price | decimal(12,2) | Harga saat transaksi |
| subtotal | decimal(12,2) | quantity * unit_price |
| timestamps | | |

#### `settings`
| Kolom | Type | Keterangan |
|-------|------|------------|
| id | bigint unsigned, PK | |
| store_name | varchar(255) | Nama toko |
| store_address | text, nullable | Alamat toko |
| store_phone | varchar(255), nullable | Telepon |
| store_email | varchar(255), nullable | Email toko |
| receipt_footer | text, nullable | Footer struk |
| timestamps | | |

---

## 7. Model (Eloquent ORM)

### `App\Models\User`

```php
class User extends Authenticatable
{
    use HasFactory, Notifiable, HasRoles, TwoFactorAuthenticatable;

    // fillable: name, email, password
    // hidden: password, two_factor_secret, two_factor_recovery_codes, remember_token
    // casts: email_verified_at => datetime, password => hashed

    public function transactions(): HasMany;
    public function initials(): string;  // Ambil inisial dari nama
}
```

### `App\Models\Category`

```php
class Category extends Model
{
    use HasFactory;

    // fillable: name, slug, description

    public function products(): HasMany;
}
```

### `App\Models\Product`

```php
class Product extends Model
{
    use HasFactory;

    // fillable: category_id, name, slug, sku, price, stock, image, description, is_active, is_unlimited_stock
    // casts: is_active => boolean, is_unlimited_stock => boolean, price => float

    public function category(): BelongsTo;
    public function transactionItems(): HasMany;
    public function getImageUrlAttribute(): string;  // URL gambar dengan fallback
}
```

### `App\Models\Transaction`

```php
class Transaction extends Model
{
    use HasFactory;

    // fillable: customer, invoice_number, total_amount, paid_amount, change_amount, payment_method, notes
    // casts: total_amount => float, paid_amount => float, change_amount => float

    public function user(): BelongsTo;
    public function items(): HasMany;
}
```

### `App\Models\TransactionItem`

```php
class TransactionItem extends Model
{
    use HasFactory;

    // fillable: transaction_id, product_id, quantity, unit_price, subtotal
    // casts: unit_price => decimal:2, subtotal => decimal:2

    public function transaction(): BelongsTo;
    public function product(): BelongsTo;
}
```

### `App\Models\Setting`

```php
class Setting extends Model
{
    // fillable: store_name, store_address, store_phone, store_email, receipt_footer
}
```

---

## 8. Autentikasi & Keamanan (Fortify)

### 8.1 Konfigurasi Fortify (`config/fortify.php`)

| Setting | Value |
|---------|-------|
| Guard | web |
| Username | email |
| Lowercase | true |
| Home | /dashboard |

### 8.2 Fitur Autentikasi

| Fitur | Status |
|-------|--------|
| Login | ✅ Aktif |
| Register | ❌ Nonaktif (hanya admin yang bisa buat user) |
| Password Reset | ✅ Aktif |
| Email Verification | ❌ Nonaktif (tampilan tetap ada) |
| 2FA (TOTP) | ✅ Aktif (QR code, OTP, recovery codes) |
| Password Confirmation | ✅ Aktif (untuk 2FA setup) |

### 8.3 Rate Limiting

- Login: 5 percobaan per menit per email+IP
- 2FA Challenge: 5 percobaan per menit per session

### 8.4 Fortify Actions

**CreateNewUser** (`app/Actions/Fortify/CreateNewUser.php`):
- Menggunakan `PasswordValidationRules` dan `ProfileValidationRules`
- Validasi: name (required, string, max:255), email (required, email, unique), password (default rules + confirmed)

**ResetUserPassword** (`app/Actions/Fortify/ResetUserPassword.php`):
- Menggunakan `PasswordValidationRules`
- Validasi password baru, update password

### 8.5 Validation Rules

**PasswordValidationRules**:
- `passwordRules()`: required, string, Password::default(), confirmed
- `currentPasswordRules()`: required, string, current_password

**ProfileValidationRules**:
- `nameRules()`: required, string, max:255
- `emailRules()`: required, string, email, max:255, unique (dengan optional ignore ID)

### 8.6 Two-Factor Authentication

- **Enable**: Generate QR code + manual entry key, konfirmasi dengan OTP
- **Disable**: Nonaktifkan 2FA
- **Recovery Codes**: View & regenerate codes (10 codes)
- Menggunakan Fortify `TwoFactorAuthenticatable` trait pada User model

### 8.7 Logout

`App\Livewire\Actions\Logout.php` - Invokable class, menghapus session dan regenerasi token.

---

## 9. Role & Permission (Spatie)

### 9.1 Permission Groups

| Group | Permissions |
|-------|-------------|
| users | users.view, users.create, users.edit, users.delete |
| roles | roles.view, roles.create, roles.edit, roles.delete |
| permissions | permissions.view, permissions.create, permissions.edit, permissions.delete |
| products | products.view, products.create, products.edit, products.delete |
| categories | categories.view, categories.create, categories.edit, categories.delete |
| transactions | transactions.view, transactions.create, transactions.edit, transactions.delete |
| reports | reports.view |
| settings | settings.store, settings.profile, settings.security |

**Total**: 31 permissions

### 9.2 Role Definitions

| Role | Permissions |
|------|-------------|
| **super-admin** | Semua permission (31) |
| **pemilik** | Semua kecuali roles.* dan permissions.* (23) |
| **kasir** | transactions.*, settings.profile (5) |

### 9.3 Implementasi di Frontend

Permission dicek di dua tempat:

1. **Sidebar** - Menu hanya tampil jika user punya permission terkait
2. **Tombol Aksi** - Tombol Create/Edit/Delete hanya tampil jika user punya permission terkait

Cara pengecekan di Blade:

```blade
@can('products.create')
    <flux:button>Create Product</flux:button>
@endcan
```

### 9.4 Permission Cache

- Driver: default cache
- Key: `spatie.permission.cache`
- Lifetime: 24 jam (configurable)
- Reset: `php artisan permission:cache-reset`

---

## 10. Routing (Folio + Web)

### 10.1 Web Routes (`routes/web.php`)

```php
Route::view('/', 'pages.auth.login');  // Login page (name: home)

Route::middleware(['auth', 'verified', 'license'])->group(function () {
    Route::view('dashboard', 'dashboard');  // Dashboard
});

require __DIR__ . '/settings.php';
```

### 10.2 Settings Routes (`routes/settings.php`)

```php
Route::middleware(['auth', 'license'])->group(function () {
    Route::redirect('settings', 'settings/profile');
    Route::livewire('settings/profile', 'pages::settings.profile');
});

Route::middleware(['auth', 'verified', 'license'])->group(function () {
    Route::livewire('settings/appearance', 'pages::settings.appearance');
    Route::livewire('settings/security', 'pages::settings.security');
});
```

### 10.3 Folio Auto-Routes

Folio secara otomatis mendaftarkan route berdasarkan file di `resources/views/pages/`:

| URL | File | Middleware |
|-----|------|------------|
| `/` | `pages/auth/login.blade.php` | - |
| `/register` | `pages/auth/register.blade.php` | - |
| `/forgot-password` | `pages/auth/forgot-password.blade.php` | - |
| `/reset-password/{token}` | `pages/auth/reset-password.blade.php` | - |
| `/verify-email` | `pages/auth/verify-email.blade.php` | - |
| `/two-factor-challenge` | `pages/auth/two-factor-challenge.blade.php` | - |
| `/confirm-password` | `pages/auth/confirm-password.blade.php` | - |
| `/dashboard` | `dashboard.blade.php` | auth, verified, license |
| `/products` | `pages/products/index.blade.php` | license |
| `/products/create` | `pages/products/create.blade.php` | license |
| `/products/{product}` | `pages/products/[Product].blade.php` | license |
| `/categories` | `pages/categories/index.blade.php` | license |
| `/transactions` | `pages/transactions/index.blade.php` | license |
| `/transactions/create` | `pages/transactions/create.blade.php` | license |
| `/transactions/{transaction}` | `pages/transactions/[Transaction].blade.php` | license |
| `/transactions/{transaction}/receipt` | `pages/transactions/[Transaction]/receipt.blade.php` | - |
| `/users` | `pages/users/index.blade.php` | license |
| `/users/create` | `pages/users/create.blade.php` | license |
| `/users/{user}` | `pages/users/[User].blade.php` | license |
| `/roles` | `pages/roles/index.blade.php` | license |
| `/roles/create` | `pages/roles/create.blade.php` | license |
| `/roles/{role}` | `pages/roles/[Role].blade.php` | license |
| `/permissions` | `pages/permissions/index.blade.php` | license |
| `/permissions/create` | `pages/permissions/create.blade.php` | license |
| `/permissions/{permission}` | `pages/permissions/[Permission].blade.php` | license |
| `/reports` | `pages/reports/index.blade.php` | license |
| `/settings/store` | `pages/settings/store.blade.php` | license |

**Catatan**: Folio ServiceProvider menerapkan middleware `license` ke semua halaman Folio melalui Folio::path()->middleware().

### 10.4 Model Binding

Folio menggunakan bracket notation untuk model binding:
- `[Product].blade.php` → Binding Product model (slug key)
- `[Transaction].blade.php` → Binding Transaction model
- `[User].blade.php` → Binding User model
- `[Role].blade.php` → Binding Role (Spatie) model
- `[Permission].blade.php` → Binding Permission (Spatie) model

### 10.5 Console Routes

`routes/console.php` - Hanya berisi command `inspire` bawaan Laravel.

---

## 11. Komponen Volt / Livewire

### 11.1 Functional Volt Components

Komponen menggunakan sintaks fungsional `use function Livewire\Volt\...`:

#### Dashboard (`dashboard.blade.php`)
- **Computed Properties**:
  - `revenueToday`: Total revenue hari ini
  - `transactionsToday`: Jumlah transaksi hari ini
  - `lowStockProducts`: Produk dengan stok < 5
  - `revenueCurrentMonth`: Revenue bulan ini
  - `revenueLastMonth`: Revenue bulan lalu (untuk perbandingan)
  - `chartData`: Data 7 hari untuk grafik ApexCharts
  - `recentTransactions`: 5 transaksi terakhir
  - `topProducts`: 5 produk terlaris

#### Products Pages
- **Index**: Search, sort, pagination (10/page), CRUD via modal, filter stok
- **Create**: Auto-slug, auto-SKU (jika kosong), upload file image
- **Edit**: Sama seperti create, dengan data existing

#### Categories Pages
- **Index**: Search inline, inline CRUD via modal, 10/page

#### Transactions Pages
- **Index**: Search invoice, sort, detail modal, delete confirmation, 10/page
- **Create**: Full POS cart system, kategori filter, search produk, stock-aware
- **Edit**: Edit existing transaction dengan cart system

#### POS Cart System (transactions/create)
```
Komponen Keranjang:
├── Input Nama Pelanggan
├── Filter Kategori
├── Search Produk
├── Grid Produk (dengan stok)
├── Keranjang:
│   ├── List item (nama, qty, harga, subtotal, +, -, hapus)
│   ├── Total
│   ├── Input Bayar (otomatis hitung kembalian)
│   ├── Pilih Metode Bayar
│   ├── Catatan
│   └── Tombol Simpan
└── Aksi: addItem, incrementQty, decrementQty, removeItem
```

#### Reports Pages
- **Date Range Filtering**: Pilih tanggal awal dan akhir
- **4 Charts** (ApexCharts):
  1. Revenue harian (area chart)
  2. Metode pembayaran (donut chart)
  3. Per Kategori (donut chart)
  4. Produk terlaris (horizontal bar chart)
- **Summary Cards**: Total revenue, total transaksi, rata-rata per transaksi
- **Computed Properties**: Filter laporan berdasarkan date range

#### Receipt Page
- **Thermal Printer Friendly** - `@media print` untuk kertas 80mm
- Tombol Print + Back

### 11.2 Class-based Volt Components (⚡ prefix)

Menggunakan `new class extends Component` syntax:

#### Profile (`settings/⚡profile.blade.php`)
- Update name & email
- Resend email verification
- Include `delete-user-form`

#### Security (`settings/⚡security.blade.php`)
- Update password (current + new)
- 2FA: Enable/disable, QR code display, OTP verification
- Include `two-factor-setup-modal`

#### Appearance (`settings/⚡appearance.blade.php`)
- Theme toggle: Light / Dark / System
- Menggunakan `$flux.appearance`

#### Delete User Form (`settings/⚡delete-user-form.blade.php`)
- Trigger delete modal

#### Delete User Modal (`settings/⚡delete-user-modal.blade.php`)
- Confirm password, delete account

#### Two-Factor Setup Modal (`settings/⚡two-factor-setup-modal.blade.php`)
- QR code display
- Manual entry key
- OTP verification input

#### Recovery Codes (`settings/two-factor/⚡recovery-codes.blade.php`)
- View recovery codes
- Regenerate codes button

### 11.3 PHP Livewire Actions

**`App\Livewire\Actions\Logout.php`**:

```php
class Logout
{
    public function __invoke(): void
    {
        Auth::logoutCurrentDevice();
        Session::invalidate();
        Session::regenerateToken();
        $this->redirect('/');
    }
}
```

---

## 12. Halaman & Fitur

### 12.1 Dashboard (`/dashboard`)
![Dashboard]
- **KPI Cards**: Revenue Today, Transactions Today, Low Stock Alerts
- **Chart**: 7-day sales trend (ApexCharts area chart)
- **Recent Transactions**: 5 transaksi terakhir
- **Top Products**: 5 produk terlaris

### 12.2 Produk (`/products`)
- **List**: Tabel dengan search, sort by nama/harga/stok
- **Create Modal**: Form dengan name, category, price, stock, image upload
- **Edit Modal**: Edit data produk
- **Delete**: Konfirmasi hapus
- **Indikator**: Stok rendah (oranye), unlimited stock (badge), tidak aktif (strikethrough)

### 12.3 Kategori (`/categories`)
- **List**: Tabel dengan search inline
- **Create**: Modal form nama + deskripsi
- **Edit**: Modal edit
- **Delete**: Konfirmasi

### 12.4 Transaksi (`/transactions`)
- **List**: Search invoice, filter, sort
- **Detail**: Modal detail transaksi (items, customer, payment info)
- **Delete**: Konfirmasi (hanya jika punya permission delete)

### 12.5 POS / Buat Transaksi (`/transactions/create`)
- **POS Interface**: Grid produk, filter kategori, search
- **Keranjang**: Tambah/kurang item, lihat subtotal
- **Pembayaran**: Input jumlah bayar, hitung otomatis kembalian
- **Metode Bayar**: Cash, Transfer, Debit Card, Credit Card
- **Invoice**: Auto-generate `INV-YYYYMMDDHHmmss-RANDOM`

### 12.6 Struk (`/transactions/{id}/receipt`)
- Tampilan struk untuk thermal printer (80mm paper)
- Tombol print browser
- Informasi toko dari settings

### 12.7 Laporan (`/reports`)
- **Filter**: Date range picker
- **4 Grafik**: Revenue trend, payment method, category breakdown, top products
- **Summary**: Total revenue, total transactions, average per transaction

### 12.8 Pengguna (`/users`)
- **List**: Tabel dengan role badges
- **Create**: Form nama, email, password, pilih roles
- **Edit**: Edit data, ganti password (opsional), ubah roles

### 12.9 Role (`/roles`)
- **List**: Tabel dengan permission count badges
- **Create**: Nama role + checkboxes permission
- **Edit**: Update permissions

### 12.10 Permission (`/permissions`)
- **List**: Guardian name + permission name
- **Create**: Nama permission
- **Edit**: Edit nama

### 12.11 Setting Toko (`/settings/store`)
- Form: Nama toko, alamat, telepon, email, footer struk

### 12.12 Profile (`/settings/profile`)
- Update nama & email
- Hapus akun (dengan confirm password)

### 12.13 Security (`/settings/security`)
- Ganti password
- 2FA setup (QR code, OTP, recovery codes)

### 12.14 Appearance (`/settings/appearance`)
- Theme: Light, Dark, System

---

## 13. Frontend Stack

### 13.1 Teknologi

| Teknologi | Versi | Fungsi |
|-----------|-------|--------|
| TailwindCSS | 4 | Utility CSS framework |
| Flux UI | 2.13 | Livewire UI component library |
| ApexCharts | CDN | Charting library |
| Instrument Sans | CDN (Bunny) | Font utama |
| Vite | 8 | Build tool |
| Lucide Icons | via Flux | Icon set |

### 13.2 Flux UI Components

Flux UI menyediakan komponen-komponen yang sudah di-override di `resources/views/flux/`:

| Kategori | Components |
|----------|------------|
| Navigasi | sidebar, navbar, navlist, navmenu, menu, breadcrumbs |
| Form | input, select, checkbox, radio, otp |
| Data | badge, card, table (bawaan) |
| Feedback | modal, toast, tooltip, skeleton, callout |
| Lainnya | avatar, button, icon |

### 13.3 Custom Override

**`flux/navlist/group.blade.php`** - Accordion-style navlist group dengan ikon chevron.

### 13.4 Layout Structure

```
layouts/
├── app.blade.php
│   └── app/sidebar.blade.php
│       ├── Sidebar (logo, navigation, user menu)
│       └── Main Content Area
└── auth.blade.php
    └── auth/simple.blade.php
        └── Centered auth form
```

### 13.5 Theme Modes

- **Light**: Default light theme
- **Dark**: Dark theme via Tailwind dark variant
- **System**: Follow OS preference via `prefers-color-scheme`

Flux appearance diatur via `$flux.appearance` di Volt component.

### 13.6 Charts (ApexCharts)

ApexCharts di-load via CDN (`<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>`).

Digunakan di:
- **Dashboard**: 7-day revenue trend (area chart)
- **Reports**: 4 charts (area, donut x2, horizontal bar)

Render dilakukan di Alpine.js `x-init` dalam Volt component.

---

## 14. Testing

### 14.1 Test Structure

```
tests/
├── Feature/
│   ├── Auth/
│   │   ├── AuthenticationTest.php   (5 tests)
│   │   ├── EmailVerificationTest.php (2 tests)
│   │   ├── PasswordConfirmationTest.php (2 tests)
│   │   ├── PasswordResetTest.php    (2 tests)
│   │   ├── RegistrationTest.php     (2 tests)
│   │   └── TwoFactorChallengeTest.php (2 tests)
│   ├── Settings/
│   │   ├── ProfileUpdateTest.php    (4 tests)
│   │   └── SecurityTest.php         (3 tests)
│   ├── CategoriesTest.php           (11 tests)
│   ├── DashboardTest.php            (2 tests)
│   ├── ExampleTest.php              (1 test)
│   ├── ProductsTest.php             (10 tests)
│   ├── ReportsTest.php              (4 tests)
│   └── TransactionsTest.php         (12 tests)
└── Unit/
    └── ExampleTest.php              (1 test)
```

**Total**: ~63 tests

### 14.2 Test Configuration (`phpunit.xml`)

```xml
<php>
    <env name="APP_ENV" value="testing"/>
    <env name="DB_CONNECTION" value="sqlite"/>
    <env name="DB_DATABASE" value=":memory:"/>
    <env name="CACHE_STORE" value="array"/>
    <env name="SESSION_DRIVER" value="array"/>
    <env name="QUEUE_CONNECTION" value="sync"/>
    <env name="MAIL_MAILER" value="array"/>
</php>
```

### 14.3 Running Tests

```bash
# Semua test
php artisan test --compact

# Test spesifik file
php artisan test --compact tests/Feature/TransactionsTest.php

# Filter test name
php artisan test --compact --filter=test_can_create_transaction
```

### 14.4 Test Coverage

| Area | Test Files | Test Count |
|------|-----------|------------|
| Auth | 6 | 15 |
| Settings | 2 | 7 |
| Categories | 1 | 11 |
| Products | 1 | 10 |
| Transactions | 1 | 12 |
| Reports | 1 | 4 |
| Dashboard | 1 | 2 |
| Example | 2 | 2 |

### 14.5 Test Patterns

- **Database Transactions**: Setiap test menggunakan `RefreshDatabase` atau `DatabaseTransactions` trait
- **Factories**: Menggunakan model factories untuk data setup
- **Acting As**: Login user dengan `$this->actingAs($user)`
- **Permission Tests**: Setup permission di `setUp()` method

Contoh test:

```php
public function test_can_create_product(): void
{
    $user = User::factory()->create();
    $user->assignRole('super-admin');
    $category = Category::factory()->create();

    $this->actingAs($user);
    
    Livewire::test(CreateProduct::class)
        ->set('name', 'Kopi Baru')
        ->set('category_id', $category->id)
        ->set('price', 25000)
        ->call('save')
        ->assertOk();
    
    $this->assertDatabaseHas('products', ['name' => 'Kopi Baru']);
}
```

### 14.6 Base TestCase

```php
class TestCase extends BaseTestCase
{
    protected function skipUnlessFortifyHas(string $feature): void
    {
        if (! Features::enabled($feature)) {
            $this->markTestSkipped("Fortify feature [$feature] not enabled.");
        }
    }
}
```

---

## 15. Licensing

### 15.1 Package

**masterix21/laravel-licensing-client** v2.0.0

Sistem menggunakan PASETO v4 tokens dengan tanda tangan Ed25519 untuk validasi lisensi secara offline.

### 15.2 Konfigurasi

File: `config/licensing-client.php`

```php
'server_url' => env('LICENSING_SERVER_URL'),
'license_key' => env('LICENSING_KEY'),
'public_key' => env('LICENSING_PUBLIC_KEY'),
'cache' => ['enabled' => true, 'store' => 'file', 'ttl' => 3600],
'heartbeat' => ['enabled' => true, 'interval' => 3600],
'grace_period_days' => 7,
'timeout' => 30,
'storage_path' => storage_path('app/licensing'),
```

### 15.3 Middleware

Middleware `license` terdaftar dan diterapkan ke:
- Semua Folio pages (via FolioServiceProvider)
- Route group `auth`, `verified` di web.php
- Route group `auth`, `verified` di settings.php

**Route yang di-exclude**:
- login, register, password/*, licensing/*

**Alur Middleware**:
1. Cek apakah route di-exclude
2. Validasi token offline
3. Jika valid, cek `force_online_after` → refresh jika perlu
4. Jika token invalid, coba refresh dari server
5. Jika refresh gagal, cek grace period
6. Jika server unreachable, mulai grace period
7. Jika server reachable dan tidak ada lisensi valid, return 403

### 15.4 Commands

```bash
php artisan license:activate [key]   # Aktivasi lisensi
php artisan license:validate         # Validasi lisensi
php artisan license:info             # Info lisensi
php artisan license:refresh          # Refresh token
php artisan license:deactivate       # Deaktivasi
```

### 15.5 Facade Methods

```php
use LucaLongo\LaravelLicensingClient\Facades\LaravelLicensingClient;

LaravelLicensingClient::isValid();
LaravelLicensingClient::activate('LIC-XXXX');
LaravelLicensingClient::validate();    // throw on invalid
LaravelLicensingClient::getLicenseInfo();
LaravelLicensingClient::isExpiringSoon(7);
LaravelLicensingClient::requiresOnlineRefresh();
LaravelLicensingClient::refresh();
LaravelLicensingClient::deactivate('LIC-XXXX');
LaravelLicensingClient::isServerHealthy();
```

---

## 16. Environment Variables

```env
# App
APP_NAME=Laravel
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=https://laravel-pos.test
APP_LOCALE=id
APP_FALLBACK_LOCALE=id
APP_FAKER_LOCALE=id_ID

# Database
DB_CONNECTION=mysql      # or sqlite
DB_HOST=lerd-mysql
DB_PORT=3306
DB_DATABASE=laravel_pos
DB_USERNAME=root
DB_PASSWORD=lerd

# Session
SESSION_DRIVER=database
SESSION_LIFETIME=120

# Cache
CACHE_STORE=database

# Queue
QUEUE_CONNECTION=database

# Mail
MAIL_MAILER=smtp
MAIL_HOST=lerd-mailpit
MAIL_PORT=1025

# Licensing
LICENSING_SERVER_URL=https://monitor.test
LICENSING_PUBLIC_KEY=
LICENSING_KEY=
```

---

## 17. Seeding Data

### 17.1 Seeder Order

```php
DatabaseSeeder::run()
  ├── RolePermissionSeeder   # Create roles & permissions
  ├── Create users           # 3 default users
  ├── CategorySeeder         # 5 categories
  ├── ProductSeeder          # 34 products
  ├── TransactionSeeder      # 30 transactions
  └── SettingSeeder          # Store settings
```

### 17.2 Categories (CategorySeeder)

| # | Name | Slug |
|---|------|------|
| 1 | Kopi & Espresso | kopi-&-espresso |
| 2 | Non-Coffee | non-coffee |
| 3 | Makanan Ringan | makanan-ringan |
| 4 | Makanan Berat | makanan-berat |
| 5 | Minuman Segar | minuman-segar |

### 17.3 Products (ProductSeeder)

34 produk dengan variasi di setiap kategori, masing-masing dengan gambar dari Unsplash.

### 17.4 Transactions (TransactionSeeder)

30 transaksi acak dalam 30 hari terakhir, masing-masing dengan 1-5 item.

### 17.5 Factories

| Factory | Custom States |
|---------|---------------|
| UserFactory | `unverified()`, `withTwoFactor()` |
| ProductFactory | `inactive()`, `lowStock()` |
| TransactionFactory | `today()` |

---

## 18. Panduan Pengguna

### 18.1 Untuk Kasir

1. **Login** - Login dengan akun kasir
2. **Buat Transaksi** - `/transactions/create`
   - Pilih pelanggan (ketik nama)
   - Cari/filter produk
   - Klik produk untuk tambah ke keranjang
   - Atur quantity (+/-)
   - Masukkan jumlah bayar
   - Pilih metode pembayaran
   - Klik "Simpan"
3. **Cetak Struk** - Setelah simpan, otomatis ke halaman struk

### 18.2 Untuk Pemilik

Semua yang bisa dilakukan kasir, plus:
- **Lihat Laporan** - `/reports` dengan filter tanggal
- **Dashboard** - Overview bisnis
- **Produk** - Tambah/edit produk baru
- **Kategori** - Kelola kategori
- **Setting Toko** - Ubah profil toko
- **Kelola Pengguna** - Tambah kasir baru

### 18.3 Untuk Admin

Semua yang bisa dilakukan pemilik, plus:
- **Role** - Buat/edit role dengan permission spesifik
- **Permission** - Kelola permission

### 18.4 Invoice Format

`INV-YYYYMMDDHHmmss-RRRR` (contoh: `INV-20260514235959-A7F3`)

### 18.5 Produk Unlimited Stock

Centang "Unlimited Stock" untuk produk yang stoknya tidak terbatas (misal: minuman yang dibuat saat dipesan). Stok produk ini tidak akan berkurang saat transaksi.

---

## 19. Pengembangan & Kontribusi

### 19.1 Code Style

Menggunakan **Laravel Pint** dengan preset default Laravel:

```bash
# Fix formatting
composer run lint
# atau
vendor/bin/pint --format agent

# Check formatting
composer run lint:check
```

### 19.2 Commit Message Convention

Mengikuti conventional commits:
- `feat:` - Fitur baru
- `fix:` - Bug fix
- `refactor:` - Refaktor kode
- `test:` - Tambah/ubah test
- `docs:` - Dokumentasi
- `chore:` - Maintenance

### 19.3 Development Workflow

```bash
# 1. Jalankan development server
composer run dev

# 2. Buat perubahan

# 3. Jalankan test
php artisan test --compact --filter=nama_test

# 4. Format kode
vendor/bin/pint --format agent

# 5. Jalankan full test suite
php artisan test --compact
```

### 19.4 Membuat Halaman Baru

```bash
# 1. Buat file di resources/views/pages/
touch resources/views/pages/fitur-baru.blade.php

# 2. Tambahkan Volt logic
# 3. Route otomatis terdaftar oleh Folio
```

### 19.5 Membuat Model + Migration

```bash
php artisan make:model NamaModel -mf
php artisan make:factory NamaModelFactory --model=NamaModel
php artisan make:seeder NamaModelSeeder
```

### 19.6 Build Production

```bash
npm run build
# atau
composer run setup
```

---

## Appendix

### A. Daftar Perintah Artisan Penting

```bash
php artisan migrate            # Run migrations
php artisan migrate:fresh      # Reset + migrate
php artisan migrate:fresh --seed  # Reset + migrate + seed
php artisan route:list         # Lihat semua routes
php artisan tinker             # Interactive shell
php artisan make:model -mf     # Model + migration + factory
php artisan storage:link       # Link storage
php artisan license:validate   # Cek lisensi
php artisan license:info       # Info lisensi
```

### B. Troubleshooting

**Error: Vite manifest not found**
```bash
npm run build
```

**Error: Storage link missing**
```bash
php artisan storage:link
```

**Error: Permission denied**
```bash
chmod -R 775 storage bootstrap/cache
```

**Error: 403 Forbidden (Licensing)**
- Pastikan `LICENSING_KEY` valid
- Cek `LICENSING_SERVER_URL` reachable
- Cek masa grace period

### C. Resources & Referensi

- [Laravel 13 Documentation](https://laravel.com/docs/13.x)
- [Livewire 4 Documentation](https://livewire.laravel.com/)
- [Volt Documentation](https://livewire.laravel.com/docs/volt)
- [Flux UI Documentation](https://fluxui.dev/docs)
- [Laravel Folio](https://laravel.com/docs/13.x/folio)
- [Laravel Fortify](https://laravel.com/docs/13.x/fortify)
- [Spatie Laravel Permission](https://spatie.be/docs/laravel-permission/v7/)
- [Laravel Licensing Client](https://github.com/masterix21/laravel-licensing-client)
- [TailwindCSS 4](https://tailwindcss.com/docs)
- [ApexCharts](https://apexcharts.com/docs)

---

*Dokumentasi ini diperbarui pada: 14 Mei 2026*
