<?php

namespace App\Http\Controllers;

use App\Exceptions\CheckoutException;
use App\Models\Setting;
use App\Services\CartService;
use App\Services\CheckoutService;
use App\Support\Money;
use App\Support\PaymentMethods;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CheckoutController extends Controller
{
    public function __construct(private CartService $carts, private CheckoutService $checkout)
    {
    }

    public function show()
    {
        $cart = $this->carts->summary();

        if ($cart['is_empty']) {
            return redirect()->route('cart.index')->with('error', 'Keranjang masih kosong.');
        }
        if ($cart['has_issues']) {
            return redirect()->route('cart.index')->with('error', 'Ada produk bermasalah di keranjang. Perbaiki dulu sebelum checkout.');
        }

        return view('checkout', [
            'cart' => $cart,
            'methods' => PaymentMethods::enabled(),
            'deliveryFee' => Setting::deliveryFee(),
            'deliveryFeeLabel' => Money::rp(Setting::deliveryFee()),
        ]);
    }

    public function store(Request $request)
    {
        $methods = array_keys(PaymentMethods::enabled());

        $data = $request->validate([
            'customer_name' => ['required', 'string', 'min:3', 'max:100'],
            'customer_phone' => ['required', 'string', 'regex:/^(\+62|62|0)8[0-9]{7,12}$/'],
            'customer_email' => ['nullable', 'email', 'max:150'],
            'fulfillment_method' => ['required', Rule::in(['delivery', 'pickup'])],
            'shipping_address' => ['required_if:fulfillment_method,delivery', 'nullable', 'string', 'min:10', 'max:500'],
            'notes' => ['nullable', 'string', 'max:500'],
            'payment_method' => ['required', Rule::in($methods)],
        ], [
            'customer_phone.regex' => 'Gunakan nomor WhatsApp Indonesia, contoh 081234567890.',
            'shipping_address.required_if' => 'Alamat pengiriman wajib diisi untuk delivery.',
            'payment_method.in' => 'Metode pembayaran tidak tersedia.',
        ]);

        $cart = $this->carts->find();
        if (! $cart) {
            return redirect()->route('cart.index')->with('error', 'Keranjang masih kosong.');
        }

        try {
            $order = $this->checkout->place($cart, $data);
        } catch (CheckoutException $e) {
            return redirect()->route('cart.index')->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('orders.show', $order->uuid)
            ->with('success', 'Pesanan ' . $order->order_number . ' berhasil dibuat.');
    }
}
