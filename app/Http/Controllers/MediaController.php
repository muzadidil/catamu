<?php

namespace App\Http\Controllers;

use App\Models\Guest;
use App\Models\Payment;
use App\Models\User;
use App\Support\QrisImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MediaController extends Controller
{
    private const HEADERS = ['Cache-Control' => 'private, max-age=31536000, immutable', 'X-Content-Type-Options' => 'nosniff'];

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

    private function file(?string $path): StreamedResponse
    {
        abort_unless($path && Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->response($path, null, self::HEADERS);
    }
}
