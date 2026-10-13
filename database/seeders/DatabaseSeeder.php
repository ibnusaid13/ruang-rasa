<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\Setting;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $cats = collect(['Cake', 'Puding', 'Cookies', 'Pastry', 'Brownies'])
            ->mapWithKeys(fn ($n) => [$n => Category::firstOrCreate(['slug' => Str::slug($n)], ['name' => $n])]);

        // Data contoh. Harga & stok hanya demo; ubah lewat Panel Admin.
        // Foto diunggah lewat Panel Admin > Produk > Edit.
        $products = [
            ['Tiramisu Cup', 'Cake', 28000, 25, true, 'Lapisan kopi, krim mascarpone, dan cokelat bubuk dalam cup.'],
            ['Basque Burnt Cheesecake', 'Cake', 38000, 15, true, 'Cheesecake bertekstur creamy dengan permukaan karamel gosong.'],
            ['Puding Cokelat Lava', 'Puding', 18000, 30, false, 'Puding cokelat lembut dengan saus cokelat hangat.'],
            ['Creme Brulee', 'Puding', 25000, 20, false, 'Custard vanila dengan lapisan gula karamel renyah.'],
            ['Choco Chip Cookies (6 pcs)', 'Cookies', 35000, 40, false, 'Cookies chewy dengan potongan cokelat.'],
            ['Donat Glaze Mix (6 pcs)', 'Pastry', 30000, 30, true, 'Donat empuk dengan aneka glaze manis.'],
            ['Fudgy Brownies', 'Brownies', 22000, 35, true, 'Brownies cokelat pekat dengan tekstur fudgy.'],
        ];

        foreach ($products as [$name, $cat, $price, $stock, $best, $desc]) {
            Product::firstOrCreate(['slug' => Str::slug($name)], [
                'category_id' => $cats[$cat]->id,
                'name' => $name,
                'description' => $desc,
                'price' => $price,
                'stock' => $stock,
                'is_active' => true,
                'is_best_seller' => $best,
            ]);
        }

        // Placeholder pengaturan. Isi data rekening ASLI lewat /admin/settings.
        foreach (['bank_name', 'bank_account', 'bank_holder'] as $k) {
            if (! Setting::find($k)) {
                Setting::put($k, '');
            }
        }
    }
}
