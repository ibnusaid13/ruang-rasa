<?php

/*
|--------------------------------------------------------------------------
| Konfigurasi Toko Ruang Rasa
|--------------------------------------------------------------------------
| Nilai default di sini bisa ditimpa lewat .env, dan sebagian (rekening,
| alamat, ongkir demo, QRIS) bisa diubah admin di /admin/settings.
| JANGAN menaruh secret apa pun di file Blade/JS.
*/

return [
    'name' => 'Ruang Rasa',

    'contact' => [
        'address' => env('SHOP_ADDRESS', ''),
        'phone' => env('SHOP_PHONE', ''),
    ],

    // Tarif demo, BUKAN data bisnis nyata. Ubah di admin / .env.
    'delivery_fee' => (int) env('SHOP_DELIVERY_FEE', 10000),

    'payment_methods' => [
        'bank_transfer' => [
            'label' => 'Transfer Bank (Manual)',
            'enabled' => (bool) env('PAY_BANK_ENABLED', true),
            'needs_proof' => true,
        ],
        'qris' => [
            'label' => 'QRIS (Manual)',
            // Aktif hanya jika admin sudah mengunggah QR resmi milik usaha.
            'enabled' => (bool) env('PAY_QRIS_ENABLED', false),
            'needs_proof' => true,
        ],
        'cod' => [
            'label' => 'Bayar di Tempat (COD)',
            'enabled' => (bool) env('PAY_COD_ENABLED', true),
            'needs_proof' => false,
        ],
        'gateway' => [
            'label' => 'Payment Gateway',
            // Butuh adapter provider + secret di .env. Default nonaktif.
            'enabled' => (bool) env('PAY_GATEWAY_ENABLED', false),
            'needs_proof' => false,
        ],
    ],

    'gateway' => [
        // Hanya dibaca server. Jangan pernah dikirim ke browser.
        'webhook_secret' => env('PAYMENT_WEBHOOK_SECRET'),
        'checkout_url' => env('PAYMENT_GATEWAY_CHECKOUT_URL'),
    ],
];
