<?php

return [

    'version' => '1.0.0',

    // Tanpa port & skema. Lokal memakai *.localhost (otomatis ke 127.0.0.1 di Chrome/Edge).
    'domains' => [
        'main' => env('CATAMU_DOMAIN', 'catamu.localhost'),
        'admin' => env('CATAMU_ADMIN_DOMAIN', 'admin.catamu.localhost'),
        'cekin' => env('CATAMU_CEKIN_DOMAIN', 'cekin.catamu.localhost'),
    ],

    // Default lama trial; super admin dapat mengubahnya di admin > Pengaturan.
    'trial_days' => 3,

    'checkin_per_minute' => 10,

    'super_admin_emails' => array_values(array_filter(array_map(
        fn ($email) => strtolower(trim($email)),
        explode(',', (string) env('SUPER_ADMIN_EMAILS', ''))
    ))),

    // Program afiliasi antar kantor. Nilai default; super admin dapat
    // mengubahnya di admin > Pengaturan.
    'affiliate' => [
        'rate' => 20,          // persen komisi dari tiap pembayaran yang disetujui
        'min_payout' => 50000, // saldo minimum untuk mengajukan pencairan
    ],

    // Harus sama dengan tombol paket (data-plan-days / data-plan-name) di halaman Berlangganan.
    'plans' => [
        ['name' => '1 Tahun', 'days' => 365, 'amount' => 99000],
    ],

    'notification_limit' => 100,

];
