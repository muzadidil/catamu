<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSuperAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            // Sengaja ke halaman masuk milik domain admin sendiri, bukan ke
            // login domain utama, supaya backoffice punya alamat yang utuh.
            return redirect()->route('admin.login');
        }

        if (! $user->isSuperAdmin()) {
            abort(403, 'Halaman ini khusus super admin.');
        }

        return $next($request);
    }
}
