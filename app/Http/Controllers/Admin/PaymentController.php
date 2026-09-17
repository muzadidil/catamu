<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Support\Subscriptions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PaymentController extends Controller
{
    public function index(Request $request): View
    {
        $tab = $request->query('tab') === 'riwayat' ? 'riwayat' : 'menunggu';

        return view('admin.payments', [
            'tab' => $tab,
            'pendingCount' => Payment::where('status', Payment::STATUS_PENDING)->count(),
            'pending' => Payment::with('tenant.owner', 'tenant.pendingPayment')->where('status', Payment::STATUS_PENDING)->oldest()->get(),
            'history' => Payment::with(['tenant.owner', 'reviewer'])
                ->whereIn('status', [Payment::STATUS_APPROVED, Payment::STATUS_REJECTED])
                ->latest('reviewed_at')
                ->paginate(20, ['*'], 'halaman')
                ->withQueryString(),
        ]);
    }

    public function approve(Request $request, Payment $payment): RedirectResponse
    {
        if ($payment->status !== Payment::STATUS_PENDING) {
            return back()->with('toast', 'Pembayaran ini sudah diproses.');
        }

        $tenant = Subscriptions::approve($payment, $request->user());

        return back()->with('toast', "Pembayaran {$tenant->officeName()} disetujui.");
    }

    public function reject(Request $request, Payment $payment): RedirectResponse
    {
        if ($payment->status !== Payment::STATUS_PENDING) {
            return back()->with('toast', 'Pembayaran ini sudah diproses.');
        }

        $data = $request->validate(['note' => ['nullable', 'string', 'max:500']]);
        Subscriptions::reject($payment, $request->user(), $data['note'] ?? null);

        return back()->with('toast', "Pembayaran {$payment->tenant->officeName()} ditolak.");
    }
}
