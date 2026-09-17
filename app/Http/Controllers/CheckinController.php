<?php

namespace App\Http\Controllers;

use App\Models\Guest;
use App\Models\Tenant;
use App\Support\GuestInput;
use App\Support\Notifier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Form cek-in mandiri untuk tamu di cekin.catamu.com/{nama-kantor}. */
class CheckinController extends Controller
{
    public function show(string $slug): View|RedirectResponse
    {
        $tenant = Tenant::resolveSlug($slug) ?? abort(404);

        if ($tenant->slug !== $slug) {
            return redirect()->route('cekin.show', ['slug' => $tenant->slug], 301);
        }

        $settings = $tenant->resolvedSettings();

        return view('cekin.show', [
            'tenant' => $tenant,
            'settings' => $settings,
            'fields' => $settings['guestFields'],
            'departments' => $tenant->departments()->where('active', true)->orderBy('name')->get(['id', 'name', 'code']),
            'available' => $tenant->isWritable(),
        ]);
    }

    public function store(Request $request, string $slug): JsonResponse
    {
        $tenant = Tenant::resolveSlug($slug) ?? abort(404);

        if (! $tenant->isWritable()) {
            abort(403, 'Cek-in mandiri sedang tidak tersedia. Silakan lapor ke resepsionis.');
        }
        if (filled($request->input('website'))) {
            abort(422, 'Permintaan tidak valid.');
        }

        $guest = new Guest(GuestInput::attributes($request, $tenant));
        $guest->tenant_id = $tenant->id;
        $guest->source = 'self';
        $guest->check_in = now();
        GuestInput::applyMedia($request, $guest, $tenant);
        $guest->save();

        Notifier::notify($tenant, 'checkin', 'Tamu cek-in mandiri', "{$guest->name} datang untuk {$guest->purpose}", $guest->id, $guest->meet);

        return response()->json(['name' => $guest->name], 201);
    }
}
