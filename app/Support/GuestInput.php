<?php

namespace App\Support;

use App\Models\Guest;
use App\Models\Tenant;
use Illuminate\Http\Request;

/** Validasi data tamu sesuai "Atur Data Tamu" kantor, dipakai aplikasi kantor & cek-in mandiri. */
class GuestInput
{
    public static function attributes(Request $request, Tenant $tenant): array
    {
        $fields = TenantDefaults::guestFields($tenant->settings['guestFields'] ?? null);
        $presence = fn (string $key) => ($fields[$key]['visible'] && $fields[$key]['required']) ? 'required' : 'nullable';

        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'phone' => [$presence('phone'), 'string', 'max:30'],
            'company' => [$presence('company'), 'string', 'max:150'],
            'email' => [$presence('email'), 'email', 'max:150'],
            'vehicle' => [$presence('vehicle'), 'string', 'max:20'],
            'meet' => [$presence('meet'), 'string', 'max:150'],
            'departmentId' => ['nullable'],
            'purpose' => [$presence('purpose'), 'string', 'max:2000'],
            'people' => ['nullable', 'integer', 'min:1', 'max:1000'],
            'notes' => [$presence('notes'), 'string', 'max:1000'],
            'photo' => [$presence('photo'), 'string'],
            'signature' => [$presence('signature'), 'string'],
        ], [
            'name.required' => 'Nama lengkap wajib diisi.',
            'phone.required' => 'Nomor HP / WhatsApp wajib diisi.',
            'company.required' => 'Instansi / perusahaan wajib diisi.',
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tamu tidak valid.',
            'vehicle.required' => 'Plat nomor wajib diisi.',
            'meet.required' => 'Pilih departemen atau isi pihak yang ditemui.',
            'purpose.required' => 'Keperluan kunjungan wajib diisi.',
            'notes.required' => 'Catatan wajib diisi.',
            'photo.required' => 'Foto tamu wajib diisi.',
            'signature.required' => 'Tanda tangan tamu wajib diisi.',
            '*.max' => 'Isian :attribute terlalu panjang.',
        ]);

        $departmentId = null;
        $meet = $data['meet'] ?? null;
        if (! empty($data['departmentId'])) {
            $department = $tenant->departments()->where('active', true)->find($data['departmentId']);
            if ($department) {
                $departmentId = $department->id;
                $meet = $department->name;
            }
        }

        return [
            'name' => $data['name'],
            'phone' => $data['phone'] ?? null,
            'company' => $data['company'] ?? null,
            'email' => $data['email'] ?? null,
            'vehicle' => isset($data['vehicle']) ? mb_strtoupper($data['vehicle']) : null,
            'meet' => $meet,
            'department_id' => $departmentId,
            'purpose' => $data['purpose'] ?? null,
            'people' => max(1, (int) ($data['people'] ?? 1)),
            'notes' => $data['notes'] ?? null,
        ];
    }

    /**
     * Data URL = gambar baru, kosong = dihapus, URL lama = tidak berubah.
     */
    public static function applyMedia(Request $request, Guest $guest, Tenant $tenant): void
    {
        $directory = ImageStore::tenantDirectory($tenant->id, 'guests');

        foreach (['photo' => 'photo_path', 'signature' => 'signature_path'] as $field => $column) {
            $value = $request->input($field);

            if (is_string($value) && str_starts_with($value, 'data:')) {
                $path = ImageStore::storeDataUrl($value, $directory, $field, 5 * 1024 * 1024, $field === 'signature');
                ImageStore::delete($guest->{$column});
                $guest->{$column} = $path;
            } elseif ($value === null || $value === '') {
                ImageStore::delete($guest->{$column});
                $guest->{$column} = null;
            }
        }
    }
}
