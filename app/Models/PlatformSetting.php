<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlatformSetting extends Model
{
    protected $primaryKey = 'key';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = [];

    public static function trialDays(): int
    {
        $value = static::find('trial_days')?->value;

        return $value === null ? (int) config('catamu.trial_days') : max(1, (int) $value);
    }

    public static function affiliateRate(): int
    {
        $value = static::find('affiliate_rate')?->value;

        return $value === null ? (int) config('catamu.affiliate.rate') : max(0, min(100, (int) $value));
    }

    public static function affiliateMinPayout(): int
    {
        $value = static::find('affiliate_min_payout')?->value;

        return $value === null ? (int) config('catamu.affiliate.min_payout') : max(0, (int) $value);
    }

    public static function put(string $key, mixed $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => (string) $value]);
    }
}
