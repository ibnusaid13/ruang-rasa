<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\Category;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Setting;
use App\Services\CartService;
use App\Services\CheckoutService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class ShopFlowTest extends TestCase
{
    use RefreshDatabase;

    private function product(array $attrs = []): Product
    {
        $cat = Category::firstOrCreate(['slug' => 'cake'], ['name' => 'Cake']);

        return Product::create(array_merge([
            'category_id' => $cat->id,
            'name' => 'Tiramisu ' . Str::random(4),
            'slug' => 'tiramisu-' . Str::lower(Str::random(6)),
            'price' => 25000,
            'stock' => 10,
            'is_active' => true,
        ], $attrs));
    }

    private function addToCart(Product $p, int $qty = 1, ?string $cartUuid = null)
    {
        $req = $cartUuid ? $this->withCookie(CartService::COOKIE, $cartUuid) : $this;

        return $req->postJson('/cart/items', ['product_id' => $p->id, 'quantity' => $qty]);
    }

    private function customer(array $over = []): array
    {
        return array_merge([
            'customer_name' => 'Budi Santoso',
            'customer_phone' => '081234567890',
            'customer_email' => null,
            'fulfillment_method' => 'pickup',
            'shipping_address' => null,
            'notes' => null,
            'payment_method' => 'bank_transfer',
        ], $over);
    }

    /** Buat order lewat service pada keranjang baru. */
    private function placeOrder(Product $p, int $qty = 1, array $over = []): Order
    {
        $cart = Cart::create(['uuid' => (string) Str::uuid(), 'status' => 'active']);
        $cart->items()->create(['product_id' => $p->id, 'quantity' => $qty]);

        return app(CheckoutService::class)->place($cart, $this->customer($over));
    }

    // 1
    public function test_product_is_saved_into_database_cart(): void
    {
        $p = $this->product();
        $res = $this->addToCart($p, 2);

        $res->assertOk()->assertJson(['count' => 2]);
        $res->assertCookie(CartService::COOKIE);
        $this->assertDatabaseHas('cart_items', ['product_id' => $p->id, 'quantity' => 2]);
        $this->assertDatabaseCount('carts', 1);

        // produk yang sama ditambah lagi -> quantity bertambah, bukan baris baru
        $uuid = Cart::first()->uuid;
        $this->addToCart($p, 1, $uuid)->assertOk();
        $this->assertDatabaseCount('cart_items', 1);
        $this->assertDatabaseHas('cart_items', ['product_id' => $p->id, 'quantity' => 3]);
    }

    // 2
    public function test_cart_persists_for_same_token_after_browser_restart(): void
    {
        $p = $this->product(['name' => 'Cheesecake Spesial']);
        $this->addToCart($p, 2);
        $uuid = Cart::first()->uuid;

        // "browser baru" yang membawa cookie token yang sama
        $this->flushSession();
        $this->withCookie(CartService::COOKIE, $uuid)->get('/cart')
            ->assertOk()->assertSee('Cheesecake Spesial');

        // tanpa token / token palsu -> keranjang kosong, tidak bocor
        $this->withCookie(CartService::COOKIE, (string) Str::uuid())->get('/cart')
            ->assertOk()->assertDontSee('Cheesecake Spesial');
    }

    // 3
    public function test_quantity_and_totals_are_computed_by_backend(): void
    {
        $a = $this->product(['price' => 12500]);
        $b = $this->product(['price' => 30000]);
        $this->addToCart($a, 3);
        $uuid = Cart::first()->uuid;
        $this->addToCart($b, 2, $uuid);

        $summary = $this->withCookie(CartService::COOKIE, $uuid)->get('/cart')->viewData('cart');
        $this->assertSame('97500.00', $summary['subtotal']); // 3*12500 + 2*30000

        // harga dari browser diabaikan
        $this->withCookie(CartService::COOKIE, $uuid)->post('/cart/items', [
            'product_id' => $a->id, 'quantity' => 1, 'price' => 1,
        ]);
        $this->assertSame(4, (int) Cart::first()->items()->where('product_id', $a->id)->value('quantity'));
    }

    // 4
    public function test_cannot_add_more_than_available_stock(): void
    {
        $p = $this->product(['stock' => 3]);

        $this->addToCart($p, 5)->assertStatus(422);
        $this->assertDatabaseCount('cart_items', 0);

        $this->addToCart($p, 3)->assertOk();
        $uuid = Cart::first()->uuid;
        $this->addToCart($p, 1, $uuid)->assertStatus(422);
        $this->assertDatabaseHas('cart_items', ['product_id' => $p->id, 'quantity' => 3]);

        $inactive = $this->product(['is_active' => false]);
        $this->addToCart($inactive, 1, $uuid)->assertStatus(422);
    }

    // 5
    public function test_checkout_stores_order_items_and_payment_consistently(): void
    {
        $p = $this->product(['price' => 20000, 'stock' => 10, 'name' => 'Brownies']);
        $this->addToCart($p, 2);
        $uuid = Cart::first()->uuid;

        $res = $this->withCookie(CartService::COOKIE, $uuid)->post('/checkout', $this->customer([
            'fulfillment_method' => 'delivery',
            'shipping_address' => 'Jl. Mawar No. 5, Jakarta',
        ]));

        $order = Order::first();
        $res->assertRedirect(route('orders.show', $order->uuid));

        $fee = Setting::deliveryFee();
        $this->assertSame(number_format(40000, 2, '.', ''), $order->subtotal);
        $this->assertSame(number_format(40000 + $fee, 2, '.', ''), $order->total);
        $this->assertDatabaseHas('order_items', [
            'order_id' => $order->id, 'product_name' => 'Brownies', 'quantity' => 2, 'unit_price' => '20000.00', 'subtotal' => '40000.00',
        ]);
        $this->assertDatabaseHas('payments', ['order_id' => $order->id, 'amount' => $order->total, 'method' => 'bank_transfer']);
        $this->assertSame(8, $p->fresh()->stock);

        // keranjang ditutup dan tidak bisa dipakai ulang
        $this->assertSame('checked_out', Cart::first()->status);
        $this->addToCart($p, 1, $uuid)->assertOk();
        $this->assertDatabaseCount('carts', 2);

        // snapshot: ubah harga produk, order lama tetap
        $p->update(['price' => 99999]);
        $this->assertSame('20000.00', $order->items()->first()->unit_price);
    }

    public function test_checkout_validates_input(): void
    {
        $p = $this->product();
        $this->addToCart($p, 1);
        $uuid = Cart::first()->uuid;

        $this->withCookie(CartService::COOKIE, $uuid)
            ->post('/checkout', $this->customer(['fulfillment_method' => 'delivery', 'shipping_address' => '', 'customer_phone' => 'abc']))
            ->assertSessionHasErrors(['shipping_address', 'customer_phone']);
        $this->assertDatabaseCount('orders', 0);
        $this->assertSame('active', Cart::first()->status);
    }

    // 6  (catatan: PHPUnit tidak bisa menjalankan dua proses paralel sungguhan;
    //     tes ini membuktikan tidak ada oversell saat dua keranjang berebut stok terakhir.
    //     Perlindungan konkurensi nyata ada pada lockForUpdate + decrement bersyarat.)
    public function test_two_checkouts_cannot_oversell_the_same_stock(): void
    {
        $p = $this->product(['stock' => 1]);

        $cartA = Cart::create(['uuid' => (string) Str::uuid(), 'status' => 'active']);
        $cartA->items()->create(['product_id' => $p->id, 'quantity' => 1]);
        $cartB = Cart::create(['uuid' => (string) Str::uuid(), 'status' => 'active']);
        $cartB->items()->create(['product_id' => $p->id, 'quantity' => 1]);

        $svc = app(CheckoutService::class);
        $svc->place($cartA, $this->customer());

        try {
            $svc->place($cartB, $this->customer());
            $this->fail('Checkout kedua seharusnya ditolak karena stok habis.');
        } catch (\App\Exceptions\CheckoutException $e) {
            $this->assertStringContainsString('Stok', $e->getMessage());
        }

        $this->assertSame(0, $p->fresh()->stock);
        $this->assertDatabaseCount('orders', 1);
        $this->assertSame('active', $cartB->fresh()->status); // keranjang tidak hilang
    }

    // 7
    public function test_new_order_is_pending_before_verification(): void
    {
        $order = $this->placeOrder($this->product());

        $this->assertSame('pending', $order->status);
        $this->assertSame('pending', $order->payment->status);
        $this->assertNull($order->payment->paid_at);
    }

    // 8
    public function test_uploading_proof_does_not_mark_payment_as_paid(): void
    {
        Storage::fake('local');
        $order = $this->placeOrder($this->product());

        $this->post("/orders/{$order->uuid}/payment-proof", [
            'proof' => UploadedFile::fake()->image('bukti.jpg'),
        ])->assertSessionHasNoErrors();

        $payment = $order->payment->fresh();
        $this->assertSame('awaiting_verification', $payment->status);
        $this->assertNotNull($payment->proof_path);
        $this->assertNull($payment->paid_at);
        $this->assertNotSame('paid', $payment->status);

        $this->get("/orders/{$order->uuid}")->assertDontSee('Pembayaran Berhasil');
        $this->get("/orders/{$order->uuid}/receipt")->assertDontSee('Pembayaran Berhasil');
    }

    // 9
    public function test_paid_status_only_through_verified_paths(): void
    {
        Storage::fake('local');
        $order = $this->placeOrder($this->product());
        $payment = $order->payment;

        // tanpa bukti -> admin tidak bisa approve
        $this->post(route('admin.payments.approve', $payment))->assertSessionHas('error');
        $this->assertSame('pending', $payment->fresh()->status);

        // webhook tanpa signature valid -> ditolak
        config(['shop.gateway.webhook_secret' => 'test-secret']);
        $this->postJson('/webhooks/payment', ['order_number' => $order->order_number, 'transaction_reference' => 'X1', 'status' => 'paid'])
            ->assertStatus(401);
        $this->assertSame('pending', $payment->fresh()->status);

        // query string / halaman sukses tidak mengubah status
        $this->get("/orders/{$order->uuid}?status=paid&payment=success")->assertOk();
        $this->assertSame('pending', $payment->fresh()->status);

        // jalur sah: upload bukti -> admin approve
        $this->post("/orders/{$order->uuid}/payment-proof", ['proof' => UploadedFile::fake()->image('b.png')]);
        $this->post(route('admin.payments.approve', $payment))->assertSessionHas('success');
        $this->assertSame('paid', $payment->fresh()->status);
        $this->assertNotNull($payment->fresh()->paid_at);
        $this->assertSame('processing', $order->fresh()->status);

        // admin tidak bisa memaksa status "processing" untuk order yang belum dibayar
        $other = $this->placeOrder($this->product());
        $this->patch(route('admin.orders.status', $other), ['status' => 'processing'])->assertSessionHas('error');
        $this->assertSame('pending', $other->fresh()->status);
    }

    public function test_signed_webhook_marks_paid_once_and_is_idempotent(): void
    {
        config(['shop.gateway.webhook_secret' => 'test-secret']);
        $order = $this->placeOrder($this->product(), 1, ['payment_method' => 'gateway']);

        $body = json_encode(['order_number' => $order->order_number, 'transaction_reference' => 'TRX-1', 'status' => 'paid']);
        $sig = hash_hmac('sha256', $body, 'test-secret');
        $headers = ['X-Signature' => $sig, 'CONTENT_TYPE' => 'application/json'];

        $this->call('POST', '/webhooks/payment', [], [], [], $this->transformHeadersToServerVars($headers), $body)->assertOk();
        $paidAt = $order->payment->fresh()->paid_at;
        $this->assertSame('paid', $order->payment->fresh()->status);

        $this->travel(5)->minutes();
        $this->call('POST', '/webhooks/payment', [], [], [], $this->transformHeadersToServerVars($headers), $body)->assertOk();
        $this->assertTrue($paidAt->equalTo($order->payment->fresh()->paid_at)); // tidak diproses dua kali
    }

    public function test_cod_becomes_paid_only_when_order_completed(): void
    {
        $order = $this->placeOrder($this->product(), 1, ['payment_method' => 'cod']);
        $this->assertSame('pending', $order->payment->status);

        foreach (['processing', 'ready', 'completed'] as $s) {
            $this->patch(route('admin.orders.status', $order), ['status' => $s])->assertSessionHas('success');
        }
        $this->assertSame('paid', $order->payment->fresh()->status);
    }

    public function test_cancel_restores_stock(): void
    {
        $p = $this->product(['stock' => 5]);
        $order = $this->placeOrder($p, 2, ['payment_method' => 'cod']);
        $this->assertSame(3, $p->fresh()->stock);

        $this->patch(route('admin.orders.status', $order), ['status' => 'cancelled'])->assertSessionHas('success');
        $this->assertSame(5, $p->fresh()->stock);
    }

    // 10
    public function test_receipt_shows_exact_order_details(): void
    {
        $p = $this->product(['name' => 'Pudding Mangga', 'price' => 15000]);
        $order = $this->placeOrder($p, 3);

        $this->get("/orders/{$order->uuid}/receipt")
            ->assertOk()
            ->assertSee($order->order_number)
            ->assertSee('Pudding Mangga')
            ->assertSee('Rp 15.000')
            ->assertSee('Rp 45.000')
            ->assertSee('Budi Santoso')
            ->assertSee('Cetak Struk')
            ->assertSee('Menunggu Pembayaran');
    }

    // 11
    public function test_orders_cannot_be_accessed_by_guessing_sequential_ids(): void
    {
        $order = $this->placeOrder($this->product());

        $this->get('/orders/' . $order->id)->assertNotFound();
        $this->get('/orders/' . $order->id . '/receipt')->assertNotFound();
        $this->get('/orders/' . $order->order_number)->assertNotFound();
        $this->get('/orders/' . Str::uuid())->assertNotFound();
        $this->get('/orders')->assertNotFound(); // tidak ada daftar order publik
        $this->get("/orders/{$order->uuid}")->assertOk();
    }

    public function test_cart_items_cannot_be_modified_by_other_visitors(): void
    {
        $p = $this->product();
        $this->addToCart($p, 1);
        $item = Cart::first()->items()->first();

        // pengunjung lain (tanpa cookie) tidak boleh menghapus item
        $this->delete(route('cart.destroy', $item))->assertNotFound();
        $this->assertDatabaseCount('cart_items', 1);
    }

    // 12
    public function test_admin_panel_button_opens_dashboard_without_login(): void
    {
        $this->get('/')->assertOk()->assertSee('Panel Admin')->assertSee(route('admin.dashboard'));
        $this->get('/admin/dashboard')->assertOk()->assertSee('Dashboard');
        $this->get('/admin/products')->assertOk();
        $this->get('/admin/orders')->assertOk();
        $this->get('/admin/payments')->assertOk();
        $this->get('/admin/contact-messages')->assertOk();
    }

    public function test_admin_product_crud_with_image_and_validation(): void
    {
        Storage::fake('public');
        $cat = Category::create(['name' => 'Cake', 'slug' => 'cake']);

        $this->post('/admin/products', ['name' => '', 'price' => -1, 'stock' => 'x'])
            ->assertSessionHasErrors(['name', 'price', 'stock']);

        $this->post('/admin/products', [
            'name' => 'Red Velvet', 'category_id' => $cat->id, 'price' => 30000, 'stock' => 7,
            'is_active' => 1, 'is_best_seller' => 1, 'image' => UploadedFile::fake()->image('rv.jpg'),
        ])->assertRedirect();

        $p = Product::where('slug', 'red-velvet')->firstOrFail();
        $this->assertTrue($p->is_best_seller);
        Storage::disk('public')->assertExists($p->image_path);

        $old = $p->image_path;
        $this->put("/admin/products/{$p->id}", ['name' => 'Red Velvet', 'price' => 32000, 'stock' => 5, 'remove_image' => 1])->assertRedirect();
        Storage::disk('public')->assertMissing($old);
        $this->assertSame('32000.00', $p->fresh()->price);
        $this->assertFalse($p->fresh()->is_active); // checkbox tidak dikirim -> nonaktif

        $this->delete("/admin/products/{$p->id}")->assertRedirect();
        $this->assertDatabaseMissing('products', ['id' => $p->id]);
    }

    public function test_contact_form_saves_and_admin_can_read_it(): void
    {
        $this->post('/contact', ['name' => 'Sari', 'email' => 'sari@example.com', 'message' => 'Apakah bisa pesan 50 box?'])
            ->assertRedirect();
        $this->get('/admin/contact-messages')->assertSee('Sari');
        $this->post('/contact', ['name' => '', 'email' => 'x', 'message' => 'a'])->assertSessionHasErrors(['name', 'email', 'message']);
    }

    public function test_mutating_admin_routes_reject_get(): void
    {
        $p = $this->product();
        $this->get("/admin/products/{$p->id}/best-seller")->assertStatus(405);
        $order = $this->placeOrder($this->product());
        $this->get(route('admin.payments.approve', $order->payment))->assertStatus(405);
    }
}
