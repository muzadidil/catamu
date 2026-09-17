<?php

namespace App\Support;

use App\Models\Tenant;
use Illuminate\Support\Carbon;

class Format
{
    public static function number(int|float|null $value): string
    {
        return number_format((float) $value, 0, ',', '.');
    }

    public static function rupiah(int|float|null $value): string
    {
        return 'Rp'.self::number($value);
    }

    /** Rp950 • Rp99 rb • Rp1,2 jt • Rp3,4 M */
    public static function rupiahCompact(int|float|null $value): string
    {
        $value = (float) $value;
        foreach ([1_000_000_000 => 'M', 1_000_000 => 'jt', 1_000 => 'rb'] as $unit => $suffix) {
            if ($value >= $unit) {
                $scaled = $value / $unit;
                $decimals = $scaled < 10 && floor($scaled) != $scaled ? 1 : 0;

                return 'Rp'.number_format($scaled, $decimals, ',', '.').' '.$suffix;
            }
        }

        return self::rupiah($value);
    }

    public static function date(?Carbon $date, string $format = 'd M Y'): string
    {
        return $date ? $date->translatedFormat($format) : '-';
    }

    public static function initials(string $name): string
    {
        return collect(preg_split('/\s+/', trim($name)))->filter()->take(2)
            ->map(fn ($word) => mb_strtoupper(mb_substr($word, 0, 1)))->implode('') ?: 'CA';
    }

    /** Kelas badge admin untuk status langganan kantor. */
    public static function stateTone(Tenant $tenant): string
    {
        if ($tenant->lifetime) {
            return 'accent';
        }

        return match ($tenant->subscriptionState()) {
            'active', 'active-pending' => 'success',
            'trial', 'trial-pending', 'pending' => 'warning',
            default => 'neutral',
        };
    }

    public static function validUntil(Tenant $tenant): string
    {
        if ($tenant->lifetime) {
            return 'Tanpa batas';
        }
        $state = $tenant->subscriptionState();
        $date = str_starts_with($state, 'active') || $tenant->activated_at ? $tenant->expires_at : $tenant->trial_expires_at;

        return self::date($date);
    }
}
