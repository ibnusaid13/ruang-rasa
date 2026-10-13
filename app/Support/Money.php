<?php

namespace App\Support;

/**
 * Semua perhitungan uang memakai integer "sen" agar bebas error floating point.
 * Database menyimpan DECIMAL(12,2).
 */
class Money
{
    public static function toCents(string|int|float|null $value): int
    {
        return (int) round(((float) $value) * 100);
    }

    public static function fromCents(int $cents): string
    {
        return number_format($cents / 100, 2, '.', '');
    }

    public static function rp(string|int|float|null $value): string
    {
        return 'Rp ' . number_format((float) $value, 0, ',', '.');
    }
}
