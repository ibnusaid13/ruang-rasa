<?php

namespace App\Services\Payments;

use App\Models\Order;

/**
 * Titik integrasi payment gateway (opsional, terpisah dari alur manual).
 * Implementasikan interface ini untuk provider pilihan Anda (Midtrans, Xendit, dll.)
 * dengan mengikuti dokumentasi resmi provider.
 */
interface PaymentGateway
{
    /** URL halaman bayar milik provider untuk pesanan ini, atau null jika belum tersedia. */
    public function paymentUrl(Order $order): ?string;

    /** Verifikasi keaslian callback sesuai mekanisme resmi provider. */
    public function verifyWebhook(string $rawBody, ?string $signature): bool;
}
