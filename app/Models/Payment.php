<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    public const STATUS_LABELS = [
        'pending' => 'Menunggu Pembayaran',
        'awaiting_verification' => 'Menunggu Verifikasi',
        'paid' => 'Pembayaran Berhasil',
        'rejected' => 'Ditolak',
        'failed' => 'Gagal',
        'cancelled' => 'Dibatalkan',
        'refunded' => 'Dikembalikan',
    ];

    protected $guarded = [];

    protected $casts = [
        'amount' => 'decimal:2',
        'paid_at' => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_LABELS[$this->status] ?? $this->status;
    }

    public function getMethodLabelAttribute(): string
    {
        return config("shop.payment_methods.{$this->method}.label", $this->method);
    }

    public function needsProof(): bool
    {
        return (bool) config("shop.payment_methods.{$this->method}.needs_proof", false);
    }

    /** Pelanggan boleh mengunggah/mengunggah ulang bukti hanya pada status ini. */
    public function canUploadProof(): bool
    {
        return $this->needsProof() && in_array($this->status, ['pending', 'rejected', 'awaiting_verification'], true);
    }
}
