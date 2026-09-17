<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Support\ClientState;
use App\Support\TenantDefaults;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SettingsController extends Controller
{
    public function updateApplication(Request $request): JsonResponse
    {
        $this->authorizeCapability($request, 'settings', 'mengubah pengaturan aplikasi');
        $tenant = $this->tenant($request);

        $data = $request->validate([
            'office' => ['nullable', 'string', 'max:150'],
            'hours' => ['nullable', 'string', 'max:100'],
            'address' => ['nullable', 'string', 'max:500'],
            'officer' => ['nullable', 'string', 'max:150'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:150'],
            'theme' => ['required', Rule::in(['system', 'light', 'dark'])],
            'dateFormat' => ['required', Rule::in(['long', 'short'])],
            'timeFormat' => ['required', Rule::in(['24', '12'])],
            'defaultPeople' => ['nullable', 'integer', 'min:1', 'max:100'],
            'guestVoiceEnabled' => ['boolean'],
        ], [
            'email.email' => 'Format email kantor tidak valid.',
            'defaultPeople.max' => 'Default jumlah pengunjung maksimal 100.',
        ]);

        $defaults = TenantDefaults::SETTINGS;
        $tenant->settings = array_merge($tenant->settings ?? [], [
            'office' => $data['office'] ?? $defaults['office'],
            'hours' => $data['hours'] ?? $defaults['hours'],
            'address' => $data['address'] ?? $defaults['address'],
            'officer' => $data['officer'] ?? $defaults['officer'],
            'phone' => $data['phone'] ?? '',
            'email' => $data['email'] ?? '',
            'dateFormat' => $data['dateFormat'],
            'timeFormat' => $data['timeFormat'],
            'defaultPeople' => max(1, (int) ($data['defaultPeople'] ?? 1)),
            'guestVoiceEnabled' => $request->boolean('guestVoiceEnabled'),
        ]);
        $tenant->save();
        $tenant->syncSlugWithOfficeName();

        $request->user()->update(['theme' => $data['theme']]);

        return response()->json([
            'settings' => ClientState::settings($tenant, $request->user()),
            'links' => ClientState::links($tenant),
        ]);
    }

    public function updateGuestFields(Request $request): JsonResponse
    {
        $this->authorizeCapability($request, 'settings', 'mengubah field registrasi');
        $tenant = $this->tenant($request);

        $request->validate(['guestFields' => ['required', 'array']]);
        $tenant->settings = array_merge($tenant->settings ?? [], [
            'guestFields' => TenantDefaults::guestFields($request->input('guestFields')),
        ]);
        $tenant->save();

        return response()->json(['settings' => ClientState::settings($tenant, $request->user())]);
    }

    public function updateNotifications(Request $request): JsonResponse
    {
        $this->authorizeCapability($request, 'settings', 'mengubah notifikasi');
        $tenant = $this->tenant($request);

        $request->validate([
            'inApp' => ['boolean'], 'browser' => ['boolean'], 'checkIn' => ['boolean'],
            'checkOut' => ['boolean'], 'updates' => ['boolean'],
        ]);
        $tenant->notification_prefs = TenantDefaults::notifications($request->only(array_keys(TenantDefaults::NOTIFICATIONS)));
        $tenant->save();

        return response()->json(['notificationPrefs' => $tenant->resolvedNotificationPrefs()]);
    }

    public function updateTheme(Request $request): JsonResponse
    {
        $data = $request->validate(['theme' => ['required', Rule::in(['system', 'light', 'dark'])]]);
        $request->user()->update(['theme' => $data['theme']]);

        return response()->json(['ok' => true]);
    }
}
