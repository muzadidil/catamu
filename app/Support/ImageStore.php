<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ImageStore
{
    private const MAX_DIMENSION = 4096;

    /**
     * Simpan gambar dari data URL (hasil canvas/FileReader di browser).
     * Gambar selalu di-encode ulang dengan GD supaya file yang tersimpan pasti gambar murni.
     */
    public static function storeDataUrl(string $dataUrl, string $directory, string $field, int $maxBytes = 5 * 1024 * 1024, bool $png = false): string
    {
        if (! preg_match('#^data:image/(png|jpe?g|webp);base64,#i', $dataUrl, $match)) {
            throw ValidationException::withMessages([$field => 'Format gambar tidak didukung. Gunakan JPG, PNG, atau WEBP.']);
        }

        $binary = base64_decode(substr($dataUrl, strlen($match[0])), true);
        if ($binary === false || $binary === '') {
            throw ValidationException::withMessages([$field => 'Gambar tidak valid.']);
        }
        if (strlen($binary) > $maxBytes) {
            throw ValidationException::withMessages([$field => 'Ukuran gambar terlalu besar.']);
        }

        return self::storeBinary($binary, $directory, $field, $png);
    }

    public static function storeBinary(string $binary, string $directory, string $field, bool $png = false): string
    {
        $info = @getimagesizefromstring($binary);
        if (! $info
            || ! in_array($info[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP], true)
            || $info[0] > self::MAX_DIMENSION || $info[1] > self::MAX_DIMENSION) {
            throw ValidationException::withMessages([$field => 'Gambar tidak valid atau resolusinya terlalu besar.']);
        }

        $image = @imagecreatefromstring($binary);
        if (! $image) {
            throw ValidationException::withMessages([$field => 'Gambar tidak dapat dibaca.']);
        }

        ob_start();
        if ($png) {
            imagealphablending($image, false);
            imagesavealpha($image, true);
            imagepng($image, null, 6);
            $extension = 'png';
        } else {
            $width = imagesx($image);
            $height = imagesy($image);
            $canvas = imagecreatetruecolor($width, $height);
            imagefill($canvas, 0, 0, imagecolorallocate($canvas, 255, 255, 255));
            imagecopy($canvas, $image, 0, 0, 0, 0, $width, $height);
            imagejpeg($canvas, null, 85);
            imagedestroy($canvas);
            $extension = 'jpg';
        }
        $encoded = ob_get_clean();
        imagedestroy($image);

        $path = trim($directory, '/').'/'.Str::uuid().'.'.$extension;
        Storage::disk('local')->put($path, $encoded);

        return $path;
    }

    /**
     * Versi persegi untuk ikon PWA/favicon: gambar dimuat utuh di tengah kanvas
     * transparan, jadi logo apa pun bentuknya tidak terpotong maupun gepeng.
     */
    public static function storeSquarePng(string $binary, string $directory, string $field, int $size): string
    {
        $source = @imagecreatefromstring($binary);
        if (! $source) {
            throw ValidationException::withMessages([$field => 'Gambar tidak dapat dibaca.']);
        }

        $width = imagesx($source);
        $height = imagesy($source);
        $scale = min($size / $width, $size / $height);
        $targetWidth = max(1, (int) round($width * $scale));
        $targetHeight = max(1, (int) round($height * $scale));

        $canvas = imagecreatetruecolor($size, $size);
        imagealphablending($canvas, false);
        imagesavealpha($canvas, true);
        imagefilledrectangle($canvas, 0, 0, $size, $size, imagecolorallocatealpha($canvas, 0, 0, 0, 127));
        imagecopyresampled(
            $canvas,
            $source,
            intdiv($size - $targetWidth, 2),
            intdiv($size - $targetHeight, 2),
            0,
            0,
            $targetWidth,
            $targetHeight,
            $width,
            $height,
        );
        imagedestroy($source);

        ob_start();
        imagepng($canvas, null, 6);
        $encoded = ob_get_clean();
        imagedestroy($canvas);

        $path = trim($directory, '/').'/'.Str::uuid().'.png';
        Storage::disk('local')->put($path, $encoded);

        return $path;
    }

    public static function delete(?string $path): void
    {
        if ($path) {
            Storage::disk('local')->delete($path);
        }
    }

    public static function tenantDirectory(int $tenantId, string $sub = ''): string
    {
        return rtrim("tenants/{$tenantId}/{$sub}", '/');
    }
}
