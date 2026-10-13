<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SettingController extends Controller
{
    private const KEYS = ['bank_name', 'bank_account', 'bank_holder', 'transfer_note', 'shop_address', 'shop_phone'];

    public function edit()
    {
        $values = [];
        foreach (self::KEYS as $key) {
            $values[$key] = Setting::get($key, '');
        }

        return view('admin.settings', [
            'values' => $values,
            'deliveryFee' => Setting::deliveryFee(),
            'qrisImage' => Setting::get('qris_image'),
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'bank_name' => ['nullable', 'string', 'max:80'],
            'bank_account' => ['nullable', 'string', 'max:40'],
            'bank_holder' => ['nullable', 'string', 'max:100'],
            'transfer_note' => ['nullable', 'string', 'max:500'],
            'shop_address' => ['nullable', 'string', 'max:300'],
            'shop_phone' => ['nullable', 'string', 'max:30'],
            'delivery_fee' => ['required', 'integer', 'min:0', 'max:1000000'],
            'qris_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:3072'],
        ]);

        foreach (self::KEYS as $key) {
            Setting::put($key, $data[$key] ?? null);
        }
        Setting::put('delivery_fee', (string) $data['delivery_fee']);

        if ($request->boolean('remove_qris') && Setting::get('qris_image')) {
            Storage::disk('public')->delete(Setting::get('qris_image'));
            Setting::put('qris_image', null);
        }

        if ($request->hasFile('qris_image')) {
            if ($old = Setting::get('qris_image')) {
                Storage::disk('public')->delete($old);
            }
            Setting::put('qris_image', $request->file('qris_image')->store('qris', 'public'));
        }

        return back()->with('success', 'Pengaturan disimpan.');
    }
}
