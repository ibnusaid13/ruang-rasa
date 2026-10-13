<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\Product;
use App\Support\Money;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Str;

/**
 * Keranjang persisten di database. Browser hanya menyimpan token UUID anonim
 * di cookie (terenkripsi, HttpOnly, SameSite=Lax). Tidak ada data pribadi
 * atau data pembayaran di cookie.
 */
class CartService
{
    public const COOKIE = 'rr_cart';
    public const COOKIE_MINUTES = 60 * 24 * 365; // 1 tahun

    /** Cari keranjang aktif milik cookie saat ini (tanpa membuat baru). */
    public function find(): ?Cart
    {
        $request = request();

        if ($request->attributes->has('rr_cart')) {
            return $request->attributes->get('rr_cart');
        }

        $token = $request->cookie(self::COOKIE);
        if (! is_string($token) || ! Str::isUuid($token)) {
            return null;
        }

        $cart = Cart::where('uuid', $token)->where('status', Cart::ACTIVE)->first();
        $request->attributes->set('rr_cart', $cart);

        return $cart;
    }

    public function findOrCreate(): Cart
    {
        $cart = $this->find();
        if ($cart) {
            return $cart;
        }

        $cart = Cart::create(['uuid' => (string) Str::uuid(), 'status' => Cart::ACTIVE]);

        Cookie::queue(Cookie::make(
            self::COOKIE,
            $cart->uuid,
            self::COOKIE_MINUTES,
            '/',
            null,
            request()->isSecure(),
            true,   // httpOnly
            false,
            'lax'
        ));

        request()->attributes->set('rr_cart', $cart);

        return $cart;
    }

    public function count(): int
    {
        $cart = $this->find();

        return $cart ? (int) $cart->items()->sum('quantity') : 0;
    }

    /**
     * Ringkasan keranjang. Harga SELALU diambil dari tabel products,
     * tidak pernah dari request browser.
     *
     * @return array{lines: array, subtotal: string, subtotal_cents: int, has_issues: bool, is_empty: bool}
     */
    public function summary(?Cart $cart = null): array
    {
        $cart ??= $this->find();
        $lines = [];
        $subtotalCents = 0;
        $hasIssues = false;

        if ($cart) {
            $items = $cart->items()->with('product')->orderBy('id')->get();

            foreach ($items as $item) {
                /** @var Product|null $product */
                $product = $item->product;
                $issue = null;

                if (! $product || ! $product->is_active) {
                    $issue = 'Produk ini sudah tidak tersedia. Hapus dari keranjang.';
                } elseif ($product->stock < 1) {
                    $issue = 'Stok habis. Hapus dari keranjang.';
                } elseif ($product->stock < $item->quantity) {
                    $issue = "Stok tersisa {$product->stock}. Kurangi jumlah pesanan.";
                }

                $lineCents = $product ? Money::toCents($product->price) * $item->quantity : 0;
                if (! $issue) {
                    $subtotalCents += $lineCents;
                } else {
                    $hasIssues = true;
                }

                $lines[] = [
                    'item' => $item,
                    'product' => $product,
                    'issue' => $issue,
                    'line_total' => Money::fromCents($lineCents),
                ];
            }
        }

        return [
            'lines' => $lines,
            'subtotal' => Money::fromCents($subtotalCents),
            'subtotal_cents' => $subtotalCents,
            'has_issues' => $hasIssues,
            'is_empty' => count($lines) === 0,
        ];
    }
}
