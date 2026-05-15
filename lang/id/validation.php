<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Baris Bahasa Validasi
    |--------------------------------------------------------------------------
    |
    | Baris bahasa berikut berisi pesan error bawaan yang digunakan oleh
    | class validator. Beberapa rule memiliki beberapa versi seperti rule size.
    | Silakan sesuaikan pesan-pesan ini sesuai kebutuhan aplikasi Anda.
    |
    */

    'accepted' => 'Field :attribute harus disetujui.',
    'accepted_if' => 'Field :attribute harus disetujui ketika :other adalah :value.',
    'active_url' => 'Field :attribute harus berupa URL yang valid.',
    'after' => 'Field :attribute harus berupa tanggal setelah :date.',
    'after_or_equal' => 'Field :attribute harus berupa tanggal setelah atau sama dengan :date.',
    'alpha' => 'Field :attribute hanya boleh berisi huruf.',
    'alpha_dash' => 'Field :attribute hanya boleh berisi huruf, angka, tanda hubung, dan underscore.',
    'alpha_num' => 'Field :attribute hanya boleh berisi huruf dan angka.',
    'any_of' => 'Field :attribute tidak valid.',
    'array' => 'Field :attribute harus berupa array.',
    'ascii' => 'Field :attribute hanya boleh berisi karakter alfanumerik dan simbol ASCII.',
    'before' => 'Field :attribute harus berupa tanggal sebelum :date.',
    'before_or_equal' => 'Field :attribute harus berupa tanggal sebelum atau sama dengan :date.',

    'between' => [
        'array' => 'Field :attribute harus memiliki antara :min sampai :max item.',
        'file' => 'Field :attribute harus berukuran antara :min sampai :max kilobyte.',
        'numeric' => 'Field :attribute harus bernilai antara :min sampai :max.',
        'string' => 'Field :attribute harus terdiri dari antara :min sampai :max karakter.',
    ],

    'boolean' => 'Field :attribute harus bernilai true atau false.',
    'can' => 'Field :attribute mengandung nilai yang tidak diizinkan.',
    'confirmed' => 'Konfirmasi field :attribute tidak cocok.',
    'contains' => 'Field :attribute tidak memiliki nilai yang diperlukan.',
    'current_password' => 'Password salah.',
    'date' => 'Field :attribute harus berupa tanggal yang valid.',
    'date_equals' => 'Field :attribute harus berupa tanggal yang sama dengan :date.',
    'date_format' => 'Format field :attribute harus sesuai dengan :format.',
    'decimal' => 'Field :attribute harus memiliki :decimal angka desimal.',
    'declined' => 'Field :attribute harus ditolak.',
    'declined_if' => 'Field :attribute harus ditolak ketika :other adalah :value.',
    'different' => 'Field :attribute dan :other harus berbeda.',
    'digits' => 'Field :attribute harus terdiri dari :digits digit.',
    'digits_between' => 'Field :attribute harus terdiri dari antara :min sampai :max digit.',
    'dimensions' => 'Dimensi gambar pada field :attribute tidak valid.',
    'distinct' => 'Field :attribute memiliki nilai duplikat.',
    'doesnt_contain' => 'Field :attribute tidak boleh mengandung salah satu dari: :values.',
    'doesnt_end_with' => 'Field :attribute tidak boleh diakhiri dengan salah satu dari: :values.',
    'doesnt_start_with' => 'Field :attribute tidak boleh diawali dengan salah satu dari: :values.',
    'email' => 'Field :attribute harus berupa alamat email yang valid.',
    'encoding' => 'Field :attribute harus diencode menggunakan :encoding.',
    'ends_with' => 'Field :attribute harus diakhiri dengan salah satu dari: :values.',
    'enum' => 'Pilihan :attribute tidak valid.',
    'exists' => 'Pilihan :attribute tidak valid.',
    'extensions' => 'Field :attribute harus memiliki ekstensi berikut: :values.',
    'file' => 'Field :attribute harus berupa file.',
    'filled' => 'Field :attribute wajib diisi.',

    'gt' => [
        'array' => 'Field :attribute harus memiliki lebih dari :value item.',
        'file' => 'Field :attribute harus lebih besar dari :value kilobyte.',
        'numeric' => 'Field :attribute harus lebih besar dari :value.',
        'string' => 'Field :attribute harus lebih dari :value karakter.',
    ],

    'gte' => [
        'array' => 'Field :attribute harus memiliki minimal :value item.',
        'file' => 'Field :attribute harus lebih besar atau sama dengan :value kilobyte.',
        'numeric' => 'Field :attribute harus lebih besar atau sama dengan :value.',
        'string' => 'Field :attribute harus lebih besar atau sama dengan :value karakter.',
    ],

    'hex_color' => 'Field :attribute harus berupa warna hexadecimal yang valid.',
    'image' => 'Field :attribute harus berupa gambar.',
    'in' => 'Pilihan :attribute tidak valid.',
    'in_array' => 'Field :attribute harus ada di dalam :other.',
    'integer' => 'Field :attribute harus berupa bilangan bulat.',
    'ip' => 'Field :attribute harus berupa alamat IP yang valid.',
    'ipv4' => 'Field :attribute harus berupa alamat IPv4 yang valid.',
    'ipv6' => 'Field :attribute harus berupa alamat IPv6 yang valid.',
    'json' => 'Field :attribute harus berupa JSON yang valid.',
    'list' => 'Field :attribute harus berupa daftar.',
    'lowercase' => 'Field :attribute harus menggunakan huruf kecil.',

    'lt' => [
        'array' => 'Field :attribute harus memiliki kurang dari :value item.',
        'file' => 'Field :attribute harus kurang dari :value kilobyte.',
        'numeric' => 'Field :attribute harus kurang dari :value.',
        'string' => 'Field :attribute harus kurang dari :value karakter.',
    ],

    'lte' => [
        'array' => 'Field :attribute tidak boleh memiliki lebih dari :value item.',
        'file' => 'Field :attribute harus kurang dari atau sama dengan :value kilobyte.',
        'numeric' => 'Field :attribute harus kurang dari atau sama dengan :value.',
        'string' => 'Field :attribute harus kurang dari atau sama dengan :value karakter.',
    ],

    'mac_address' => 'Field :attribute harus berupa MAC address yang valid.',

    'max' => [
        'array' => 'Field :attribute tidak boleh memiliki lebih dari :max item.',
        'file' => 'Field :attribute tidak boleh lebih besar dari :max kilobyte.',
        'numeric' => 'Field :attribute tidak boleh lebih besar dari :max.',
        'string' => 'Field :attribute tidak boleh lebih dari :max karakter.',
    ],

    'mimes' => 'Field :attribute harus berupa file dengan tipe: :values.',
    'mimetypes' => 'Field :attribute harus berupa file dengan tipe: :values.',

    'min' => [
        'array' => 'Field :attribute minimal harus memiliki :min item.',
        'file' => 'Field :attribute minimal harus berukuran :min kilobyte.',
        'numeric' => 'Field :attribute minimal harus bernilai :min.',
        'string' => 'Field :attribute minimal harus terdiri dari :min karakter.',
    ],

    'numeric' => 'Field :attribute harus berupa angka.',
    'present' => 'Field :attribute harus tersedia.',
    'regex' => 'Format field :attribute tidak valid.',
    'required' => 'Field :attribute wajib diisi.',
    'required_if' => 'Field :attribute wajib diisi ketika :other adalah :value.',
    'required_with' => 'Field :attribute wajib diisi ketika :values tersedia.',
    'same' => 'Field :attribute harus sama dengan :other.',

    'size' => [
        'array' => 'Field :attribute harus berisi :size item.',
        'file' => 'Field :attribute harus berukuran :size kilobyte.',
        'numeric' => 'Field :attribute harus bernilai :size.',
        'string' => 'Field :attribute harus terdiri dari :size karakter.',
    ],

    'starts_with' => 'Field :attribute harus diawali dengan salah satu dari: :values.',
    'string' => 'Field :attribute harus berupa teks.',
    'timezone' => 'Field :attribute harus berupa zona waktu yang valid.',
    'unique' => ':attribute sudah digunakan.',
    'uploaded' => 'Field :attribute gagal diupload.',
    'uppercase' => 'Field :attribute harus menggunakan huruf besar.',
    'url' => 'Field :attribute harus berupa URL yang valid.',
    'ulid' => 'Field :attribute harus berupa ULID yang valid.',
    'uuid' => 'Field :attribute harus berupa UUID yang valid.',

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Language Lines
    |--------------------------------------------------------------------------
    */

    'custom' => [
        'attribute-name' => [
            'rule-name' => 'pesan-custom',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Attributes
    |--------------------------------------------------------------------------
    */

    'attributes' => [
        'email' => 'email',
        'password' => 'password',
        'name' => 'nama',
    ],

];
