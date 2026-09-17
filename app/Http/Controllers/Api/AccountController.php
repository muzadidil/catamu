<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\ClientState;
use App\Support\ImageStore;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AccountController extends Controller
{
    public function update(Request $request): JsonResponse
    {
        $this->authorizeCapability($request, 'accountManage', 'mengubah akun Owner', false);
        $owner = $request->user();

        $data = $request->validate([
            'name' => ['nullable', 'string', 'max:150'],
            'email' => ['nullable', 'email', 'max:150'],
            'phone' => ['nullable', 'string', 'max:30'],
            'pin' => ['nullable', 'regex:/^\d{4,6}$/', 'confirmed'],
        ], [
            'email.email' => 'Format email tidak valid.',
            'pin.regex' => 'PIN harus 4–6 digit angka.',
            'pin.confirmed' => 'Konfirmasi PIN tidak sama.',
        ]);

        $email = isset($data['email']) ? strtolower(trim($data['email'])) : null;
        if ($email && User::where('email', $email)->whereKeyNot($owner->id)->exists()) {
            throw ValidationException::withMessages(['email' => 'Email sudah digunakan akun lain.']);
        }

        $owner->fill([
            'name' => trim($data['name'] ?? '') ?: 'Administrator',
            'email' => $email,
            'phone' => $data['phone'] ?? null,
        ]);
        if (! empty($data['pin'])) {
            $owner->pin = $data['pin'];
        }
        $owner->save();

        return $this->profileResponse($owner);
    }

    public function updatePhoto(Request $request): JsonResponse
    {
        $this->authorizeCapability($request, 'accountManage', 'mengubah foto profil Owner', false);
        $owner = $request->user();
        $request->validate(['photo' => ['required', 'string']]);

        $path = ImageStore::storeDataUrl($request->input('photo'), ImageStore::tenantDirectory($owner->tenant_id, 'users'), 'photo', 1024 * 1024);
        ImageStore::delete($owner->photo_path);
        $owner->update(['photo_path' => $path]);

        return $this->profileResponse($owner);
    }

    public function destroyPhoto(Request $request): JsonResponse
    {
        $this->authorizeCapability($request, 'accountManage', 'menghapus foto profil Owner', false);
        $owner = $request->user();

        ImageStore::delete($owner->photo_path);
        $owner->update(['photo_path' => null]);

        return $this->profileResponse($owner);
    }

    public function destroyPin(Request $request): JsonResponse
    {
        $this->authorizeCapability($request, 'accountManage', 'menghapus PIN Owner', false);
        $owner = $request->user();

        if (! $owner->pin) {
            abort(422, 'PIN belum dibuat.');
        }
        $owner->pin = null;
        $owner->save();

        return $this->profileResponse($owner);
    }

    public function destroy(Request $request): JsonResponse
    {
        $this->authorizeCapability($request, 'accountManage', 'menghapus akun Owner', false);

        if (trim((string) $request->input('confirmation')) !== 'HAPUS AKUN') {
            abort(422, 'Ketik HAPUS AKUN untuk melanjutkan.');
        }

        $tenant = $this->tenant($request);

        Auth::guard('web')->logout();
        $tenant->purge();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(['redirect' => route('login')]);
    }

    private function profileResponse(User $owner): JsonResponse
    {
        return response()->json(['profile' => ClientState::profile($owner, true)]);
    }
}
