<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    public const TYPE_OWNER = 'owner';
    public const TYPE_TEAM = 'team';
    public const TYPE_SUPER_ADMIN = 'superadmin';

    public const ROLES = ['Admin', 'Resepsionis', 'Viewer'];

    protected $guarded = ['id'];

    protected $hidden = ['password', 'pin', 'remember_token'];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'pin' => 'hashed',
            'permissions' => 'array',
            'active' => 'boolean',
            'last_login_at' => 'datetime',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function isOwner(): bool
    {
        return $this->type === self::TYPE_OWNER;
    }

    public function isTeam(): bool
    {
        return $this->type === self::TYPE_TEAM;
    }

    public function isSuperAdmin(): bool
    {
        return $this->type === self::TYPE_SUPER_ADMIN
            && in_array(strtolower((string) $this->email), config('catamu.super_admin_emails'), true);
    }

    /** Port dari CATAMU_FLOW.roleAccess() di JS. */
    public function access(): array
    {
        if ($this->isOwner()) {
            return [
                'guestsView' => true, 'guestsWrite' => true, 'reports' => true, 'settings' => true,
                'teamManage' => true, 'subscriptionManage' => true, 'accountManage' => true,
            ];
        }

        $role = $this->role ?: 'Resepsionis';
        $permissions = $this->permissions ?? [];
        $viewer = $role === 'Viewer';
        $admin = $role === 'Admin';

        return [
            'guestsView' => ! empty($permissions['guests']),
            'guestsWrite' => ! empty($permissions['guests']) && ! $viewer,
            'reports' => ! empty($permissions['reports']),
            'settings' => ! empty($permissions['settings']) && ! $viewer,
            'teamManage' => $admin && ! empty($permissions['settings']),
            'subscriptionManage' => false,
            'accountManage' => false,
        ];
    }

    public function hasCapability(string $capability): bool
    {
        return (bool) ($this->access()[$capability] ?? false);
    }

    public static function phoneKey(?string $phone): ?string
    {
        $digits = preg_replace('/\D/', '', (string) $phone);

        return $digits === '' ? null : $digits;
    }
}
