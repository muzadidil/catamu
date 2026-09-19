<?php

namespace App\Support;

use App\Models\Tenant;
use App\Models\User;

/** Menyusun data yang sebelumnya dibaca JS dari localStorage. */
class ClientState
{
    public static function for(User $user): array
    {
        $tenant = $user->tenant;
        $tenant->loadMissing(['pendingPayment', 'latestReviewedPayment']);
        $access = $user->access();
        $owner = $user->isOwner() ? $user : $tenant->owner;

        return [
            'actor' => self::actor($user),
            'settings' => self::settings($tenant, $user),
            'profile' => self::profile($owner, $user->isOwner()),
            'team' => $access['teamManage']
                ? $tenant->teamMembers()->orderBy('name')->get()->map(fn (User $member) => self::teamMember($member))->all()
                : [],
            'departments' => $tenant->departments()->orderBy('name')->get()->map->toClient()->all(),
            'guests' => ($access['guestsView'] || $access['reports'])
                ? $tenant->guests()->orderByDesc('check_in')->get()->map->toClient()->all()
                : [],
            'subscription' => self::subscription($tenant),
            'affiliate' => $user->isOwner() ? Affiliate::summary($tenant) : null,
            'branding' => $access['settings'] ? TenantBranding::forView($tenant) : null,
            'feedbacks' => $tenant->feedbacks()->latest('id')->get()->map->toClient()->all(),
            'rating' => self::rating($tenant),
            'notifications' => $tenant->appNotifications()->latest('id')->limit(config('catamu.notification_limit'))->get()->map->toClient()->all(),
            'notificationPrefs' => $tenant->resolvedNotificationPrefs(),
            'platform' => [
                'qrisImageUrl' => QrisImage::url(),
            ],
            'links' => self::links($tenant),
        ];
    }

    public static function links(Tenant $tenant): array
    {
        return [
            'appUrl' => $tenant->appUrl(),
            'checkinUrl' => $tenant->checkinUrl(),
            'qrPosterUrl' => route('app.qr', ['slug' => $tenant->slug]),
        ];
    }

    public static function actor(User $user): array
    {
        if ($user->isOwner()) {
            return [
                'type' => 'owner', 'id' => (string) $user->id, 'name' => $user->name, 'role' => 'Owner',
                'permissions' => ['guests' => true, 'reports' => true, 'settings' => true],
            ];
        }

        return ['type' => 'team'] + self::teamMember($user);
    }

    public static function settings(Tenant $tenant, User $user): array
    {
        return $tenant->resolvedSettings() + ['theme' => $user->theme ?: 'system'];
    }

    public static function profile(?User $owner, bool $full): array
    {
        $profile = [
            'name' => $owner?->name ?: 'Administrator',
            'hasPin' => (bool) $owner?->pin,
        ];

        if ($full && $owner) {
            $profile += [
                'email' => $owner->email ?? '',
                'phone' => $owner->phone ?? '',
                'photo' => $owner->photo_path
                    ? route('media.user', ['user' => $owner->id, 'v' => substr(md5($owner->photo_path), 0, 8)])
                    : '',
            ];
        }

        return $profile;
    }

    public static function teamMember(User $member): array
    {
        $permissions = $member->permissions ?? [];

        return [
            'id' => (string) $member->id,
            'name' => $member->name,
            'email' => $member->email ?? '',
            'phone' => $member->phone ?? '',
            'role' => $member->role ?: 'Resepsionis',
            'active' => $member->active,
            'permissions' => [
                'guests' => ! empty($permissions['guests']),
                'reports' => ! empty($permissions['reports']),
                'settings' => ! empty($permissions['settings']),
            ],
            'hasPassword' => (bool) $member->password,
            'createdAt' => $member->created_at?->toISOString(),
            'updatedAt' => $member->updated_at?->toISOString(),
        ];
    }

    public static function subscription(Tenant $tenant): array
    {
        $pending = $tenant->pendingPayment;
        $reviewed = $tenant->latestReviewedPayment;

        return [
            'plan' => $tenant->plan,
            'lifetime' => $tenant->lifetime,
            'trialDays' => $tenant->trial_started_at && $tenant->trial_expires_at
                ? max(1, (int) round($tenant->trial_started_at->diffInDays($tenant->trial_expires_at)))
                : config('catamu.trial_days'),
            'trialStartedAt' => $tenant->trial_started_at?->toISOString(),
            'trialExpiresAt' => $tenant->trial_expires_at?->toISOString(),
            'activatedAt' => $tenant->activated_at?->toISOString(),
            'expiresAt' => $tenant->expires_at?->toISOString(),
            'approvedAt' => $tenant->approved_at?->toISOString(),
            'pendingPayment' => $pending ? [
                'id' => (string) $pending->id,
                'days' => $pending->days,
                'planName' => $pending->plan_name,
                'amount' => $pending->amount,
                'method' => $pending->method,
                'submittedAt' => $pending->created_at?->toISOString(),
            ] : null,
            'lastRejectedPayment' => (! $pending && $reviewed?->status === 'rejected' && $reviewed->reviewed_at?->gt(now()->subDays(14))) ? [
                'planName' => $reviewed->plan_name,
                'note' => $reviewed->review_note ?? '',
                'reviewedAt' => $reviewed->reviewed_at?->toISOString(),
            ] : null,
        ];
    }

    public static function rating(Tenant $tenant): array
    {
        return [
            'score' => (int) $tenant->rating_score,
            'comment' => $tenant->rating_comment ?? '',
            'updatedAt' => $tenant->rating_updated_at?->toISOString(),
        ];
    }
}
