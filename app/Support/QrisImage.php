<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;

class QrisImage
{
    public const PATH = 'platform/qris.png';

    public static function exists(): bool
    {
        return Storage::disk('local')->exists(self::PATH);
    }

    public static function url(string $routeName = 'media.qris'): ?string
    {
        if (! self::exists()) {
            return null;
        }

        return route($routeName, ['v' => Storage::disk('local')->lastModified(self::PATH)]);
    }
}
