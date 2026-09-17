<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use App\Models\User;
use App\Support\TenantProvisioner;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\View\View;
use Laravel\Socialite\Facades\Socialite;
use Throwable;

class AuthController extends Controller
{
    private const MAX_ATTEMPTS = 5;
    private const DECAY_SECONDS = 300;

    public function showLogin(Request $request): View|RedirectResponse
    {
        if ($user = $request->user()) {
            return redirect()->to($user->isSuperAdmin() ? route('admin.dashboard') : $user->tenant->appUrl());
        }

        $tenant = $this->lockedTenant($request);

        return view('auth.login', [
            'tenant' => $tenant,
            'officeName' => $tenant?->officeName() ?? 'CATAMU',
            'ownerHasPin' => (bool) $tenant?->owner?->pin,
        ]);
    }

    public function redirectToGoogle(): RedirectResponse
    {
        if (! $this->googleConfigured()) {
            return redirect()->route('login')->with('error', 'Login Google belum dikonfigurasi. Isi GOOGLE_CLIENT_ID dan GOOGLE_CLIENT_SECRET di file .env.');
        }

        return Socialite::driver('google')->redirectUrl(route('google.callback'))->redirect();
    }

    public function handleGoogleCallback(Request $request): RedirectResponse
    {
        if (! $this->googleConfigured()) {
            return redirect()->route('login');
        }

        try {
            $google = Socialite::driver('google')->redirectUrl(route('google.callback'))->user();
        } catch (Throwable $e) {
            report($e);

            return redirect()->route('login')->with('error', 'Login Google gagal atau dibatalkan. Silakan coba lagi.');
        }

        $email = strtolower((string) $google->getEmail());
        $verified = filter_var($google->user['email_verified'] ?? $google->user['verified_email'] ?? false, FILTER_VALIDATE_BOOL);
        if ($email === '' || ! $verified) {
            return redirect()->route('login')->with('error', 'Email akun Google belum terverifikasi.');
        }

        if (in_array($email, config('catamu.super_admin_emails'), true)) {
            $admin = User::where('email', $email)->first() ?? new User(['email' => $email]);
            if ($admin->exists && $admin->type !== User::TYPE_SUPER_ADMIN) {
                return redirect()->route('login')->with('error', 'Email super admin sudah dipakai akun kantor. Gunakan email Google lain untuk super admin.');
            }
            $admin->fill(['type' => User::TYPE_SUPER_ADMIN, 'name' => $google->getName() ?: $email, 'google_id' => $google->getId()])->save();
            $this->startSession($request, $admin);

            return redirect()->route('admin.dashboard');
        }

        $owner = User::where('google_id', $google->getId())->first();
        if ($owner && ! $owner->isOwner()) {
            return redirect()->route('login')->with('error', 'Akun Google ini tidak dapat digunakan untuk masuk sebagai Owner.');
        }

        if (! $owner) {
            $existing = User::where('email', $email)->first();
            if ($existing) {
                return redirect()->route('login')->with('error', $existing->isTeam()
                    ? 'Email ini terdaftar sebagai anggota tim. Pilih "Anggota Tim" untuk masuk.'
                    : 'Email ini sudah digunakan akun lain.');
            }
            $owner = TenantProvisioner::createOwner((string) $google->getName(), $email, $google->getId());
        }

        $this->startSession($request, $owner);

        return redirect()->to($owner->tenant->appUrl())->with('toast', "Masuk sebagai {$owner->name} (Owner).");
    }

    public function loginTeam(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'credential' => ['required', 'string', 'max:150'],
            'password' => ['required', 'string', 'max:200'],
        ], [
            'credential.required' => 'Isi email/nomor HP dan password anggota.',
            'password.required' => 'Isi email/nomor HP dan password anggota.',
        ]);

        $credential = trim($data['credential']);
        $key = 'team-login:'.sha1(strtolower($credential).'|'.$request->ip());

        if (RateLimiter::tooManyAttempts($key, self::MAX_ATTEMPTS)) {
            return $this->loginError('team', 'Terlalu banyak percobaan masuk. Coba lagi dalam '.RateLimiter::availableIn($key).' detik.');
        }

        $query = User::where('type', User::TYPE_TEAM);
        if (str_contains($credential, '@')) {
            $query->where('email', strtolower($credential));
        } else {
            $query->where('phone_key', User::phoneKey($credential) ?? '-');
        }
        $member = $query->first();

        if (! $member || ! $member->active || ! $member->password || ! $member->tenant || ! Hash::check($data['password'], $member->password)) {
            RateLimiter::hit($key, self::DECAY_SECONDS);

            return $this->loginError('team', 'Akun atau password anggota tidak sesuai.');
        }

        RateLimiter::clear($key);
        $this->startSession($request, $member);

        return redirect()->to($member->tenant->appUrl())->with('toast', "Masuk sebagai {$member->name} ({$member->role}).");
    }

    public function loginPin(Request $request): RedirectResponse
    {
        $tenant = $this->lockedTenant($request);
        $owner = $tenant?->owner;

        if (! $owner?->pin) {
            return $this->loginError('owner', 'PIN Owner belum dibuat. Masuk dengan Google.');
        }

        $key = 'pin-login:'.$tenant->id.'|'.$request->ip();
        if (RateLimiter::tooManyAttempts($key, self::MAX_ATTEMPTS)) {
            return $this->loginError('owner', 'Terlalu banyak percobaan PIN. Coba lagi dalam '.RateLimiter::availableIn($key).' detik atau masuk dengan Google.');
        }

        $pin = trim((string) $request->input('pin'));
        if (! preg_match('/^\d{4,6}$/', $pin) || ! Hash::check($pin, $owner->pin)) {
            RateLimiter::hit($key, self::DECAY_SECONDS);

            return $this->loginError('owner', 'PIN salah.');
        }

        RateLimiter::clear($key);
        $this->startSession($request, $owner);

        return redirect()->to($owner->tenant->appUrl())->with('toast', "Masuk sebagai {$owner->name} (Owner).");
    }

    public function lock(Request $request): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;

        Auth::guard('web')->logout();
        $request->session()->regenerate();
        $request->session()->regenerateToken();
        $request->session()->put('lock_tenant_id', $tenantId);

        return response()->json(['redirect' => route('login')]);
    }

    public function switchAccount(Request $request): RedirectResponse
    {
        $request->session()->forget('lock_tenant_id');

        return redirect()->route('login');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    private function startSession(Request $request, User $user): void
    {
        Auth::guard('web')->login($user, true);
        $request->session()->regenerate();

        if ($user->tenant_id) {
            $request->session()->put('lock_tenant_id', $user->tenant_id);
        } else {
            $request->session()->forget('lock_tenant_id');
        }

        $user->forceFill(['last_login_at' => now()])->save();
    }

    private function lockedTenant(Request $request): ?Tenant
    {
        $id = $request->session()->get('lock_tenant_id');

        return $id ? Tenant::find($id) : null;
    }

    private function loginError(string $mode, string $message): RedirectResponse
    {
        return redirect()->route('login')
            ->withInput(['mode' => $mode, 'credential' => request()->input('credential')])
            ->with('error', $message);
    }

    private function googleConfigured(): bool
    {
        return filled(config('services.google.client_id')) && filled(config('services.google.client_secret'));
    }
}
