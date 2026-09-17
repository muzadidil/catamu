<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Guest;
use App\Support\GuestInput;
use App\Support\ImageStore;
use App\Support\Notifier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class GuestController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $this->authorizeCapability($request, 'guestsWrite', 'menyimpan data tamu');
        $tenant = $this->tenant($request);

        $guest = new Guest(GuestInput::attributes($request, $tenant));
        $guest->tenant_id = $tenant->id;
        $guest->created_by = $request->user()->id;
        $guest->check_in = now();
        GuestInput::applyMedia($request, $guest, $tenant);
        $guest->save();

        $notification = Notifier::notify($tenant, 'checkin', 'Tamu baru check-in', "{$guest->name} datang untuk {$guest->purpose}", $guest->id, $guest->meet);

        return response()->json(['guest' => $guest->toClient(), 'notification' => $notification], 201);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $this->authorizeCapability($request, 'guestsWrite', 'mengedit data tamu');
        $tenant = $this->tenant($request);
        $guest = $tenant->guests()->findOrFail($id);

        $guest->fill(GuestInput::attributes($request, $tenant));
        GuestInput::applyMedia($request, $guest, $tenant);
        $guest->save();

        $notification = Notifier::notify($tenant, 'update', 'Data tamu diperbarui', "{$guest->name} • {$guest->purpose}", $guest->id, $guest->meet);

        return response()->json(['guest' => $guest->toClient(), 'notification' => $notification]);
    }

    public function checkout(Request $request, string $id): JsonResponse
    {
        $this->authorizeCapability($request, 'guestsWrite', 'melakukan check-out tamu');
        $tenant = $this->tenant($request);
        $guest = $tenant->guests()->findOrFail($id);

        if ($guest->check_out) {
            abort(422, 'Tamu sudah check-out.');
        }

        $data = $request->validate(['note' => ['nullable', 'string', 'max:1000']]);
        $guest->check_out = now();
        $guest->checkout_note = $data['note'] ?? null;
        $guest->save();

        $notification = Notifier::notify($tenant, 'checkout', 'Tamu check-out', "{$guest->name} telah menyelesaikan kunjungan", $guest->id, $guest->meet);

        return response()->json(['guest' => $guest->toClient(), 'notification' => $notification]);
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        $this->authorizeCapability($request, 'guestsWrite', 'menghapus data tamu');
        $guest = $this->tenant($request)->guests()->findOrFail($id);

        ImageStore::delete($guest->photo_path);
        ImageStore::delete($guest->signature_path);
        $guest->delete();

        return response()->json(['ok' => true]);
    }

    public function destroyAll(Request $request): JsonResponse
    {
        $this->authorizeCapability($request, 'settings', 'menghapus seluruh data tamu');
        $tenant = $this->tenant($request);

        $tenant->guests()->delete();
        Storage::disk('local')->deleteDirectory(ImageStore::tenantDirectory($tenant->id, 'guests'));

        return response()->json(['ok' => true]);
    }
}
