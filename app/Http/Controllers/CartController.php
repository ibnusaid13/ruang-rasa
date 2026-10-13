<?php

namespace App\Http\Controllers;

use App\Models\CartItem;
use App\Models\Product;
use App\Services\CartService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CartController extends Controller
{
    public function __construct(private CartService $carts)
    {
    }

    public function index()
    {
        return view('cart', ['cart' => $this->carts->summary()]);
    }

    public function add(Request $request)
    {
        $data = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'quantity' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);
        $qty = (int) ($data['quantity'] ?? 1);

        $product = Product::find($data['product_id']);
        if (! $product->is_active) {
            return $this->fail($request, 'Produk ini sedang tidak dijual.');
        }

        $cart = $this->carts->findOrCreate();

        $error = DB::transaction(function () use ($cart, $product, $qty) {
            $item = CartItem::where('cart_id', $cart->id)
                ->where('product_id', $product->id)
                ->lockForUpdate()
                ->first();

            $newQty = ($item?->quantity ?? 0) + $qty;

            if ($product->stock < 1) {
                return 'Maaf, stok ' . $product->name . ' habis.';
            }
            if ($newQty > $product->stock) {
                return 'Stok ' . $product->name . ' hanya tersisa ' . $product->stock . '. Di keranjang Anda sudah ada ' . ($item?->quantity ?? 0) . '.';
            }

            if ($item) {
                $item->update(['quantity' => $newQty]);
            } else {
                CartItem::create(['cart_id' => $cart->id, 'product_id' => $product->id, 'quantity' => $newQty]);
            }

            return null;
        });

        if ($error) {
            return $this->fail($request, $error);
        }

        $message = $product->name . ' masuk ke keranjang.';
        $count = $this->carts->count();

        if ($request->expectsJson()) {
            return response()->json(['message' => $message, 'count' => $count]);
        }

        return back()->with('success', $message);
    }

    public function update(Request $request, CartItem $item)
    {
        $this->authorizeItem($item);

        $data = $request->validate(['quantity' => ['required', 'integer', 'min:1', 'max:99']]);
        $product = $item->product;

        if (! $product || ! $product->is_active) {
            return back()->with('error', 'Produk ini sudah tidak tersedia.');
        }
        if ($data['quantity'] > $product->stock) {
            return back()->with('error', 'Stok ' . $product->name . ' hanya tersisa ' . $product->stock . '.');
        }

        $item->update(['quantity' => $data['quantity']]);

        return back()->with('success', 'Jumlah diperbarui.');
    }

    public function destroy(CartItem $item)
    {
        $this->authorizeItem($item);
        $item->delete();

        return back()->with('success', 'Produk dihapus dari keranjang.');
    }

    /** Item hanya boleh diubah oleh pemilik token keranjang aktif. */
    private function authorizeItem(CartItem $item): void
    {
        $cart = $this->carts->find();
        abort_unless($cart && $item->cart_id === $cart->id, 404);
    }

    private function fail(Request $request, string $message)
    {
        if ($request->expectsJson()) {
            return response()->json(['message' => $message], 422);
        }

        return back()->with('error', $message);
    }
}
