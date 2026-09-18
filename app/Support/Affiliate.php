<?php

namespace App\Support;

use App\Models\Payment;
use App\Models\PayoutRequest;
use App\Models\PlatformSetting;
use App\Models\ReferralCommission;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Program afiliasi: kantor membagikan link undangan, dan mendapat komisi dari
 * tiap pembayaran kantor yang diajaknya yang disetujui super admin.
 *
 * Saldo tidak disimpan sebagai satu angka yang diubah-ubah, melainkan dihitung
 * dari dua buku: komisi yang masuk dikurangi pencairan yang tertahan/terbayar.
 * Dengan begitu saldo tidak bisa melenceng karena satu update yang gagal.
 */
class Affiliate
{
    /** Tanpa 0/O/1/I/L supaya kode tidak salah ketik saat disalin manual. */
    private const ALPHABET = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';

    private const CODE_LENGTH = 8;

    /** 4-24 karakter, huruf/angka/strip, tidak diawali atau diakhiri strip. */
    public const CODE_REGEX = '/^[A-Za-z0-9][A-Za-z0-9-]{2,22}[A-Za-z0-9]$/';

    public static function rate(): int
    {
        return PlatformSetting::affiliateRate();
    }

    public static function minPayout(): int
    {
        return PlatformSetting::affiliateMinPayout();
    }

    public static function normalizeCode(string $code): string
    {
        return strtoupper(trim($code));
    }

    /** Kode dibuat sekali saat pertama dibutuhkan, lalu tetap sampai owner menggantinya. */
    public static function codeFor(Tenant $tenant): string
    {
        if ($tenant->referral_code) {
            return $tenant->referral_code;
        }

        do {
            $code = self::randomCode();
        } while (self::codeTaken($code, $tenant->id));

        $tenant->forceFill(['referral_code' => $code])->save();

        return $code;
    }

    public static function codeTaken(string $code, ?int $ignoreTenantId = null): bool
    {
        return Tenant::where('referral_code', self::normalizeCode($code))
            ->when($ignoreTenantId, fn ($query) => $query->whereKeyNot($ignoreTenantId))
            ->exists();
    }

    public static function resolve(string $code): ?Tenant
    {
        $code = self::normalizeCode($code);

        return $code === '' ? null : Tenant::where('referral_code', $code)->first();
    }

    public static function linkFor(Tenant $tenant): string
    {
        return route('join', ['code' => self::codeFor($tenant)]);
    }

    /**
     * Komisi dicatat untuk tiap pembayaran yang disetujui, termasuk perpanjangan.
     * Kolom payment_id unik, jadi satu pembayaran tidak bisa menghasilkan komisi ganda.
     */
    public static function recordCommission(Payment $payment): ?ReferralCommission
    {
        $referred = $payment->tenant;
        $referrer = $referred?->referrer;

        if (! $referred || ! $referrer || $referrer->id === $referred->id) {
            return null;
        }

        if (ReferralCommission::where('payment_id', $payment->id)->exists()) {
            return null;
        }

        $rate = self::rate();
        $amount = (int) floor($payment->amount * $rate / 100);

        if ($amount <= 0) {
            return null;
        }

        $commission = ReferralCommission::create([
            'referrer_tenant_id' => $referrer->id,
            'referred_tenant_id' => $referred->id,
            'referred_office' => $referred->officeName(),
            'payment_id' => $payment->id,
            'base_amount' => $payment->amount,
            'rate' => $rate,
            'amount' => $amount,
        ]);

        Notifier::notify($referrer, 'affiliate', 'Komisi afiliasi masuk',
            Format::rupiah($amount).' dari pembayaran '.$referred->officeName().'. Cek menu Afiliasi untuk mencairkan.');

        return $commission;
    }

    /** @return array{earned:int,paid:int,onHold:int,available:int} */
    public static function balance(Tenant $tenant): array
    {
        $earned = (int) $tenant->referralCommissions()->sum('amount');
        $paid = (int) $tenant->payoutRequests()->where('status', PayoutRequest::STATUS_APPROVED)->sum('amount');
        $onHold = (int) $tenant->payoutRequests()->where('status', PayoutRequest::STATUS_PENDING)->sum('amount');

        return [
            'earned' => $earned,
            'paid' => $paid,
            'onHold' => $onHold,
            'available' => max(0, $earned - $paid - $onHold),
        ];
    }

