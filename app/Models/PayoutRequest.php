<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Pengajuan pencairan saldo komisi afiliasi oleh Owner. */
class PayoutRequest extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['reviewed_at' => 'datetime'];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function statusLabel(): string
    {
        return [
            self::STATUS_PENDING => 'Menunggu',
            self::STATUS_APPROVED => 'Dicairkan',
            self::STATUS_REJECTED => 'Ditolak',
        ][$this->status] ?? $this->status;
    }

    public function toClient(): array
    {
        return [
            'id' => (string) $this->id,
            'amount' => (int) $this->amount,
            'bank' => $this->bank,
            'account' => $this->account,
            'accountName' => $this->account_name,
            'status' => $this->status,
            'statusLabel' => $this->statusLabel(),
            'note' => $this->review_note ?? '',
            'createdAt' => $this->created_at?->toISOString(),
            'reviewedAt' => $this->reviewed_at?->toISOString(),
        ];
    }
}
