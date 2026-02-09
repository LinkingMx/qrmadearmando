<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\GiftCardCategory;
use Illuminate\Http\JsonResponse;

class CategoryController extends Controller
{
    /**
     * Get all gift card categories
     * Public endpoint - no authentication required
     * Used for offline caching of category data
     */
    public function index(): JsonResponse
    {
        $categories = GiftCardCategory::select('id', 'prefix', 'name_es', 'nature')
            ->orderBy('name_es')
            ->get()
            ->map(function ($category) {
                return [
                    'id' => $category->id,
                    'prefix' => $category->prefix,
                    'name_es' => $category->name_es,
                    'nature' => $category->nature->value,
                    'cached_at' => now()->timestamp,
                ];
            });

        // Set cache headers for offline use (24 hours)
        return response()->json([
            'data' => $categories,
        ])
            ->header('Cache-Control', 'public, max-age=86400')
            ->header('ETag', hash('sha256', json_encode($categories)));
    }
}
