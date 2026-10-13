<?php

namespace App\Services\Payments;

use App\Models\Order;

/**
 * KERANGKA generik: verifikasi HMAC-SHA256 atas body mentah dengan secret di .env.
 * Ini BUKAN integrasi provider nyata. Untuk produksi, ganti dengan adapter yang
 * memakai mekanisme verifikasi resmi provider yang Anda pilih.
 */
class HmacGateway implements PaymentGateway
{
    public function paymentUrl(Order $order): ?string
    {
        $base = config('shop.gateway.checkout_url');

        return $base ? rtrim($base, '/') . '?order=' . urlencode($order->order_number) : null;
    }

    public function verifyWebhook(string $rawBody, ?string $signature): bool
    {
        $secret = config('shop.gateway.webhook_secret');
        if (! $secret || ! $signature) {
            return false;
        }

        return hash_equals(hash_hmac('sha256', $rawBody, $secret), $signature);
    }
}
