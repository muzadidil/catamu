<?php

namespace App\Models;

use App\Support\ImageStore;
use App\Support\TenantDefaults;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Tenant extends Model
{
    public const WRITABLE_STATES = ['trial', 'trial-pending', 'active', 'active-pending'];

    private const RESERVED_SLUGS = ['app', 'api', 'admin', 'login', 'logout', 'media', 'assets', 'static', 'www', 'cekin', 'catamu', 'qr', 'css', 'js'];

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'lifetime' => 'boolean',
            'settings' => 'array',
            'notification_prefs' => 'array',
            'trial_started_at' => 'datetime',
            'trial_expires_at' => 'datetime',
            'activated_at' => 'datetime',
            'expires_at' => 'datetime',
            'approved_at' => 'datetime',
            'rating_updated_at' => 'datetime',
        ];
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function owner(): HasOne
    {
        return $this->hasOne(User::class)->where('type', User::TYPE_OWNER);
    }

    public function teamMembers(): HasMany
    {
        return $this->hasMany(User::class)->where('type', User::TYPE_TEAM);
    }

    public function departments(): HasMany
    {
        return $this->hasMany(Department::class);
    }

    public function guests(): HasMany
    {
        return $this->hasMany(Guest::class);
    }

    public function appNotifications(): HasMany
    {
        return $this->hasMany(AppNotification::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function feedbacks(): HasMany
    {
        return $this->hasMany(Feedback::class);
    }

    public function pendingPayment(): HasOne
    {
        return $this->hasOne(Payment::class)->where('status', Payment::STATUS_PENDING)->latestOfMany();
    }

    public function latestReviewedPayment(): HasOne
    {
        return $this->hasOne(Payment::class)->whereIn('status', [Payment::STATUS_APPROVED, Payment::STATUS_REJECTED])->latestOfMany('reviewed_at');
    }

    public function officeName(): string
    {
        return $this->resolvedSettings()['office'];
    }

    public function resolvedSettings(): array
    {
        return TenantDefaults::settings($this->settings);
    }

    public function resolvedNotificationPrefs(): array
    {
        return TenantDefaults::notifications($this->notification_prefs);
    }

    public function slugRedirects(): HasMany
    {
        return $this->hasMany(TenantSlugRedirect::class);
    }

    public static function resolveSlug(string $slug): ?self
    {
        return static::where('slug', $slug)->first()
            ?? TenantSlugRedirect::where('slug', $slug)->first()?->tenant;
    }

    public static function baseSlug(string $officeName): string
    {
        $base = trim(Str::limit(Str::slug($officeName), 60, ''), '-') ?: 'kantor';

        return in_array($base, self::RESERVED_SLUGS, true) ? "{$base}-kantor" : $base;
    }

    public static function uniqueSlug(string $officeName, ?int $ignoreTenantId = null): string
    {
        $base = static::baseSlug($officeName);
        $slug = $base;

        for ($i = 2; static::slugTaken($slug, $ignoreTenantId); $i++) {
            $slug = "{$base}-{$i}";
        }

        return $slug;
    }

    private static function slugTaken(string $slug, ?int $ignoreTenantId): bool
    {
        return static::where('slug', $slug)->when($ignoreTenantId, fn ($query) => $query->whereKeyNot($ignoreTenantId))->exists()
            || TenantSlugRedirect::where('slug', $slug)->when($ignoreTenantId, fn ($query) => $query->where('tenant_id', '!=', $ignoreTenantId))->exists();
    }

    /** Slug mengikuti nama kantor; slug lama disimpan agar link & QR lama tetap dialihkan. */
    public function syncSlugWithOfficeName(): void
    {
        $base = static::baseSlug($this->officeName());
        if ($this->slug === $base || preg_match('/^'.preg_quote($base, '/').'-\d+$/', (string) $this->slug)) {
            return;
        }

        $newSlug = static::uniqueSlug($this->officeName(), $this->id);

        DB::transaction(function () use ($newSlug) {
            $this->slugRedirects()->where('slug', $newSlug)->delete();
            if ($this->slug) {
                TenantSlugRedirect::updateOrCreate(['slug' => $this->slug], ['tenant_id' => $this->id]);
            }
            $this->forceFill(['slug' => $newSlug])->save();
        });
    }

    public function checkinUrl(): string
    {
        return route('cekin.show', ['slug' => $this->slug]);
    }

    public function appUrl(): string
    {
        return route('app', ['slug' => $this->slug]);
    }

    /** Hapus kantor beserta seluruh data dan file privatnya. */
    public function purge(): void
    {
        DB::transaction(fn () => $this->delete());
        Storage::disk('local')->deleteDirectory(ImageStore::tenantDirectory($this->id));
    }

    public function scopeLifetimePlan(Builder $query): void
    {
        $query->where('lifetime', true);
    }

    public function scopeActivePlan(Builder $query): void
    {
        $query->where('lifetime', false)->where('expires_at', '>', now());
    }

    public function scopeInTrial(Builder $query): void
    {
        $query->where('lifetime', false)
            ->whereNull('activated_at')
            ->where('trial_expires_at', '>', now())
            ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '<=', now()));
    }

    public function scopeAwaitingVerification(Builder $query): void
    {
        $query->whereHas('payments', fn ($payments) => $payments->where('status', Payment::STATUS_PENDING));
    }

    public function scopeExpiredPlan(Builder $query): void
    {
        $query->where('lifetime', false)
            ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '<=', now()))
            ->where(fn ($q) => $q->whereNotNull('activated_at')->orWhereNull('trial_expires_at')->orWhere('trial_expires_at', '<=', now()));
    }

    public function subscriptionState(?Carbon $now = null): string
    {
        $now ??= now();
        $pending = $this->pendingPayment !== null;

        if ($this->lifetime) {
            return $pending ? 'active-pending' : 'active';
        }

        if ($this->expires_at && $this->expires_at->gt($now)) {
            return $pending ? 'active-pending' : 'active';
        }
        if (! $this->activated_at && $this->trial_expires_at && $this->trial_expires_at->gt($now)) {
            return $pending ? 'trial-pending' : 'trial';
        }

        return $pending ? 'pending' : 'expired';
    }

    public function subscriptionLabel(): string
    {
        if ($this->lifetime) {
            return 'Lifetime';
        }

        return [
            'trial' => 'Trial Aktif', 'trial-pending' => 'Trial + Pending', 'active' => 'Aktif',
            'active-pending' => 'Aktif + Pending', 'pending' => 'Menunggu Verifikasi', 'expired' => 'Kedaluwarsa',
        ][$this->subscriptionState()];
    }

    public function isWritable(): bool
    {
        return in_array($this->subscriptionState(), self::WRITABLE_STATES, true);
    }
}
