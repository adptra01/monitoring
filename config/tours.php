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
    | Activation Requests Tour
    |--------------------------------------------------------------------------
    |
    | Contextual — shown automatically on first visit.
    | Explains how to approve or reject manual activation requests.
    |
    */
    'activation-requests' => [
        'id' => 'activation-requests',
        'type' => 'contextual',
        'label' => 'Permintaan Aktivasi',
        'description' => 'Setujui atau tolak permintaan aktivasi lisensi',
        'icon' => 'check-badge',
        'routes' => ['activation-requests.index'],
        'steps' => [
            [
                'element' => '[data-tour="activation-header"]',
                'title' => 'Permintaan Aktivasi',
                'description' => 'Tinjau permintaan aktivasi yang masuk. Filter berdasarkan status — Tertunda, Disetujui, atau Ditolak — menggunakan dropdown di sebelah kanan.',
                'position' => 'bottom',
            ],
            [
                'element' => '[data-tour="activation-table"]',
                'title' => 'Daftar Permintaan',
                'description' => 'Setiap baris menampilkan kunci lisensi, perangkat yang meminta, lencana status, dan kode aktivasi. Permintaan tertunda dapat disetujui atau ditolak langsung.',
                'position' => 'top',
            ],
            [
                'element' => '[data-tour="activation-actions"]',
                'title' => 'Setujui / Tolak',
                'description' => 'Klik centang untuk menyetujui atau X untuk menolak permintaan tertunda. Setelah disetujui, perangkat dapat mengaktifkan lisensi.',
                'position' => 'left',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Device Management Tour
    |--------------------------------------------------------------------------
    |
    | Contextual — shown automatically on first visit to Devices page.
    | Explains device monitoring and fingerprinting.
    |
    */
    'device-management' => [
        'id' => 'device-management',
        'type' => 'contextual',
        'label' => 'Manajemen Perangkat',
        'description' => 'Pantau perangkat yang diaktifkan dan sidik jari perangkat keras',
        'icon' => 'computer-desktop',
        'routes' => ['devices.index'],
        'steps' => [
            [
                'element' => '[data-tour="devices-header"]',
                'title' => 'Ikhtisar Perangkat',
                'description' => 'Lihat semua perangkat yang telah diaktifkan di seluruh lisensi Anda. Setiap perangkat terikat dengan sidik jari perangkat keras yang unik.',
                'position' => 'bottom',
            ],
            [
                'element' => '[data-tour="devices-search"]',
                'title' => 'Cari Perangkat',
                'description' => 'Cari berdasarkan nama perangkat atau sidik jari untuk menemukan perangkat tertentu dengan cepat.',
                'position' => 'bottom',
            ],
            [
                'element' => '[data-tour="devices-table"]',
                'title' => 'Daftar Perangkat',
                'description' => 'Setiap baris menampilkan ID perangkat, kunci lisensi terkait, nama perangkat, platform, dan stempel waktu terakhir terlihat.',
                'position' => 'top',
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
    | Audit Logs Tour
    |--------------------------------------------------------------------------
    |
    | Contextual — shown automatically on first visit to Audit Logs page.
    |
    */
    'audit-logs' => [
        'id' => 'audit-logs',
        'type' => 'contextual',
        'label' => 'Log Audit',
        'description' => 'Lacak aktivitas sistem dan perubahan sumber daya',
        'icon' => 'clipboard-document-list',
        'routes' => ['audit-logs.index'],
        'steps' => [
            [
                'element' => '[data-tour="audit-header"]',
                'title' => 'Jejak Audit',
                'description' => 'Setiap tindakan dalam sistem dicatat di sini — pembuatan lisensi, persetujuan aktivasi, penangguhan, dan lainnya.',
                'position' => 'bottom',
            ],
            [
                'element' => '[data-tour="audit-filters"]',
                'title' => 'Cari & Filter',
                'description' => 'Cari berdasarkan alamat IP atau nama entitas, dan filter berdasarkan jenis tindakan untuk menemukan entri log tertentu.',
                'position' => 'bottom',
            ],
            [
                'element' => '[data-tour="audit-table"]',
                'title' => 'Entri Log',
                'description' => 'Setiap entri menampilkan tindakan yang dilakukan, entitas yang terpengaruh, pengguna yang melakukannya, alamat IP, dan stempel waktu.',
                'position' => 'top',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Webhook Configuration Tour
    |--------------------------------------------------------------------------
    |
    | Feature tour — accessible from the Help sidebar menu.
    |
    */
    'webhook-config' => [
        'id' => 'webhook-config',
        'type' => 'feature',
        'label' => 'Konfigurasi Webhook',
        'description' => 'Konfigurasikan endpoint webhook untuk notifikasi peristiwa',
        'icon' => 'webhook',
        'routes' => ['webhooks.index', 'webhooks.create', 'webhooks.*'],
        'steps' => [
            [
                'element' => '[data-tour="webhooks-header"]',
                'title' => 'Endpoint Webhook',
                'description' => 'Kelola endpoint yang menerima panggilan balik HTTP saat peristiwa lisensi terjadi — seperti lisensi dibuat, dicabut, atau perangkat terdaftar.',
                'position' => 'bottom',
            ],
            [
                'element' => '[data-tour="webhooks-create"]',
                'title' => 'Tambah Webhook',
                'description' => 'Daftarkan URL endpoint baru dan pilih peristiwa mana yang ingin dilanggani. Setiap endpoint mendapatkan rahasia penandatanganan yang unik.',
                'position' => 'left',
            ],
            [
                'element' => '[data-tour="webhooks-table"]',
                'title' => 'Daftar Endpoint',
                'description' => 'Setiap endpoint menampilkan URL, peristiwa yang dilanggani sebagai lencana, status aktif, dan tanggal pembuatan.',
                'position' => 'top',
            ],
            [
                'element' => '[data-tour="webhooks-actions"]',
                'title' => 'Aksi Endpoint',
                'description' => 'Edit pengaturan endpoint, alihkan status aktif, atau hapus endpoint.',
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
        'description' => 'Kelola paket harga untuk produk Anda',
        'icon' => 'currency-dollar',
        'routes' => ['plans.index', 'plans.create', 'plans.*'],
        'steps' => [
            [
                'element' => '[data-tour="plans-header"]',
                'title' => 'Paket Langganan',
                'description' => 'Tentukan tingkat harga untuk produk Anda. Setiap paket menentukan harga bulanan dan tahunan, plus jumlah maksimum perangkat yang diizinkan.',
                'position' => 'bottom',
            ],
            [
                'element' => '[data-tour="plans-create"]',
                'title' => 'Tambah Paket',
                'description' => 'Buat paket langganan baru yang terkait dengan produk. Atur harga, interval penagihan, dan batas perangkat.',
                'position' => 'left',
            ],
            [
                'element' => '[data-tour="plans-table"]',
                'title' => 'Daftar Paket',
                'description' => 'Setiap paket menampilkan produk induk, nama paket, harga bulanan/tahunan, dan perangkat maks. Gunakan aksi untuk mengedit atau menghapus.',
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
