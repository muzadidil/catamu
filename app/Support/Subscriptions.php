<?php

namespace App\Support;

use App\Models\Payment;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/** Perubahan paket langganan oleh super admin. */
class Subscriptions
{
    public static function extend(Tenant $tenant, int $days, string $planName): Tenant
    {
        return DB::transaction(function () use ($tenant, $days, $planName) {
            $locked = Tenant::whereKey($tenant->id)->lockForUpdate()->firstOrFail();
            $now = now();
            $base = $locked->expires_at && $locked->expires_at->gt($now) ? $locked->expires_at->copy() : $now->copy();

            $locked->update([
                'plan' => $locked->lifetime ? 'Lifetime' : $planName,
                'activated_at' => $locked->activated_at ?? $now,
                'expires_at' => $base->addDays($days),
                'approved_at' => $now,
            ]);

            return $locked;
        });
    }

    public static function approve(Payment $payment, User $reviewer): Tenant
    {
        $tenant = DB::transaction(function () use ($payment, $reviewer) {
            $tenant = self::extend($payment->tenant, $payment->days, $payment->plan_name);
            $payment->update(['status' => Payment::STATUS_APPROVED, 'reviewed_by' => $reviewer->id, 'reviewed_at' => now()]);

            return $tenant;
        });

        Notifier::notify($tenant, 'subscription', 'Pembayaran disetujui', self::activeMessage($tenant));

        // Kantor pengajak dapat komisi dari tiap pembayaran ini, termasuk perpanjangan.
        Affiliate::recordCommission($payment);

        return $tenant;
    }

    public static function reject(Payment $payment, User $reviewer, ?string $note): void
    {
        $payment->update([
            'status' => Payment::STATUS_REJECTED,
            'review_note' => $note,
            'reviewed_by' => $reviewer->id,
            'reviewed_at' => now(),
        ]);

        Notifier::notify($payment->tenant, 'subscription', 'Pembayaran ditolak',
            $note ? "Alasan: {$note}" : 'Silakan periksa bukti pembayaran lalu kirim ulang.');
    }

    public static function activateYearly(Tenant $tenant): Tenant
    {
        $plan = config('catamu.plans')[0];
        $tenant = self::extend($tenant, $plan['days'], $plan['name']);

        Notifier::notify($tenant, 'subscription', 'Langganan diaktifkan admin', self::activeMessage($tenant));

        return $tenant;
    }

    public static function toggleLifetime(Tenant $tenant): Tenant
    {
        if ($tenant->lifetime) {
            $tenant->update([
                'lifetime' => false,
                'plan' => $tenant->expires_at?->isFuture() ? config('catamu.plans')[0]['name'] : $tenant->plan,
            ]);
            Notifier::notify($tenant, 'subscription', 'Paket Lifetime dinonaktifkan',
                $tenant->expires_at?->isFuture() ? self::activeMessage($tenant) : 'Silakan perpanjang langganan agar data dapat diubah kembali.');

            return $tenant;
        }

        $tenant->update([
            'lifetime' => true,
            'plan' => 'Lifetime',
            'activated_at' => $tenant->activated_at ?? now(),
            'approved_at' => now(),
        ]);
        Notifier::notify($tenant, 'subscription', 'Paket Lifetime aktif', 'Kantor Anda kini memakai paket Lifetime tanpa batas waktu.');

        return $tenant;
    }

    private static function activeMessage(Tenant $tenant): string
    {
        return $tenant->lifetime
            ? 'Kantor Anda memakai paket Lifetime tanpa batas waktu.'
            : "Paket {$tenant->plan} aktif sampai {$tenant->expires_at->translatedFormat('d F Y')}.";
    }
}
