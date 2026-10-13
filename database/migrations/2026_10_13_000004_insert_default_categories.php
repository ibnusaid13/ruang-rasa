<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /** Kategori bawaan toko. Aman dijalankan ulang (tidak membuat duplikat). */
    private array $categories = [
        'cake' => 'Cake',
        'puding' => 'Puding',
        'cookies' => 'Cookies',
        'pastry' => 'Pastry',
        'brownies' => 'Brownies',
    ];

    public function up(): void
    {
        foreach ($this->categories as $slug => $name) {
            DB::table('categories')->updateOrInsert(
                ['slug' => $slug],
                ['name' => $name, 'created_at' => now(), 'updated_at' => now()]
            );
        }
    }

    public function down(): void
    {
        // Sengaja kosong: kategori mungkin sudah dipakai produk.
    }
};
