<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\ClientState;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class TeamController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $this->authorizeCapability($request, 'teamManage', 'mengelola anggota tim');

        $member = new User(['type' => User::TYPE_TEAM, 'tenant_id' => $this->tenant($request)->id]);
        $this->fillMember($request, $member);
        $member->save();

        return response()->json(['member' => ClientState::teamMember($member)], 201);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $this->authorizeCapability($request, 'teamManage', 'mengelola anggota tim');

        $member = $this->tenant($request)->teamMembers()->findOrFail($id);
        $this->fillMember($request, $member);
        $member->save();

        return response()->json(['member' => ClientState::teamMember($member)]);
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        $this->authorizeCapability($request, 'teamManage', 'menghapus anggota tim');
        $this->tenant($request)->teamMembers()->findOrFail($id)->delete();

        return response()->json(['ok' => true]);
    }

    private function fillMember(Request $request, User $member): void
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'email' => ['nullable', 'email', 'max:150'],
            'phone' => ['nullable', 'string', 'max:30'],
            'role' => ['required', Rule::in(User::ROLES)],
            'active' => ['boolean'],
            'permissions.guests' => ['boolean'],
            'permissions.reports' => ['boolean'],
            'permissions.settings' => ['boolean'],
            'password' => [$member->exists ? 'nullable' : 'required', 'string', 'min:6', 'max:200', 'confirmed'],
        ], [
            'name.required' => 'Nama anggota wajib diisi.',
            'email.email' => 'Format email anggota tidak valid.',
            'password.required' => 'Password anggota wajib diisi.',
            'password.min' => 'Password minimal 6 karakter.',
            'password.confirmed' => 'Konfirmasi password tidak sama.',
        ]);

        $email = isset($data['email']) ? strtolower(trim($data['email'])) : null;
        $phoneKey = User::phoneKey($data['phone'] ?? null);

        if ($email && User::where('email', $email)->whereKeyNot($member->id)->exists()) {
            throw ValidationException::withMessages(['email' => 'Email sudah digunakan anggota lain.']);
        }
        if ($phoneKey && User::where('phone_key', $phoneKey)->whereKeyNot($member->id)->exists()) {
            throw ValidationException::withMessages(['phone' => 'Nomor HP sudah digunakan anggota lain.']);
        }

        $member->fill([
            'name' => trim($data['name']),
            'email' => $email,
            'phone' => $data['phone'] ?? null,
            'phone_key' => $phoneKey,
            'role' => $data['role'],
            'active' => $request->boolean('active', true),
            'permissions' => [
                'guests' => $request->boolean('permissions.guests'),
                'reports' => $request->boolean('permissions.reports'),
                'settings' => $data['role'] === 'Viewer' ? false : $request->boolean('permissions.settings'),
            ],
        ]);

        if (! empty($data['password'])) {
            $member->password = $data['password'];
        }
    }
}
