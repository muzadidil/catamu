<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Support\ClientState;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class FeedbackController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'category' => ['required', Rule::in(['Saran', 'Masalah', 'Fitur', 'Lainnya'])],
            'title' => ['required', 'string', 'max:150'],
            'message' => ['required', 'string', 'max:2000'],
        ], [
            'title.required' => 'Judul dan pesan wajib diisi.',
            'message.required' => 'Judul dan pesan wajib diisi.',
        ]);

        $feedback = $this->tenant($request)->feedbacks()->create($data + ['user_id' => $request->user()->id]);

        return response()->json(['feedback' => $feedback->toClient()], 201);
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        $this->tenant($request)->feedbacks()->findOrFail($id)->delete();

        return response()->json(['ok' => true]);
    }

    public function updateRating(Request $request): JsonResponse
    {
        $data = $request->validate([
            'score' => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['nullable', 'string', 'max:1000'],
        ], [
            'score.*' => 'Pilih rating 1–5 bintang.',
        ]);

        $tenant = $this->tenant($request);
        $tenant->update([
            'rating_score' => $data['score'],
            'rating_comment' => $data['comment'] ?? null,
            'rating_updated_at' => now(),
        ]);

        return response()->json(['rating' => ClientState::rating($tenant)]);
    }
}
