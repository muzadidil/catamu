<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use App\Support\ClientState;
use App\Support\TenantBranding;
use chillerlan\QRCode\Output\QRMarkupSVG;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class AppController extends Controller
{
    public function index(Request $request): View
    {
        return view('app.index', [
            'state' => ClientState::for($request->user()),
            'user' => $request->user(),
            'tenant' => $request->user()->tenant,
        ]);
    }

    public function state(Request $request): JsonResponse
    {
        return response()->json(ClientState::for($request->user()));
    }

    /** Publik: browser mengambil manifest tanpa cookie. Isinya hanya nama & alamat publik kantor. */
    public function manifest(string $slug): JsonResponse
    {
        $tenant = Tenant::resolveSlug($slug) ?? abort(404);
        $scope = $tenant->appUrl().'/';

        return response()->json([
            'name' => 'adatamu.id - '.$tenant->officeName(),
            'short_name' => 'adatamu.id',
            'description' => 'Buku tamu digital '.$tenant->officeName(),
            'id' => $scope,
            'start_url' => $scope,
            'scope' => $scope,
            'display' => 'standalone',
            'orientation' => 'portrait',
            'background_color' => '#f5f7fb',
            'theme_color' => '#b91c1c',
            'lang' => 'id',
            // Logo kantor dipasang tanpa "maskable": logo unggahan dibiarkan utuh
            // di kanvas transparan, jadi kalau dipangkas Android bisa terpotong.
            'icons' => TenantBranding::iconUrl($tenant, 192) ? [
                ['src' => TenantBranding::iconUrl($tenant, 192), 'sizes' => '192x192', 'type' => 'image/png', 'purpose' => 'any'],
                ['src' => TenantBranding::iconUrl($tenant, 512), 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'any'],
            ] : [
                ['src' => asset('icon-192.png'), 'sizes' => '192x192', 'type' => 'image/png', 'purpose' => 'any maskable'],
                ['src' => asset('icon-512.png'), 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'any maskable'],
            ],
        ], 200, ['Content-Type' => 'application/manifest+json'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    public function serviceWorker(): Response
    {
        return response(file_get_contents(public_path('service-worker.js')), 200, [
            'Content-Type' => 'application/javascript; charset=utf-8',
            'Cache-Control' => 'no-cache',
        ]);
    }

    public function checkinPoster(Request $request): View
    {
        $tenant = $this->tenant($request);
        $url = $tenant->checkinUrl();

        $qr = (new QRCode(new QROptions([
            'outputInterface' => QRMarkupSVG::class,
            'outputBase64' => false,
            'svgAddXmlHeader' => false,
            'addQuietzone' => true,
            'quietzoneSize' => 2,
            'drawLightModules' => false,
            'svgViewBoxSize' => null,
        ])))->render($url);

        return view('app.qr-poster', [
            'tenant' => $tenant,
            'settings' => $tenant->resolvedSettings(),
            'url' => $url,
            'qrSvg' => $qr,
        ]);
    }
}
