<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Onboarding Tour
    |--------------------------------------------------------------------------
    |
    | Shown automatically to users who haven't completed it yet.
    | Guides them through the main dashboard and navigation.
    |
    */
    'onboarding' => [
        'id' => 'onboarding',
        'type' => 'onboarding',
        'label' => 'Tur Selamat Datang',
        'description' => 'Kenali Sistem Manajemen Lisensi',
        'icon' => 'rocket-launch',
        'routes' => ['dashboard'],
        'steps' => [
            [
                'element' => '[data-tour="dashboard-stats"]',
                'title' => 'Statistik Dashboard',
                'description' => 'Lihat metrik utama secara sekilas — total produk, lisensi, lisensi aktif, aktivasi tertunda, dan perangkat terdaftar.',
                'position' => 'bottom',
            ],
            [
                'element' => '[data-tour="dashboard-actions"]',
                'title' => 'Aksi Cepat',
                'description' => 'Langsung menuju ke pengelolaan produk, melihat lisensi, atau menangani permintaan aktivasi.',
                'position' => 'top',
            ],
            [
                'element' => '[data-tour="sidebar-navigation"]',
                'title' => 'Navigasi Sidebar',
                'description' => 'Akses semua bagian dari sidebar. Setiap grup — Katalog, Lisensi, Kontrol Akses, Monitoring — berisi halaman terkait.',
                'position' => 'right',
            ],
            [
                'element' => '[data-tour="user-menu"]',
                'title' => 'Menu Pengguna',
                'description' => 'Kelola profil, pengaturan, dan keluar dari sini.',
                'position' => 'left',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | License Management Feature Tour
    |--------------------------------------------------------------------------
    |
    | Accessible via the Help menu or a button on the Licenses page.
    | Explains how to create, edit, and manage licenses.
    |
    */
    'license-management' => [
        'id' => 'license-management',
        'type' => 'feature',
        'label' => 'Manajemen Lisensi',
        'description' => 'Pelajari cara membuat, mengedit, dan mengelola lisensi perangkat lunak',
        'icon' => 'key',
        'routes' => ['licenses.index', 'licenses.create', 'licenses.*'],
        'steps' => [
            [
                'element' => '[data-tour="licenses-header"]',
                'title' => 'Ikhtisar Lisensi',
                'description' => 'Halaman ini menampilkan semua lisensi dalam sistem. Gunakan bilah pencarian untuk memfilter berdasarkan kunci lisensi, produk, atau status.',
                'position' => 'bottom',
            ],
            [
                'element' => '[data-tour="licenses-create"]',
                'title' => 'Buat Lisensi',
                'description' => 'Klik di sini untuk menerbitkan lisensi baru. Anda akan memilih produk, paket langganan, dan mengonfigurasi properti lisensi.',
                'position' => 'left',
            ],
            [
                'element' => '[data-tour="licenses-table"]',
                'title' => 'Daftar Lisensi',
                'description' => 'Setiap baris menampilkan informasi penting: kunci lisensi, produk yang ditetapkan, status, dan periode berlaku. Klik lisensi untuk mengedit atau mencabutnya.',
                'position' => 'top',
            ],
            [
                'element' => '[data-tour="licenses-status"]',
                'title' => 'Status Lisensi',
                'description' => 'Pantau status lisensi — aktif, ditangguhkan, dicabut, atau kedaluwarsa. Gunakan filter status untuk mempersempit hasil.',
                'position' => 'bottom',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Products Page Contextual Tour
    |--------------------------------------------------------------------------
    |
    | Automatically shown when a user visits the Products page for the first time.
    | Explains the product catalog and how to configure products.
    |
    */
    'contextual-products' => [
        'id' => 'contextual-products',
        'type' => 'contextual',
        'label' => 'Panduan Produk',
        'description' => 'Memahami katalog produk',
        'icon' => 'cube',
        'routes' => ['products.index', 'products.create', 'products.*'],
        'steps' => [
            [
                'element' => '[data-tour="products-header"]',
                'title' => 'Katalog Produk',
                'description' => 'Kelola produk perangkat lunak Anda di sini. Setiap produk dapat memiliki beberapa paket langganan dan lisensi.',
                'position' => 'bottom',
            ],
            [
                'element' => '[data-tour="products-create"]',
                'title' => 'Tambah Produk',
                'description' => 'Daftarkan produk baru dengan memberikan nama, deskripsi, dan metadata opsional.',
                'position' => 'left',
            ],
            [
                'element' => '[data-tour="products-table"]',
                'title' => 'Daftar Produk',
                'description' => 'Lihat semua produk yang terdaftar. Setiap baris menampilkan nama produk, deskripsi, dan tombol aksi.',
                'position' => 'top',
            ],
            [
                'element' => '[data-tour="products-actions"]',
                'title' => 'Aksi Produk',
                'description' => 'Edit detail produk, lihat lisensi terkait, atau hapus produk dari menu ini.',
                'position' => 'left',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | User Management Tour
    |--------------------------------------------------------------------------
    |
    | Feature tour — accessible from the Help sidebar menu.
    |
    */
    'user-management' => [
        'id' => 'user-management',
        'type' => 'feature',
        'label' => 'Manajemen Pengguna',
        'description' => 'Kelola pengguna sistem, peran, dan izin',
        'icon' => 'users',
        'routes' => ['users.index', 'users.create', 'users.*'],
        'steps' => [
            [
                'element' => '[data-tour="users-header"]',
                'title' => 'Manajemen Pengguna',
                'description' => 'Lihat dan kelola semua pengguna sistem. Filter berdasarkan peran untuk menemukan grup pengguna tertentu dengan cepat.',
                'position' => 'bottom',
            ],
            [
                'element' => '[data-tour="users-create"]',
                'title' => 'Tambah Pengguna',
                'description' => 'Klik untuk membuat akun pengguna baru. Anda dapat menetapkan peran selama pembuatan.',
                'position' => 'left',
            ],
            [
                'element' => '[data-tour="users-table"]',
                'title' => 'Daftar Pengguna',
                'description' => 'Setiap baris menampilkan detail pengguna: nama, email, peran yang ditetapkan, dan status admin. Gunakan tombol aksi untuk mengedit atau menghapus pengguna.',
                'position' => 'top',
            ],
            [
                'element' => '[data-tour="users-actions"]',
                'title' => 'Aksi Pengguna',
                'description' => 'Edit detail pengguna atau hapus pengguna. Admin terakhir tidak dapat dihapus untuk mencegah terkunci.',
                'position' => 'left',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | API Clients Tour
    |--------------------------------------------------------------------------
    |
    | Feature tour — accessible from the Help sidebar menu.
    |
    */
    'api-clients' => [
        'id' => 'api-clients',
        'type' => 'feature',
        'label' => 'Klien API',
        'description' => 'Kelola kredensial API untuk aplikasi klien',
        'icon' => 'server',
        'routes' => ['api-clients.index', 'api-clients.create', 'api-clients.*'],
        'steps' => [
            [
                'element' => '[data-tour="api-clients-header"]',
                'title' => 'Ikhtisar Klien API',
                'description' => 'Kelola kunci API dan rahasia yang digunakan oleh aplikasi eksternal untuk mengautentikasi dengan API lisensi.',
                'position' => 'bottom',
            ],
            [
                'element' => '[data-tour="api-clients-create"]',
                'title' => 'Tambah Klien API',
                'description' => 'Daftarkan aplikasi klien baru. Anda akan menerima kunci API dan rahasia — rahasia hanya ditampilkan sekali.',
                'position' => 'left',
            ],
            [
                'element' => '[data-tour="api-clients-table"]',
                'title' => 'Daftar Klien',
                'description' => 'Setiap klien menampilkan nama, kunci API, batas permintaan, status, dan stempel waktu terakhir digunakan. Gunakan ikon kunci untuk menampilkan rahasia.',
                'position' => 'top',
            ],
            [
                'element' => '[data-tour="api-clients-actions"]',
                'title' => 'Aksi Klien',
                'description' => 'Tampilkan rahasia (ikon kunci), edit pengaturan klien, atau hapus klien. Menghapus akan memutus integrasi yang ada.',
                'position' => 'left',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Role Management Tour
    |--------------------------------------------------------------------------
    |
    | Feature tour — accessible from the Help sidebar menu.
    |
    */
    'role-management' => [
        'id' => 'role-management',
        'type' => 'feature',
        'label' => 'Manajemen Peran',
        'description' => 'Kelola peran dan izin untuk RBAC',
        'icon' => 'shield-check',
        'routes' => ['roles.index', 'roles.create', 'roles.*'],
        'steps' => [
            [
                'element' => '[data-tour="roles-header"]',
                'title' => 'Manajemen Peran',
                'description' => 'Lihat semua peran dalam sistem. Peran admin dilindungi dan tidak dapat dihapus.',
                'position' => 'bottom',
            ],
            [
                'element' => '[data-tour="roles-create"]',
                'title' => 'Tambah Peran',
                'description' => 'Buat peran baru dengan izin khusus. Tetapkan ke pengguna untuk mengontrol akses.',
                'position' => 'left',
            ],
            [
                'element' => '[data-tour="roles-table"]',
                'title' => 'Daftar Peran',
                'description' => 'Setiap baris menampilkan nama peran, jenis penjaga, jumlah izin, dan jumlah pengguna yang ditetapkan.',
                'position' => 'top',
            ],
            [
                'element' => '[data-tour="roles-actions"]',
                'title' => 'Aksi Peran',
                'description' => 'Edit izin peran atau hapus peran. Menghapus mempengaruhi semua pengguna yang ditetapkan ke peran tersebut.',
                'position' => 'left',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Subscription Plans Tour
    |--------------------------------------------------------------------------
    |
    | Contextual — shown automatically on first visit to Plans page.
    |
    */
    'subscription-plans' => [
        'id' => 'subscription-plans',
        'type' => 'contextual',
        'label' => 'Paket Langganan',
        'description' => 'Kelola paket durasi lisensi',
        'icon' => 'currency-dollar',
        'routes' => ['plans.index', 'plans.create', 'plans.*'],
        'steps' => [
            [
                'element' => '[data-tour="plans-header"]',
                'title' => 'Paket Langganan',
                'description' => 'Tentukan paket durasi untuk lisensi Anda. Setiap paket menentukan nama, deskripsi, dan masa berlaku lisensi dalam hari.',
                'position' => 'bottom',
            ],
            [
                'element' => '[data-tour="plans-create"]',
                'title' => 'Tambah Paket',
                'description' => 'Buat paket baru dengan nama, slug, deskripsi, dan durasi dalam hari.',
                'position' => 'left',
            ],
            [
                'element' => '[data-tour="plans-table"]',
                'title' => 'Daftar Paket',
                'description' => 'Setiap paket menampilkan nama, durasi hari, dan status aktif. Gunakan aksi untuk mengedit atau menghapus.',
                'position' => 'top',
            ],
            [
                'element' => '[data-tour="plans-actions"]',
                'title' => 'Aksi Paket',
                'description' => 'Edit detail paket atau hapus paket. Menghapus paket dapat mempengaruhi lisensi aktif yang menggunakannya.',
                'position' => 'left',
            ],
        ],
    ],

];
