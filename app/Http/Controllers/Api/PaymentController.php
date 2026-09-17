<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Support\ClientState;
use App\Support\ImageStore;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $this->authorizeCapability($request, 'subscriptionManage', 'mengirim pembayaran langganan', false);
        $tenant = $this->tenant($request);

        if ($tenant->pendingPayment) {
            abort(422, 'Masih ada pembayaran yang menunggu verifikasi.');
        }

        $data = $request->validate([
            'days' => ['required', 'integer'],
            'planName' => ['required', 'string'],
            'proof' => ['required', 'string'],
        ], [
            'proof.required' => 'Tambahkan bukti foto pembayaran terlebih dahulu.',
        ]);

        $plan = collect(config('catamu.plans'))->first(
            fn ($plan) => $plan['days'] === (int) $data['days'] && $plan['name'] === $data['planName']
        );
        if (! $plan) {
            abort(422, 'Paket langganan tidak valid.');
        }

        $proofPath = ImageStore::storeDataUrl($data['proof'], ImageStore::tenantDirectory($tenant->id, 'payments'), 'proof', 6 * 1024 * 1024);

        $tenant->payments()->create([
            'user_id' => $request->user()->id,
            'plan_name' => $plan['name'],
            'days' => $plan['days'],
            'amount' => $plan['amount'],
            'method' => 'QRIS',
            'proof_path' => $proofPath,
            'status' => Payment::STATUS_PENDING,
        ]);

        $tenant->unsetRelation('pendingPayment')->unsetRelation('latestReviewedPayment');

        return response()->json(['subscription' => ClientState::subscription($tenant)], 201);
    }
}
