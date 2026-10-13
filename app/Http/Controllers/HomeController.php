<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;

class HomeController extends Controller
{
    public function index()
    {
        return view('home', [
            'bestSellers' => Product::active()->where('is_best_seller', true)->latest()->take(4)->get(),
            'latest' => Product::active()->latest()->take(8)->get(),
            'categories' => Category::withCount(['products' => fn ($q) => $q->where('is_active', true)])->get(),
        ]);
    }
}
