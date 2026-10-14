<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PaymentWebhookController;
use App\Http\Controllers\ProductController;
use Illuminate\Support\Facades\Route;

Route::get('/health', fn () => response('OK', 200)->header('Content-Type', 'text/plain'));
// ---------- Publik ----------
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/products', [ProductController::class, 'index'])->name('products.index');
Route::get('/products/{product:slug}', [ProductController::class, 'show'])->name('products.show');

Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
Route::post('/cart/items', [CartController::class, 'add'])->name('cart.add');
Route::patch('/cart/items/{item}', [CartController::class, 'update'])->name('cart.update');
Route::delete('/cart/items/{item}', [CartController::class, 'destroy'])->name('cart.destroy');

Route::get('/checkout', [CheckoutController::class, 'show'])->name('checkout.show');
Route::post('/checkout', [CheckoutController::class, 'store'])->name('checkout.store');

// Order diakses hanya lewat UUID acak (divalidasi di controller). Tidak ada daftar order publik.
Route::get('/orders/{uuid}', [OrderController::class, 'show'])->name('orders.show');
Route::get('/orders/{uuid}/receipt', [OrderController::class, 'receipt'])->name('orders.receipt');
Route::post('/orders/{uuid}/payment-proof', [OrderController::class, 'uploadProof'])->name('orders.proof');

Route::post('/contact', [ContactController::class, 'store'])->name('contact.store');

// Callback gateway (kerangka). Tanpa CSRF karena dipanggil server provider;
// keamanan dijaga verifikasi signature di controller.
Route::post('/webhooks/payment', [PaymentWebhookController::class, 'handle'])
    ->withoutMiddleware([
        \Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class,
        \Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class,
    ])
    ->name('webhooks.payment');

// ---------- Admin (TANPA LOGIN sesuai permintaan demo; JANGAN dipublikasikan) ----------
Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/', fn () => redirect()->route('admin.dashboard'));
    Route::get('/dashboard', [Admin\DashboardController::class, 'index'])->name('dashboard');

    Route::resource('products', Admin\ProductController::class)->except('show');
    Route::post('products/{product}/best-seller', [Admin\ProductController::class, 'toggleBestSeller'])->name('products.best-seller');

    Route::get('orders', [Admin\OrderController::class, 'index'])->name('orders.index');
    Route::get('orders/{order}', [Admin\OrderController::class, 'show'])->name('orders.show');
    Route::patch('orders/{order}/status', [Admin\OrderController::class, 'updateStatus'])->name('orders.status');

    Route::get('payments', [Admin\PaymentController::class, 'index'])->name('payments.index');
    Route::get('payments/{payment}', [Admin\PaymentController::class, 'show'])->name('payments.show');
    Route::get('payments/{payment}/proof', [Admin\PaymentController::class, 'proof'])->name('payments.proof');
    Route::post('payments/{payment}/approve', [Admin\PaymentController::class, 'approve'])->name('payments.approve');
    Route::post('payments/{payment}/reject', [Admin\PaymentController::class, 'reject'])->name('payments.reject');

    Route::get('contact-messages', [Admin\ContactMessageController::class, 'index'])->name('messages.index');
    Route::get('contact-messages/{message}', [Admin\ContactMessageController::class, 'show'])->name('messages.show');
    Route::delete('contact-messages/{message}', [Admin\ContactMessageController::class, 'destroy'])->name('messages.destroy');

    Route::get('settings', [Admin\SettingController::class, 'edit'])->name('settings.edit');
    Route::put('settings', [Admin\SettingController::class, 'update'])->name('settings.update');

});
