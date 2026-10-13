<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\DB;

class Order extends Model
{
    public const STATUS_LABELS = [
        'pending' => 'Menunggu',
        'processing' => 'Diproses',
        'ready' => 'Siap Diambil/Dikirim',
        'completed' => 'Selesai',
        'cancelled' => 'Dibatalkan',
    ];

    protected $guarded = [];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'shipping_fee' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class);
    }

    public function cart(): BelongsTo
    {
        return $this->belongsTo(Cart::class);
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_LABELS[$this->status] ?? $this->status;
    }

    /**
     * Alur status yang valid. Status "processing" untuk pesanan non-COD
     * hanya boleh setelah pembayaran diverifikasi (paid). Pesanan yang
     * sudah dibayar tidak bisa dibatalkan (fitur refund belum ada).
     */
    public function allowedNextStatuses(): array
    {
        $payment = $this->payment;
        $isCod = $payment?->method === 'cod';
        $isPaid = $payment?->status === 'paid';

        return match ($this->status) {
            'pending' => array_values(array_filter([
                ($isCod || $isPaid) ? 'processing' : null,
                $isPaid ? null : 'cancelled',
            ])),
            'processing' => array_values(array_filter([
                'ready',
                $isPaid ? null : 'cancelled',
            ])),
            'ready' => ['completed'],
            default => [],
        };
    }

    /**
     * Pindah status dengan validasi alur. Pembatalan mengembalikan stok.
     * Menyelesaikan pesanan COD menandai pembayaran COD sebagai diterima.
     */
    public function transitionTo(string $newStatus): void
    {
        if (! in_array($newStatus, $this->allowedNextStatuses(), true)) {
            throw new \DomainException('Perubahan status tidak valid dari "' . $this->status_label . '".');
        }

        DB::transaction(function () use ($newStatus) {
            if ($newStatus === 'cancelled') {
                foreach ($this->items as $item) {
                    if ($item->product_id) {
                        Product::whereKey($item->product_id)->increment('stock', $item->quantity);
                    }
                }
                $this->payment?->update(['status' => 'cancelled']);
            }

            if ($newStatus === 'completed' && $this->payment?->method === 'cod' && $this->payment->status !== 'paid') {
                $this->payment->update(['status' => 'paid', 'paid_at' => now()]);
            }

            $this->update(['status' => $newStatus]);
        });
    }
}
