<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Feedback;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FeedbackController extends Controller
{
    private const CATEGORIES = ['Saran', 'Masalah', 'Fitur', 'Lainnya'];

    public function index(Request $request): View
    {
        $category = in_array($request->query('kategori'), self::CATEGORIES, true) ? $request->query('kategori') : null;
        $rated = Tenant::whereNotNull('rating_score');
        $distribution = (clone $rated)->selectRaw('rating_score, COUNT(*) as total')->groupBy('rating_score')->pluck('total', 'rating_score');

        return view('admin.feedbacks', [
            'category' => $category,
            'categories' => self::CATEGORIES,
            'categoryCounts' => Feedback::selectRaw('category, COUNT(*) as total')->groupBy('category')->pluck('total', 'category'),
            'feedbacks' => Feedback::with(['tenant', 'user'])
                ->when($category, fn ($query) => $query->where('category', $category))
                ->latest('id')
                ->paginate(20)
                ->withQueryString(),
            'ratings' => (clone $rated)->latest('rating_updated_at')->limit(8)->get(),
            'ratingCount' => (clone $rated)->count(),
            'averageRating' => (clone $rated)->avg('rating_score'),
            'distribution' => collect([5, 4, 3, 2, 1])->mapWithKeys(fn ($score) => [$score => (int) ($distribution[$score] ?? 0)]),
        ]);
    }
}
