<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Support\Subscriptions;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TenantController extends Controller
{
    public const FILTERS = [
        'semua' => ['label' => 'Semua', 'scope' => null],
        'trial' => ['label' => 'Trial', 'scope' => 'inTrial'],
        'aktif' => ['label' => 'Aktif', 'scope' => 'activePlan'],
        'lifetime' => ['label' => 'Lifetime', 'scope' => 'lifetimePlan'],
        'menunggu' => ['label' => 'Menunggu Verifikasi', 'scope' => 'awaitingVerification'],
        'kedaluwarsa' => ['label' => 'Kedaluwarsa', 'scope' => 'expiredPlan'],
    ];

    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q'));
        $filter = array_key_exists($request->query('status'), self::FILTERS) ? $request->query('status') : 'semua';

        $base = Tenant::query()->when($search !== '', function (Builder $query) use ($search) {
            $like = '%'.str_replace(['%', '_'], ['\%', '\_'], $search).'%';
            $query->where(function (Builder $query) use ($search, $like) {
                $query->where('slug', 'like', $like)
                    ->orWhere('settings', 'like', $like)
                    ->orWhereHas('users', fn (Builder $users) => $users->where('email', 'like', $like)->orWhere('name', 'like', $like));
                if (ctype_digit($search)) {
                    $query->orWhereKey((int) $search);
                }
            });
        });

        $counts = collect(self::FILTERS)->map(fn ($item) => $this->applyFilter(clone $base, $item['scope'])->count());

        $tenants = $this->applyFilter(clone $base, self::FILTERS[$filter]['scope'])
            ->with(['owner', 'pendingPayment'])
            ->withCount(['guests', 'teamMembers'])
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.tenants', [
            'tenants' => $tenants,
            'filters' => self::FILTERS,
            'counts' => $counts,
            'filter' => $filter,
            'search' => $search,
        ]);
    }

    public function show(Tenant $tenant): View
    {
        $tenant->load(['owner', 'pendingPayment', 'teamMembers', 'payments.reviewer', 'feedbacks.user'])
            ->loadCount(['guests', 'departments']);

        return view('admin.tenant-show', [
            'tenant' => $tenant,
            'settings' => $tenant->resolvedSettings(),
            'usage' => [
                'guestsMonth' => $tenant->guests()->where('check_in', '>=', now()->startOfMonth())->count(),
                'guestsToday' => $tenant->guests()->where('check_in', '>=', today())->count(),
                'selfCheckins' => $tenant->guests()->where('source', 'self')->count(),
                'lastGuestAt' => $tenant->guests()->max('check_in'),
            ],
        ]);
    }

    public function activateYearly(Tenant $tenant): RedirectResponse
    {
        $tenant = Subscriptions::activateYearly($tenant);

        return back()->with('toast', "{$tenant->officeName()} aktif sampai {$tenant->expires_at->translatedFormat('d F Y')}.");
    }

    public function toggleLifetime(Tenant $tenant): RedirectResponse
    {
        $tenant = Subscriptions::toggleLifetime($tenant);

        return back()->with('toast', $tenant->lifetime
            ? "{$tenant->officeName()} sekarang memakai paket Lifetime."
            : "Paket Lifetime {$tenant->officeName()} dinonaktifkan.");
    }

    public function destroy(Request $request, Tenant $tenant): RedirectResponse
    {
        if (trim((string) $request->input('confirmation')) !== 'HAPUS') {
            return back()->with('toast', 'Ketik HAPUS untuk menghapus kantor.');
        }

        $name = $tenant->officeName();
        $tenant->purge();

        return redirect()->route('admin.tenants')->with('toast', "Kantor {$name} beserta seluruh datanya telah dihapus.");
    }

    private function applyFilter(Builder $query, ?string $scope): Builder
    {
        return $scope ? $query->{$scope}() : $query;
    }
}
