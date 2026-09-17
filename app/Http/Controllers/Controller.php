<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use Illuminate\Http\Request;

abstract class Controller
{
    /** Port dari guardCapability() di JS, dijalankan ulang di server. */
    protected function authorizeCapability(Request $request, string $capability, string $label, bool $requireWritable = true): void
    {
        $user = $request->user();

        if (! $user->hasCapability($capability)) {
            abort(403, "Akses ditolak. Role Anda tidak diizinkan untuk {$label}.");
        }

        if ($requireWritable && ! $user->tenant->isWritable()) {
            abort(403, $user->tenant->subscriptionState() === 'pending'
                ? 'Pembayaran masih menunggu verifikasi. Aplikasi sementara dalam mode baca saja.'
                : 'Masa trial/langganan telah berakhir. Aplikasi dalam mode baca saja.');
        }
    }

    protected function tenant(Request $request): Tenant
    {
        return $request->user()->tenant;
    }
}
