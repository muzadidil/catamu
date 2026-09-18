<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Support\Affiliate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Menu Afiliasi milik Owner.
 *
 * Semua aksi di sini sengaja tidak menuntut langganan aktif (requireWritable
 * false): komisi yang sudah didapat tetap hak kantor walau masa aktifnya habis.
 */
class AffiliateController extends Controller
{
    public function updateCode(Request $request): JsonResponse
    {
        $this->authorizeCapability($request, 'affiliateManage', 'mengatur kode afiliasi', false);
        $tenant = $this->tenant($request);

        $data = $request->validate([
            'code' => ['required', 'string', 'min:4', 'max:24', 'regex:'.Affiliate::CODE_REGEX],
        ], [
            'code.required' => 'Isi kode referral yang diinginkan.',
            'code.min' => 'Kode referral minimal 4 karakter.',
            'code.max' => 'Kode referral maksimal 24 karakter.',
            'code.regex' => 'Kode hanya boleh huruf, angka, dan tanda strip di tengah.',
        ]);

        $code = Affiliate::normalizeCode($data['code']);

        if (Affiliate::codeTaken($code, $tenant->id)) {
            abort(422, 'Kode ini sudah dipakai kantor lain. Coba kode yang lain.');
        }

        $tenant->forceFill(['referral_code' => $code])->save();

        return response()->json(['affiliate' => Affiliate::summary($tenant)]);
    }

    public function updateAccount(Request $request): JsonResponse
    {
        $this->authorizeCapability($request, 'affiliateManage', 'mengatur rekening pencairan', false);
        $tenant = $this->tenant($request);

        $data = $request->validate([
            'bank' => ['required', 'string', 'max:40'],
            'number' => ['required', 'string', 'max:40'],
            'name' => ['required', 'string', 'max:120'],
        ], [
            'bank.required' => 'Isi nama bank atau e-wallet.',
            'number.required' => 'Isi nomor rekening atau nomor e-wallet.',
            'name.required' => 'Isi nama pemilik rekening.',
        ]);

        $tenant->forceFill([
            'payout_bank' => trim($data['bank']),
            'payout_account' => trim($data['number']),
            'payout_name' => trim($data['name']),
        ])->save();

        return response()->json(['affiliate' => Affiliate::summary($tenant)]);
    }

    public function storePayout(Request $request): JsonResponse
    {
        $this->authorizeCapability($request, 'affiliateManage', 'mengajukan pencairan komisi', false);
        $tenant = $this->tenant($request);

        $data = $request->validate([
            'amount' => ['required', 'integer', 'min:1'],
        ], [
            'amount.required' => 'Isi nominal yang ingin dicairkan.',
            'amount.integer' => 'Nominal pencairan harus berupa angka.',
        ]);

        Affiliate::requestPayout($tenant, $request->user(), (int) $data['amount']);

        return response()->json(['affiliate' => Affiliate::summary($tenant->refresh())], 201);
    }
}
