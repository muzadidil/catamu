<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Guest;
use App\Models\Payment;
use App\Models\Tenant;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $monthStart = now()->startOfMonth();
        $approved = Payment::where('status', Payment::STATUS_APPROVED);

        return view('admin.dashboard', [
            'stats' => [
                'tenants' => Tenant::count(),
                'newThisMonth' => Tenant::where('created_at', '>=', $monthStart)->count(),
                'active' => Tenant::activePlan()->count(),
                'lifetime' => Tenant::lifetimePlan()->count(),
                'trial' => Tenant::inTrial()->count(),
                'expired' => Tenant::expiredPlan()->count(),
                'pending' => Payment::where('status', Payment::STATUS_PENDING)->count(),
                'revenueMonth' => (clone $approved)->where('reviewed_at', '>=', $monthStart)->sum('amount'),
                'revenueTotal' => (clone $approved)->sum('amount'),
                'guestsToday' => Guest::where('check_in', '>=', today())->count(),
                'selfCheckinsToday' => Guest::where('check_in', '>=', today())->where('source', 'self')->count(),
            ],
            'pendingPayments' => Payment::with('tenant.owner')->where('status', Payment::STATUS_PENDING)->oldest()->limit(5)->get(),
            'trialEnding' => Tenant::with('owner')->inTrial()->where('trial_expires_at', '<=', now()->addDays(3))->orderBy('trial_expires_at')->limit(6)->get(),
            'latestTenants' => Tenant::with(['owner', 'pendingPayment'])->latest()->limit(6)->get(),
        ]);
    }
}
