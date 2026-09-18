<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Satu baris komisi afiliasi, dicatat saat pembayaran kantor yang diajak disetujui. */
class ReferralCommission extends Model
{
    protected $guarded = ['id'];

    public function referrer(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'referrer_tenant_id');
    }

    public function referred(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'referred_tenant_id');
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function toClient(): array
    {
        return [
            'id' => (string) $this->id,
            'office' => $this->referred_office,
            'baseAmount' => (int) $this->base_amount,
            'rate' => (int) $this->rate,
            'amount' => (int) $this->amount,
            'createdAt' => $this->created_at?->toISOString(),
        ];
    }
}
