<?php

namespace App\Support;

class TenantDefaults
{
    public const GUEST_FIELDS = [
        'name' => ['visible' => true, 'required' => true, 'locked' => true],
        'phone' => ['visible' => true, 'required' => true],
        'company' => ['visible' => true, 'required' => false],
        'email' => ['visible' => true, 'required' => false],
        'vehicle' => ['visible' => true, 'required' => false],
        'meet' => ['visible' => true, 'required' => true],
        'people' => ['visible' => true, 'required' => false],
        'purpose' => ['visible' => true, 'required' => true],
        'notes' => ['visible' => true, 'required' => false],
        'photo' => ['visible' => true, 'required' => false],
        'signature' => ['visible' => true, 'required' => false],
    ];

    public const SETTINGS = [
        'office' => 'Kantor Utama',
        'address' => 'Alamat kantor belum diatur',
        'hours' => '08.00–17.00 WIB',
        'officer' => 'Resepsionis',
        'phone' => '',
        'email' => '',
        'dateFormat' => 'long',
        'timeFormat' => '24',
        'defaultPeople' => 1,
        'guestVoiceEnabled' => true,
    ];

    public const NOTIFICATIONS = [
        'inApp' => true,
        'browser' => false,
        'checkIn' => true,
        'checkOut' => true,
        'updates' => true,
    ];

    public const DEPARTMENTS = [
        ['name' => 'Direksi', 'code' => 'DIR', 'description' => 'Pimpinan dan manajemen'],
        ['name' => 'HRD', 'code' => 'HRD', 'description' => 'Sumber daya manusia'],
        ['name' => 'Keuangan', 'code' => 'FIN', 'description' => 'Keuangan dan administrasi'],
        ['name' => 'Operasional', 'code' => 'OPS', 'description' => 'Operasional kantor'],
        ['name' => 'IT', 'code' => 'IT', 'description' => 'Teknologi informasi'],
        ['name' => 'Marketing', 'code' => 'MKT', 'description' => 'Pemasaran dan relasi'],
        ['name' => 'Gudang', 'code' => 'WHS', 'description' => 'Gudang dan logistik'],
        ['name' => 'Umum', 'code' => 'GA', 'description' => 'General affairs dan layanan umum'],
    ];

    public static function settings(?array $stored): array
    {
        $settings = array_merge(self::SETTINGS, array_intersect_key($stored ?? [], self::SETTINGS));
        $settings['guestFields'] = self::guestFields($stored['guestFields'] ?? null);

        return $settings;
    }

    public static function notifications(?array $stored): array
    {
        $prefs = self::NOTIFICATIONS;
        foreach ($prefs as $key => $default) {
            if (array_key_exists($key, $stored ?? [])) {
                $prefs[$key] = (bool) $stored[$key];
            }
        }

        return $prefs;
    }

    public static function guestFields(mixed $source): array
    {
        $raw = is_array($source) ? $source : [];
        $normalized = [];

        foreach (self::GUEST_FIELDS as $key => $base) {
            $current = is_array($raw[$key] ?? null) ? $raw[$key] : [];
            $locked = $base['locked'] ?? false;
            $visible = $locked ? true : (array_key_exists('visible', $current) ? $current['visible'] !== false : $base['visible']);
            $required = $locked ? true : ($visible && (array_key_exists('required', $current) ? $current['required'] === true : $base['required']));

            $normalized[$key] = ['visible' => $visible, 'required' => $required];
            if ($locked) {
                $normalized[$key]['locked'] = true;
            }
        }

        return $normalized;
    }
}
