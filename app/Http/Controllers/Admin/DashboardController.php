<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;

class DashboardController extends Controller
{
    public function index()
    {
        $paid = Payment::where('status', 'paid');

        // Penjualan 7 hari terakhir (hanya pembayaran paid). Dikelompokkan di PHP agar
        // tidak bergantung pada fungsi tanggal spesifik DB.
        $days = collect(range(6, 0))->map(fn ($i) => now()->subDays($i)->startOfDay());
        $paidRecent = Payment::where('status', 'paid')
            ->where('paid_at', '>=', $days->first())
            ->get(['amount', 'paid_at']);

        $chart = $days->map(fn ($day) => [
            'label' => $day->format('d/m'),
            'total' => (float) $paidRecent
                ->filter(fn ($p) => $p->paid_at->isSameDay($day))
                ->sum(fn ($p) => (float) $p->amount),
        ]);

        return view('admin.dashboard', [
            'productCount' => Product::count(),
            'orderCount' => Order::count(),
            'awaitingPayment' => Payment::whereIn('status', ['pending', 'awaiting_verification'])
                ->whereHas('order', fn ($q) => $q->where('status', '!=', 'cancelled'))
                ->count(),
            'paidCount' => (clone $paid)->count(),
            'totalSales' => (clone $paid)->sum('amount'),
            'recentOrders' => Order::with('payment')->latest()->take(8)->get(),
            'chart' => $chart,
            'chartMax' => max(1, (float) $chart->max('total')),
        ]);
    }
}
