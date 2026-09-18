<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PayoutRequest;
use App\Models\ReferralCommission;
use App\Models\Tenant;
use App\Support\Affiliate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AffiliateController extends Controller
{
    public function index(Request $request): View
    {
        $tab = $request->query('tab') === 'riwayat' ? 'riwayat' : 'menunggu';

        return view('admin.affiliates', [
            'tab' => $tab,
            'pendingCount' => PayoutRequest::where('status', PayoutRequest::STATUS_PENDING)->count(),
            'pending' => PayoutRequest::with('tenant.owner')
                ->where('status', PayoutRequest::STATUS_PENDING)
                ->oldest()
                ->get(),
            'history' => PayoutRequest::with(['tenant.owner', 'reviewer'])
                ->whereIn('status', [PayoutRequest::STATUS_APPROVED, PayoutRequest::STATUS_REJECTED])
                ->latest('reviewed_at')
                ->paginate(20, ['*'], 'halaman')
                ->withQueryString(),
            'totals' => [
                'commission' => (int) ReferralCommission::sum('amount'),
                'paid' => (int) PayoutRequest::where('status', PayoutRequest::STATUS_APPROVED)->sum('amount'),
                'onHold' => (int) PayoutRequest::where('status', PayoutRequest::STATUS_PENDING)->sum('amount'),
                'referredOffices' => Tenant::whereNotNull('referred_by_tenant_id')->count(),
            ],
            'topReferrers' => ReferralCommission::query()
                ->selectRaw('referrer_tenant_id, SUM(amount) as total, COUNT(*) as entries')
                ->groupBy('referrer_tenant_id')
                ->orderByDesc('total')
                ->limit(10)
                ->with('referrer.owner')
                ->get(),
            'rate' => Affiliate::rate(),
        ]);
    }

    public function approve(Request $request, PayoutRequest $payout): RedirectResponse
    {
        if ($payout->status !== PayoutRequest::STATUS_PENDING) {
            return back()->with('toast', 'Pengajuan ini sudah diproses.');
        }

        Affiliate::approvePayout($payout, $request->user());

        return back()->with('toast', "Pencairan komisi {$payout->tenant->officeName()} ditandai sudah dikirim.");
    }

    public function reject(Request $request, PayoutRequest $payout): RedirectResponse
    {
        if ($payout->status !== PayoutRequest::STATUS_PENDING) {
            return back()->with('toast', 'Pengajuan ini sudah diproses.');
        }

        $data = $request->validate(['note' => ['nullable', 'string', 'max:500']]);
        Affiliate::rejectPayout($payout, $request->user(), $data['note'] ?? null);

        return back()->with('toast', "Pencairan komisi {$payout->tenant->officeName()} ditolak.");
    }
}
