<?php

namespace App\Http\Controllers;

use App\Models\Guest;
use App\Models\Payment;
use App\Models\Tenant;
use App\Models\User;
use App\Support\QrisImage;
use App\Support\TenantBranding;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MediaController extends Controller
{
    private const HEADERS = ['Cache-Control' => 'private, max-age=31536000, immutable', 'X-Content-Type-Options' => 'nosniff'];

    private const PUBLIC_HEADERS = ['Cache-Control' => 'public, max-age=31536000, immutable', 'X-Content-Type-Options' => 'nosniff'];

    public function guest(Request $request, Guest $guest, string $kind): StreamedResponse
    {
        $user = $request->user();
        $access = $user->access();
        abort_unless($user->tenant_id === $guest->tenant_id && ($access['guestsView'] || $access['reports']), 404);

        return $this->file($kind === 'photo' ? $guest->photo_path : $guest->signature_path);
    }

    public function user(Request $request, User $user): StreamedResponse
    {
        abort_unless($request->user()->tenant_id !== null && $request->user()->tenant_id === $user->tenant_id, 404);

        return $this->file($user->photo_path);
    }

    public function payment(Request $request, Payment $payment): StreamedResponse
    {
        $viewer = $request->user();
        abort_unless($viewer->isSuperAdmin() || ($viewer->isOwner() && $viewer->tenant_id === $payment->tenant_id), 404);

        return $this->file($payment->proof_path);
    }

    public function qris(): StreamedResponse
    {
        return $this->file(QrisImage::exists() ? QrisImage::PATH : null);
    }

    /**
     * Logo dan gambar slide halaman login kantor. Publik karena dilihat sebelum
     * user masuk; URL-nya membawa penanda versi sehingga aman di-cache lama.
     */
    public function brandingLogo(string $slug): StreamedResponse
    {
        return $this->publicFile(TenantBranding::of($this->tenantBySlug($slug))['logo']);
    }

    public function brandingSlide(string $slug, int $index): StreamedResponse
    {
        $slides = TenantBranding::of($this->tenantBySlug($slug))['slides'];

        return $this->publicFile($slides[$index]['image'] ?? null);
    }

    /** Ikon PWA dan favicon kantor, dibuat saat logo diunggah. */
    public function brandingIcon(string $slug, int $size): StreamedResponse
    {
        $branding = TenantBranding::of($this->tenantBySlug($slug));

        return $this->publicFile($size >= 512 ? $branding['icon512'] : $branding['icon192']);
    }

    private function tenantBySlug(string $slug): Tenant
    {
        return Tenant::resolveSlug($slug) ?? abort(404);
    }

    private function file(?string $path): StreamedResponse
    {
        abort_unless($path && Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->response($path, null, self::HEADERS);
    }

    private function publicFile(?string $path): StreamedResponse
    {
        abort_unless($path && Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->response($path, null, self::PUBLIC_HEADERS);
    }
}
