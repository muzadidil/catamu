<?php

namespace App\Providers;

use App\Models\Payment;
use App\Models\PayoutRequest;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        View::composer('admin.layout', fn ($view) => $view
            ->with('pendingPaymentsCount', Payment::where('status', Payment::STATUS_PENDING)->count())
            ->with('pendingPayoutsCount', PayoutRequest::where('status', PayoutRequest::STATUS_PENDING)->count()));

        RateLimiter::for('cekin', fn (Request $request) => Limit::perMinute(config('catamu.checkin_per_minute'))
            ->by($request->ip().'|'.$request->route('slug'))
            ->response(fn () => response()->json(['message' => 'Terlalu banyak cek-in dari perangkat ini. Coba lagi sebentar lagi.'], 429)));
    }
}
