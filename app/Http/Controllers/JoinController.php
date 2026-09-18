<?php

namespace App\Http\Controllers;

use App\Support\Affiliate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/** Pintu masuk link afiliasi: catamu.com/join/KODE, dengan alias catamu.com/join=KODE. */
class JoinController extends Controller
{
    public function enter(Request $request, string $code): RedirectResponse
    {
        $referrer = Affiliate::resolve($code);

        if (! $referrer) {
            return redirect()->route('login')->with('error', 'Kode undangan tidak dikenal atau sudah diganti pemiliknya.');
        }

        // Satu kunjungan per sesi, supaya refresh halaman tidak menggelembungkan statistik.
        if ((int) $request->session()->get('referral_tenant_id') !== $referrer->id) {
            $referrer->increment('referral_visits');
        }

        $request->session()->put('referral_tenant_id', $referrer->id);

        return redirect()->route('login');
    }
}
