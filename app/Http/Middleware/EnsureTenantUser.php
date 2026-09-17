<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenantUser
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user?->isSuperAdmin()) {
            return $request->expectsJson()
                ? response()->json(['message' => 'Halaman ini khusus akun kantor.'], 403)
                : redirect()->route('admin.dashboard');
        }

        if (! $user || ! ($user->isOwner() || $user->isTeam()) || ! $user->active || ! $user->tenant) {
            if ($user) {
                Auth::guard('web')->logout();
            }

            return $request->expectsJson()
                ? response()->json(['message' => 'Sesi berakhir. Silakan masuk kembali.'], 401)
                : redirect()->route('login');
        }

        $tenant = $user->tenant;
        $slug = (string) $request->route('slug');

        if ($slug !== $tenant->slug) {
            $isOwnOldSlug = $tenant->slugRedirects()->where('slug', $slug)->exists();

            if ($request->expectsJson()) {
                abort_unless($isOwnOldSlug, 404);
            } else {
                $suffix = substr($request->path(), strlen($slug));

                return redirect()->to(rtrim($tenant->appUrl(), '/').preg_replace('#^/app#', '', $suffix));
            }
        }

        URL::defaults(['slug' => $tenant->slug]);
        $request->route()->forgetParameter('slug');

        return $next($request);
    }
}
