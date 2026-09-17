<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function read(Request $request, string $id): JsonResponse
    {
        $this->tenant($request)->appNotifications()->whereKey($id)->whereNull('read_at')->update(['read_at' => now()]);

        return response()->json(['ok' => true]);
    }

    public function readAll(Request $request): JsonResponse
    {
        $this->tenant($request)->appNotifications()->whereNull('read_at')->update(['read_at' => now()]);

        return response()->json(['ok' => true]);
    }

    public function clear(Request $request): JsonResponse
    {
        $this->tenant($request)->appNotifications()->delete();

        return response()->json(['ok' => true]);
    }
}