    /** Pemeriksaan saldo dikunci bersama penulisannya agar dua pengajuan tidak saling menyusul. */
    public static function requestPayout(Tenant $tenant, User $user, int $amount): PayoutRequest
    {
        return DB::transaction(function () use ($tenant, $user, $amount) {
            $locked = Tenant::whereKey($tenant->id)->lockForUpdate()->firstOrFail();

            if (! $locked->hasPayoutAccount()) {
                abort(422, 'Lengkapi rekening pencairan terlebih dahulu.');
            }

            if ($locked->payoutRequests()->where('status', PayoutRequest::STATUS_PENDING)->exists()) {
                abort(422, 'Masih ada pengajuan pencairan yang menunggu diproses.');
            }

            $minimum = self::minPayout();
            if ($amount < $minimum) {
                abort(422, 'Pencairan minimal '.Format::rupiah($minimum).'.');
            }

            $available = self::balance($locked)['available'];
            if ($amount > $available) {
                abort(422, 'Saldo komisi yang bisa dicairkan hanya '.Format::rupiah($available).'.');
            }

            return $locked->payoutRequests()->create([
                'user_id' => $user->id,
                'amount' => $amount,
                'bank' => $locked->payout_bank,
                'account' => $locked->payout_account,
                'account_name' => $locked->payout_name,
                'status' => PayoutRequest::STATUS_PENDING,
            ]);
        });
    }

    public static function approvePayout(PayoutRequest $payout, User $reviewer): void
    {
        $payout->update([
            'status' => PayoutRequest::STATUS_APPROVED,
            'reviewed_by' => $reviewer->id,
            'reviewed_at' => now(),
        ]);

        Notifier::notify($payout->tenant, 'affiliate', 'Pencairan komisi disetujui',
            Format::rupiah($payout->amount).' dikirim ke '.$payout->bank.' '.$payout->account.' a.n. '.$payout->account_name.'.');
    }

    public static function rejectPayout(PayoutRequest $payout, User $reviewer, ?string $note): void
    {
        $payout->update([
            'status' => PayoutRequest::STATUS_REJECTED,
            'review_note' => $note,
            'reviewed_by' => $reviewer->id,
            'reviewed_at' => now(),
        ]);

        Notifier::notify($payout->tenant, 'affiliate', 'Pencairan komisi ditolak',
            ($note ? "Alasan: {$note}. " : '').'Saldo komisi kembali tersedia dan dapat diajukan ulang.');
    }

    /** Data menu Afiliasi milik Owner. */
    public static function summary(Tenant $tenant): array
    {
        $referrals = $tenant->referrals()->with('pendingPayment')->orderByDesc('id')->get();

        return [
            'code' => self::codeFor($tenant),
            'link' => self::linkFor($tenant),
            'rate' => self::rate(),
            'minPayout' => self::minPayout(),
            'planAmount' => (int) config('catamu.plans')[0]['amount'],
            'visits' => (int) $tenant->referral_visits,
            'signups' => $referrals->count(),
            'subscribed' => $referrals->filter(fn (Tenant $office) => $office->activated_at !== null)->count(),
            'balance' => self::balance($tenant),
            'account' => [
                'bank' => $tenant->payout_bank ?? '',
                'number' => $tenant->payout_account ?? '',
                'name' => $tenant->payout_name ?? '',
            ],
            'offices' => $referrals->map(fn (Tenant $office) => [
                'name' => $office->officeName(),
                'status' => $office->subscriptionLabel(),
                'subscribed' => $office->activated_at !== null,
                'joinedAt' => $office->created_at?->toISOString(),
            ])->all(),
            'commissions' => $tenant->referralCommissions()->latest('id')->limit(50)->get()->map->toClient()->all(),
            'payouts' => $tenant->payoutRequests()->latest('id')->limit(20)->get()->map->toClient()->all(),
        ];
    }

    private static function randomCode(): string
    {
        $code = '';
        for ($i = 0; $i < self::CODE_LENGTH; $i++) {
            $code .= self::ALPHABET[random_int(0, strlen(self::ALPHABET) - 1)];
        }

        return $code;
    }
}
