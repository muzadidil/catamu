<?php

namespace App\Support;

use App\Models\Tenant;

/**
 * Branding kantor untuk halaman login khusus kantor: cekin.catamu.com/{slug}/login.
 *
 * Disimpan di kolom JSON settings tenant supaya tidak perlu tabel baru, sementara
 * file gambarnya tetap di disk privat dan disajikan lewat MediaController. URL
 * gambar membawa penanda versi dari nama filenya, jadi cache browser ikut
 * berganti begitu Owner mengunggah gambar baru.
 */
class TenantBranding
{
    public const MAX_SLIDES = 6;

    public static function of(Tenant $tenant): array
    {
        return self::normalize($tenant->settings['branding'] ?? null);
    }

    public static function normalize(mixed $stored): array
    {
        $stored = is_array($stored) ? $stored : [];
        $slides = [];

        foreach (array_values(is_array($stored['slides'] ?? null) ? $stored['slides'] : []) as $slide) {
            if (! is_array($slide) || count($slides) >= self::MAX_SLIDES) {
                continue;
            }

            $slides[] = [
                'image' => self::path($slide['image'] ?? null),
                'eyebrow' => self::text($slide['eyebrow'] ?? '', 40),
                'title' => self::text($slide['title'] ?? '', 80),
                'text' => self::text($slide['text'] ?? '', 220),
            ];
        }

        return ['logo' => self::path($stored['logo'] ?? null), 'slides' => $slides];
    }

    public static function save(Tenant $tenant, array $branding): array
    {
        $normalized = self::normalize($branding);

        $tenant->settings = array_merge($tenant->settings ?? [], ['branding' => $normalized]);
        $tenant->save();

        return $normalized;
    }

    /** Bentuk siap pakai di Blade maupun JSON state aplikasi. */
    public static function forView(Tenant $tenant): array
    {
        $branding = self::of($tenant);
        $slides = [];

        foreach ($branding['slides'] as $index => $slide) {
            $slides[] = [
                'eyebrow' => $slide['eyebrow'],
                'title' => $slide['title'],
                'text' => $slide['text'],
                'url' => $slide['image'] ? self::slideUrl($tenant, $index, $slide['image']) : null,
            ];
        }

        return [
            'office' => $tenant->officeName(),
            'slug' => $tenant->slug,
            'logoUrl' => $branding['logo'] ? self::logoUrl($tenant, $branding['logo']) : null,
            'loginUrl' => route('tenant.login', ['slug' => $tenant->slug]),
            'slides' => $slides,
        ];
    }

    public static function logoUrl(Tenant $tenant, string $path): string
    {
        return route('media.branding.logo', ['slug' => $tenant->slug, 'v' => self::version($path)]);
    }

    public static function slideUrl(Tenant $tenant, int $index, string $path): string
    {
        return route('media.branding.slide', ['slug' => $tenant->slug, 'index' => $index, 'v' => self::version($path)]);
    }

    private static function version(string $path): string
    {
        return substr(md5($path), 0, 8);
    }

    private static function path(mixed $value): ?string
    {
        return is_string($value) && $value !== '' ? $value : null;
    }

    private static function text(mixed $value, int $max): string
    {
        return mb_substr(trim((string) $value), 0, $max);
    }
}
