<?php

namespace App\Support;

use App\Models\PlatformSetting;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class TenantProvisioner
{
    public static function createOwner(string $name, string $email, ?string $googleId): User
    {
        return DB::transaction(function () use ($name, $email, $googleId) {
            $now = now();
            $trialDays = PlatformSetting::trialDays();
            $tenant = Tenant::create([
                'slug' => Tenant::uniqueSlug(TenantDefaults::SETTINGS['office']),
                'plan' => "Trial {$trialDays} Hari",
                'trial_started_at' => $now,
                'trial_expires_at' => $now->copy()->addDays($trialDays),
            ]);

            $tenant->departments()->createMany(
                array_map(fn ($department) => $department + ['active' => true], TenantDefaults::DEPARTMENTS)
            );

            return $tenant->users()->create([
                'type' => User::TYPE_OWNER,
                'name' => $name ?: 'Administrator',
                'email' => strtolower($email),
                'google_id' => $googleId,
                'active' => true,
                'theme' => 'system',
            ]);
        });
    }
}
