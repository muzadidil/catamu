<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Tenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class DepartmentController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $this->authorizeCapability($request, 'settings', 'mengubah departemen');
        $tenant = $this->tenant($request);

        $department = $tenant->departments()->create($this->validatedAttributes($request, $tenant));

        return response()->json(['department' => $department->toClient()], 201);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $this->authorizeCapability($request, 'settings', 'mengubah departemen');
        $tenant = $this->tenant($request);
        $department = $tenant->departments()->findOrFail($id);

        $department->update($this->validatedAttributes($request, $tenant, $department));

        return response()->json(['department' => $department->toClient()]);
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        $this->authorizeCapability($request, 'settings', 'menghapus departemen');
        $this->tenant($request)->departments()->findOrFail($id)->delete();

        return response()->json(['ok' => true]);
    }

    private function validatedAttributes(Request $request, Tenant $tenant, ?Department $current = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'code' => ['nullable', 'string', 'max:12'],
            'description' => ['nullable', 'string', 'max:255'],
            'active' => ['boolean'],
        ], [
            'name.required' => 'Nama departemen wajib diisi.',
            'code.max' => 'Kode departemen maksimal 12 karakter.',
        ]);

        $name = trim($data['name']);
        $code = isset($data['code']) ? mb_strtoupper(trim($data['code'])) : null;
        $others = $tenant->departments()->when($current, fn ($query) => $query->whereKeyNot($current->id));

        if ((clone $others)->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])->exists()) {
            throw ValidationException::withMessages(['name' => 'Nama departemen sudah digunakan.']);
        }
        if ($code && (clone $others)->whereRaw('LOWER(code) = ?', [mb_strtolower($code)])->exists()) {
            throw ValidationException::withMessages(['code' => 'Kode departemen sudah digunakan.']);
        }

        return [
            'name' => $name,
            'code' => $code ?: null,
            'description' => $data['description'] ?? null,
            'active' => $request->boolean('active', true),
        ];
    }
}
