<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\Api;
use App\Http\Controllers\AppController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CheckinController;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\MediaController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

$domains = config('catamu.domains');

/*
| catamu.com — landing page, login Owner/anggota tim, halaman legal
*/
Route::domain($domains['main'])->group(function () {
    Route::get('/', [LandingController::class, 'index'])->name('landing');

    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login/team', [AuthController::class, 'loginTeam'])->name('login.team');
    Route::post('/login/pin', [AuthController::class, 'loginPin'])->name('login.pin');
    Route::post('/login/switch', [AuthController::class, 'switchAccount'])->name('login.switch');
    Route::get('/auth/google', [AuthController::class, 'redirectToGoogle'])->name('google.redirect');
    Route::get('/auth/google/callback', [AuthController::class, 'handleGoogleCallback'])->name('google.callback');
    Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

    Route::view('/privacy-policy', 'legal.privacy')->name('legal.privacy');
    Route::view('/terms', 'legal.terms')->name('legal.terms');
});

/*
| admin.catamu.com — backoffice super admin
*/
Route::domain($domains['admin'])->middleware('superadmin')->name('admin.')->group(function () {
    Route::get('/', [Admin\DashboardController::class, 'index'])->name('dashboard');

    Route::get('/pembayaran', [Admin\PaymentController::class, 'index'])->name('payments');
    Route::post('/pembayaran/{payment}/setujui', [Admin\PaymentController::class, 'approve'])->name('payments.approve');
    Route::post('/pembayaran/{payment}/tolak', [Admin\PaymentController::class, 'reject'])->name('payments.reject');

    Route::get('/kantor', [Admin\TenantController::class, 'index'])->name('tenants');
    Route::get('/kantor/{tenant}', [Admin\TenantController::class, 'show'])->name('tenants.show');
    Route::post('/kantor/{tenant}/aktifkan-tahunan', [Admin\TenantController::class, 'activateYearly'])->name('tenants.yearly');
    Route::post('/kantor/{tenant}/lifetime', [Admin\TenantController::class, 'toggleLifetime'])->name('tenants.lifetime');
    Route::delete('/kantor/{tenant}', [Admin\TenantController::class, 'destroy'])->name('tenants.destroy');

    Route::get('/masukan', [Admin\FeedbackController::class, 'index'])->name('feedbacks');

    Route::get('/pengaturan', [Admin\SettingsController::class, 'edit'])->name('settings');
    Route::post('/pengaturan/trial', [Admin\SettingsController::class, 'updateTrial'])->name('settings.trial');
    Route::post('/pengaturan/qris', [Admin\SettingsController::class, 'updateQris'])->name('settings.qris');

    Route::get('/media/payments/{payment}/proof', [MediaController::class, 'payment'])->name('media.payment');
    Route::get('/media/qris', [MediaController::class, 'qris'])->name('media.qris');
});

/*
| cekin.catamu.com/{nama-kantor}      — form cek-in mandiri untuk tamu (publik)
| cekin.catamu.com/{nama-kantor}/app  — aplikasi kantor (login)
*/
Route::domain($domains['cekin'])->group(function () {
    Route::get('/', fn () => redirect()->route('landing'));

    Route::middleware('auth')->group(function () {
        Route::get('/_media/guests/{guest}/{kind}', [MediaController::class, 'guest'])->whereIn('kind', ['photo', 'signature'])->name('media.guest');
        Route::get('/_media/users/{user}/photo', [MediaController::class, 'user'])->name('media.user');
        Route::get('/_media/payments/{payment}/proof', [MediaController::class, 'payment'])->name('media.payment');
        Route::get('/_media/qris', [MediaController::class, 'qris'])->name('media.qris');
    });

    Route::prefix('{slug}')->where(['slug' => '[a-z0-9]+(?:-[a-z0-9]+)*'])->group(function () {
        Route::get('/', [CheckinController::class, 'show'])->name('cekin.show');
        Route::post('/', [CheckinController::class, 'store'])->middleware('throttle:cekin')->name('cekin.store');
        Route::get('/app/manifest.webmanifest', [AppController::class, 'manifest'])->name('app.manifest');

        Route::prefix('app')->middleware('tenant')->group(function () {
            Route::get('/', [AppController::class, 'index'])->name('app');
            Route::get('/service-worker.js', [AppController::class, 'serviceWorker'])->name('app.service-worker');
            Route::get('/qr-cekin', [AppController::class, 'checkinPoster'])->name('app.qr');
            Route::post('/lock', [AuthController::class, 'lock'])->name('lock');

            Route::prefix('api')->group(function () {
                Route::get('/state', [AppController::class, 'state']);

                Route::post('/guests', [Api\GuestController::class, 'store']);
                Route::delete('/guests', [Api\GuestController::class, 'destroyAll']);
                Route::put('/guests/{id}', [Api\GuestController::class, 'update']);
                Route::post('/guests/{id}/checkout', [Api\GuestController::class, 'checkout']);
                Route::delete('/guests/{id}', [Api\GuestController::class, 'destroy']);

                Route::post('/departments', [Api\DepartmentController::class, 'store']);
                Route::put('/departments/{id}', [Api\DepartmentController::class, 'update']);
                Route::delete('/departments/{id}', [Api\DepartmentController::class, 'destroy']);

                Route::post('/team', [Api\TeamController::class, 'store']);
                Route::put('/team/{id}', [Api\TeamController::class, 'update']);
                Route::delete('/team/{id}', [Api\TeamController::class, 'destroy']);

                Route::put('/settings/application', [Api\SettingsController::class, 'updateApplication']);
                Route::put('/settings/guest-fields', [Api\SettingsController::class, 'updateGuestFields']);
                Route::put('/settings/notifications', [Api\SettingsController::class, 'updateNotifications']);
                Route::put('/me/theme', [Api\SettingsController::class, 'updateTheme']);

                Route::put('/account', [Api\AccountController::class, 'update']);
                Route::post('/account/photo', [Api\AccountController::class, 'updatePhoto']);
                Route::delete('/account/photo', [Api\AccountController::class, 'destroyPhoto']);
                Route::delete('/account/pin', [Api\AccountController::class, 'destroyPin']);
                Route::delete('/account', [Api\AccountController::class, 'destroy']);

                Route::post('/payments', [Api\PaymentController::class, 'store']);

                Route::post('/feedbacks', [Api\FeedbackController::class, 'store']);
                Route::delete('/feedbacks/{id}', [Api\FeedbackController::class, 'destroy']);
                Route::put('/rating', [Api\FeedbackController::class, 'updateRating']);

                Route::post('/notifications/read-all', [Api\NotificationController::class, 'readAll']);
                Route::post('/notifications/{id}/read', [Api\NotificationController::class, 'read']);
                Route::delete('/notifications', [Api\NotificationController::class, 'clear']);
            });
        });
    });
});

// Host yang tidak dikenal (mis. 127.0.0.1:8000) diarahkan ke domain utama.
Route::fallback(function (Request $request) use ($domains) {
    if (in_array($request->getHost(), $domains, true)) {
        abort(404);
    }

    $port = in_array($request->getPort(), [80, 443], true) ? '' : ':'.$request->getPort();

    return redirect()->away($request->getScheme().'://'.$domains['main'].$port.'/');
});
