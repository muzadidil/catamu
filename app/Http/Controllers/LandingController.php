<?php

namespace App\Http\Controllers;

use App\Models\PlatformSetting;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LandingController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        return view('landing', [
            'trialDays' => PlatformSetting::trialDays(),
            'plan' => config('catamu.plans')[0],
            'dashboardUrl' => match (true) {
                ! $user => null,
                $user->isSuperAdmin() => route('admin.dashboard'),
                (bool) $user->tenant => $user->tenant->appUrl(),
                default => null,
            },
        ]);
    }
}
