<?php

namespace Tests;

use App\Models\Tenant;
use App\Models\User;
use App\Support\TenantProvisioner;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /** URL API aplikasi kantor milik user yang sedang login di test. */
    protected function api(string $path, ?User $user = null): string
    {
        $user ??= auth()->user();

        return 'http://'.config('catamu.domains.cekin').'/'.$user->tenant->slug.'/app/api'.$path;
    }

    protected function createOwner(string $email = 'owner@example.com'): User
    {
        return TenantProvisioner::createOwner('Owner Kantor', $email, 'google-'.md5($email));
    }

    protected function createTeamMember(Tenant $tenant, string $role = 'Resepsionis', array $permissions = ['guests' => true], array $attributes = []): User
    {
        return $tenant->users()->create(array_merge([
            'type' => User::TYPE_TEAM,
            'name' => "Anggota {$role}",
            'email' => strtolower($role).uniqid().'@example.com',
            'password' => 'rahasia123',
            'role' => $role,
            'permissions' => $permissions,
            'active' => true,
        ], $attributes));
    }

    protected function pngDataUrl(int $width = 12, int $height = 12): string
    {
        $image = imagecreatetruecolor($width, $height);
        imagefill($image, 0, 0, imagecolorallocate($image, 200, 30, 30));
        ob_start();
        imagepng($image);
        $binary = ob_get_clean();
        imagedestroy($image);

        return 'data:image/png;base64,'.base64_encode($binary);
    }

    protected function guestPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Budi Santoso',
            'phone' => '081234567890',
            'company' => 'PT Maju',
            'email' => '',
            'vehicle' => 'n 1234 ab',
            'meet' => 'Keuangan',
            'departmentId' => '',
            'purpose' => 'Rapat vendor',
            'people' => 2,
            'notes' => '',
            'photo' => '',
            'signature' => '',
        ], $overrides);
    }
}
