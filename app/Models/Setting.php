<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Key-value sederhana untuk pengaturan yang bisa diubah admin. */
class Setting extends Model
{
    protected $primaryKey = 'key';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $guarded = [];

    public static function get(string $key, mixed $default = null): mixed
    {
        $row = static::query()->find($key);

        return ($row && $row->value !== null && $row->value !== '') ? $row->value : $default;
    }

    public static function put(string $key, ?string $value): void
    {
        static::query()->updateOrCreate(['key' => $key], ['value' => $value]);
    }

    public static function deliveryFee(): int
    {
        return (int) static::get('delivery_fee', config('shop.delivery_fee'));
    }
}
