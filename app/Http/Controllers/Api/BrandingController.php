<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Support\ImageStore;
use App\Support\TenantBranding;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Logo dan slide halaman login kantor ({slug}/login) yang dikelola Owner.
 *
 * Slide disimpan sebagai daftar berurutan di settings tenant; indeks slide
 * dipakai sebagai identitas, jadi setiap perubahan selalu menulis ulang seluruh
 * daftar agar urutan dan file gambarnya tidak pernah melenceng satu sama lain.
 */
class BrandingController extends Controller
{
    public function updateLogo(Request $request): JsonResponse
    {
        $tenant = $this->authorizedTenant($request, 'mengubah logo kantor');
        $request->validate(['logo' => ['required', 'string']], ['logo.required' => 'Pilih gambar logo terlebih dahulu.']);

        $branding = TenantBranding::of($tenant);
        $path = ImageStore::storeDataUrl(
            $request->input('logo'),
            ImageStore::tenantDirectory($tenant->id, 'branding'),
            'logo',
            2 * 1024 * 1024,
            true,
        );

        ImageStore::delete($branding['logo']);
        $branding['logo'] = $path;

        return $this->respond($tenant, $branding);
    }

    public function destroyLogo(Request $request): JsonResponse
    {
        $tenant = $this->authorizedTenant($request, 'menghapus logo kantor');

        $branding = TenantBranding::of($tenant);
        ImageStore::delete($branding['logo']);
        $branding['logo'] = null;

        return $this->respond($tenant, $branding);
    }

    public function storeSlide(Request $request): JsonResponse
    {
        $tenant = $this->authorizedTenant($request, 'menambah slide halaman login');
        $branding = TenantBranding::of($tenant);

        if (count($branding['slides']) >= TenantBranding::MAX_SLIDES) {
            throw ValidationException::withMessages([
                'slide' => 'Maksimal '.TenantBranding::MAX_SLIDES.' slide. Hapus salah satu sebelum menambah.',
            ]);
        }

        $data = $this->slideInput($request);
        $branding['slides'][] = [
            'image' => $data['image'] ? $this->storeSlideImage($tenant, $data['image']) : null,
            'eyebrow' => $data['eyebrow'],
            'title' => $data['title'],
            'text' => $data['text'],
        ];

        return $this->respond($tenant, $branding);
    }

    public function updateSlide(Request $request, string $slug, int $index): JsonResponse
    {
        $tenant = $this->authorizedTenant($request, 'mengubah slide halaman login');
        $branding = TenantBranding::of($tenant);
        $slide = $branding['slides'][$index] ?? abort(404);

        $data = $this->slideInput($request);
        if ($data['image']) {
            ImageStore::delete($slide['image']);
            $slide['image'] = $this->storeSlideImage($tenant, $data['image']);
        }

        $branding['slides'][$index] = [
            'image' => $slide['image'],
            'eyebrow' => $data['eyebrow'],
            'title' => $data['title'],
            'text' => $data['text'],
        ];

        return $this->respond($tenant, $branding);
    }

    public function destroySlide(Request $request, string $slug, int $index): JsonResponse
    {
        $tenant = $this->authorizedTenant($request, 'menghapus slide halaman login');
        $branding = TenantBranding::of($tenant);
        $slide = $branding['slides'][$index] ?? abort(404);

        ImageStore::delete($slide['image']);
        unset($branding['slides'][$index]);
        $branding['slides'] = array_values($branding['slides']);

        return $this->respond($tenant, $branding);
    }

    /** Urutan baru dikirim sebagai daftar indeks lama, mis. [2,0,1]. */
    public function reorderSlides(Request $request): JsonResponse
    {
        $tenant = $this->authorizedTenant($request, 'mengurutkan slide halaman login');
        $branding = TenantBranding::of($tenant);

        $request->validate(['order' => ['required', 'array']]);
        $order = array_map('intval', $request->input('order'));

        if (count($order) !== count($branding['slides']) || count(array_unique($order)) !== count($order)) {
            throw ValidationException::withMessages(['order' => 'Urutan slide tidak valid.']);
        }

        $reordered = [];
        foreach ($order as $from) {
            $reordered[] = $branding['slides'][$from] ?? abort(404);
        }
        $branding['slides'] = $reordered;

        return $this->respond($tenant, $branding);
    }

    private function authorizedTenant(Request $request, string $label): Tenant
    {
        $this->authorizeCapability($request, 'settings', $label);

        return $this->tenant($request);
    }

    private function slideInput(Request $request): array
    {
        $data = $request->validate([
            'image' => ['nullable', 'string'],
            'eyebrow' => ['nullable', 'string', 'max:40'],
            'title' => ['nullable', 'string', 'max:80'],
            'text' => ['nullable', 'string', 'max:220'],
        ], [
            'eyebrow.max' => 'Label kecil maksimal 40 karakter.',
            'title.max' => 'Judul maksimal 80 karakter.',
            'text.max' => 'Deskripsi maksimal 220 karakter.',
        ]);

        return [
            'image' => $data['image'] ?? null,
            'eyebrow' => trim($data['eyebrow'] ?? ''),
            'title' => trim($data['title'] ?? ''),
            'text' => trim($data['text'] ?? ''),
        ];
    }

    private function storeSlideImage(Tenant $tenant, string $dataUrl): string
    {
        return ImageStore::storeDataUrl($dataUrl, ImageStore::tenantDirectory($tenant->id, 'branding'), 'image', 4 * 1024 * 1024);
    }

    private function respond(Tenant $tenant, array $branding): JsonResponse
    {
        TenantBranding::save($tenant, $branding);

        return response()->json(['branding' => TenantBranding::forView($tenant->refresh())]);
    }
}
