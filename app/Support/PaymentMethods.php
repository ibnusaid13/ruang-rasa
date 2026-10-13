<?php

namespace App\Support;

use App\Models\Setting;

class PaymentMethods
{
    /** @return array<string, array> metode yang benar-benar tersedia saat ini */
    public static function enabled(): array
    {
        $methods = config('shop.payment_methods', []);
        $result = [];

        foreach ($methods as $key => $cfg) {
            if (empty($cfg['enabled'])) {
                continue;
            }
            // QRIS hanya muncul jika QR resmi milik usaha sudah diunggah admin.
            if ($key === 'qris' && ! Setting::get('qris_image')) {
                continue;
            }
            // Gateway hanya muncul jika secret sudah diisi di .env.
            if ($key === 'gateway' && ! config('shop.gateway.webhook_secret')) {
                continue;
            }
            $result[$key] = $cfg;
        }

        return $result;
    }

    public static function bank(): array
    {
        return [
            'name' => Setting::get('bank_name', ''),
            'account' => Setting::get('bank_account', ''),
            'holder' => Setting::get('bank_holder', ''),
            'note' => Setting::get('transfer_note', 'Transfer sesuai nominal total, lalu unggah bukti pembayaran di halaman pesanan.'),
        ];
    }
}
