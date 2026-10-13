<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $query = Order::with('payment')->latest();

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }
        if ($q = trim((string) $request->query('q'))) {
            $query->where(fn ($w) => $w->where('order_number', 'like', "%{$q}%")
                ->orWhere('customer_name', 'like', "%{$q}%"));
        }

        return view('admin.orders.index', ['orders' => $query->paginate(15)->withQueryString()]);
    }

    public function show(Order $order)
    {
        return view('admin.orders.show', ['order' => $order->load('items', 'payment')]);
    }

    public function updateStatus(Request $request, Order $order)
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(array_keys(Order::STATUS_LABELS))],
        ]);

        $order->load('payment', 'items');

        try {
            $order->transitionTo($data['status']);
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Status pesanan diperbarui.');
    }
}
