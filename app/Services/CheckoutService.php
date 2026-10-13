<?php

namespace App\Services;

use App\Exceptions\CheckoutException;
use App\Models\Cart;
use App\Models\Order;
use App\Models\Product;
use App\Models\Setting;
use App\Support\Money;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CheckoutService
{
    /**
     * Membuat pesanan dari keranjang aktif secara atomik.
     * Semua harga/stok dibaca ulang dari database di dalam transaksi.
     *
     * @param array{customer_name:string, customer_phone:string, customer_email:?string,
     *              fulfillment_method:string, shipping_address:?string, notes:?string,
     *              payment_method:string} $data
     *
     * @throws CheckoutException
     */
    public function place(Cart $cart, array $data): Order
    {
        return DB::transaction(function () use ($cart, $data) {
            // Kunci keranjang agar tidak bisa di-checkout dua kali bersamaan.
            $lockedCart = Cart::whereKey($cart->id)
                ->where('status', Cart::ACTIVE)
                ->lockForUpdate()
                ->first();

            if (! $lockedCart) {
                throw new CheckoutException('Keranjang ini sudah di-checkout atau tidak aktif.');
            }

            $items = $lockedCart->items()->orderBy('product_id')->get();
            if ($items->isEmpty()) {
                throw new CheckoutException('Keranjang Anda kosong.');
            }

            // Kunci baris produk (urut id agar tidak deadlock antar-transaksi).
            $products = Product::whereIn('id', $items->pluck('product_id'))
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $subtotalCents = 0;
            $lines = [];

            foreach ($items as $item) {
                /** @var Product|null $product */
                $product = $products->get($item->product_id);

                if (! $product || ! $product->is_active) {
                    throw new CheckoutException('Salah satu produk di keranjang sudah tidak tersedia. Periksa keranjang Anda.');
                }
                if ($product->stock < $item->quantity) {
                    throw new CheckoutException("Stok \"{$product->name}\" tidak mencukupi (tersisa {$product->stock}).");
                }

                $unit = Money::toCents($product->price);
                $lineCents = $unit * $item->quantity;
                $subtotalCents += $lineCents;

                $lines[] = [
                    'product' => $product,
                    'name' => $product->name,
                    'unit_cents' => $unit,
                    'qty' => $item->quantity,
                    'line_cents' => $lineCents,
                ];
            }

            $shippingCents = $data['fulfillment_method'] === 'delivery'
                ? Money::toCents(Setting::deliveryFee())
                : 0;
            $totalCents = $subtotalCents + $shippingCents;

            $order = Order::create([
                'uuid' => (string) Str::uuid(),
                'order_number' => $this->newOrderNumber(),
                'cart_id' => $lockedCart->id,
                'customer_name' => $data['customer_name'],
                'customer_phone' => $data['customer_phone'],
                'customer_email' => $data['customer_email'] ?? null,
                'fulfillment_method' => $data['fulfillment_method'],
                'shipping_address' => $data['fulfillment_method'] === 'delivery' ? $data['shipping_address'] : null,
                'notes' => $data['notes'] ?? null,
                'subtotal' => Money::fromCents($subtotalCents),
                'shipping_fee' => Money::fromCents($shippingCents),
                'total' => Money::fromCents($totalCents),
                'status' => 'pending',
            ]);

            foreach ($lines as $line) {
                $order->items()->create([
                    'product_id' => $line['product']->id,
                    'product_name' => $line['name'],
                    'unit_price' => Money::fromCents($line['unit_cents']),
                    'quantity' => $line['qty'],
                    'subtotal' => Money::fromCents($line['line_cents']),
                ]);

                // Pengurangan atomik bersyarat: sabuk pengaman kedua selain lockForUpdate.
                $affected = Product::whereKey($line['product']->id)
                    ->where('stock', '>=', $line['qty'])
                    ->decrement('stock', $line['qty']);

                if ($affected === 0) {
                    throw new CheckoutException("Stok \"{$line['name']}\" baru saja habis. Silakan periksa keranjang Anda.");
                }
            }

            $order->payment()->create([
                'method' => $data['payment_method'],
                'amount' => Money::fromCents($totalCents),
                'status' => 'pending', // paid HANYA lewat verifikasi admin / webhook terverifikasi
            ]);

            $lockedCart->update(['status' => Cart::CHECKED_OUT]);

            return $order->load('items', 'payment');
        });
    }

    private function newOrderNumber(): string
    {
        do {
            $number = 'RR-' . now()->format('ymd') . '-' . strtoupper(Str::random(5));
        } while (Order::where('order_number', $number)->exists());

        return $number;
    }
}
