<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PlatformSetting;
use App\Support\ImageStore;
use App\Support\QrisImage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function edit(): View
    {
        return view('admin.settings', [
            'trialDays' => PlatformSetting::trialDays(),
            'qrisUrl' => QrisImage::url('admin.media.qris'),
            'plan' => config('catamu.plans')[0],
        ]);
    }

    public function updateTrial(Request $request): RedirectResponse
    {
        $data = $request->validate(['trial_days' => ['required', 'integer', 'min:1', 'max:365']], [
            'trial_days.required' => 'Lama trial wajib diisi.',
            'trial_days.integer' => 'Lama trial harus berupa angka hari.',
            'trial_days.min' => 'Lama trial minimal 1 hari.',
            'trial_days.max' => 'Lama trial maksimal 365 hari.',
        ]);

        PlatformSetting::put('trial_days', $data['trial_days']);

        return back()->with('toast', "Lama trial kantor baru diubah menjadi {$data['trial_days']} hari.");
    }

    public function updateQris(Request $request): RedirectResponse
    {
        $request->validate(['qris' => ['required', 'image', 'mimes:png,jpg,jpeg,webp', 'max:2048']], [
            'qris.required' => 'Pilih gambar QRIS terlebih dahulu.',
            'qris.image' => 'File QRIS harus berupa gambar.',
            'qris.mimes' => 'Format QRIS harus PNG, JPG, atau WEBP.',
            'qris.max' => 'Ukuran gambar QRIS maksimal 2 MB.',
        ]);

        $uploaded = ImageStore::storeBinary($request->file('qris')->get(), 'platform/uploads', 'qris', true);
        Storage::disk('local')->delete(QrisImage::PATH);
        Storage::disk('local')->move($uploaded, QrisImage::PATH);

        return back()->with('toast', 'Gambar QRIS berhasil diperbarui.');
    }
}
