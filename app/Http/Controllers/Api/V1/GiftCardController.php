<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\GiftCard;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GiftCardController extends Controller
{
    /**
     * Get all gift cards for the authenticated user
     * Used for offline caching - returns paginated results
     */
    public function index(Request $request): JsonResponse
    {
        // Get gift cards, optionally filtered by category
        $query = GiftCard::with('category')
            ->where('status', 'active')
            ->orderBy('created_at', 'desc');

        if ($request->has('category_id')) {
            $query->where('category_id', $request->input('category_id'));
        }

        $giftCards = $query->paginate(50);

        // Set cache headers for offline use (24 hours)
        return response()->json([
            'data' => $giftCards->items(),
            'meta' => [
                'total' => $giftCards->total(),
                'per_page' => $giftCards->perPage(),
                'current_page' => $giftCards->currentPage(),
                'last_page' => $giftCards->lastPage(),
            ],
        ])
            ->header('Cache-Control', 'public, max-age=86400')
            ->header('ETag', hash('sha256', json_encode($giftCards->items())));
    }

    /**
     * Search for a gift card by legacy_id or UUID
     * Used by scanner for QR code lookups
     */
    public function search(Request $request): JsonResponse
    {
        $identifier = $request->input('legacy_id') ?? $request->input('id');

        if (! $identifier) {
            return response()->json([
                'error' => 'Se requiere legacy_id o id',
            ], 400);
        }

        // Search by legacy_id first (QR code format)
        $giftCard = GiftCard::where('legacy_id', $identifier)
            ->orWhere('id', $identifier)
            ->with('category')
            ->firstOrFail();

        // Check if card is active
        if (! $giftCard->status) {
            return response()->json([
                'error' => 'Gift card está inactivo',
            ], 403);
        }

        // Set cache headers for offline use (1 hour)
        return response()->json([
            'data' => $giftCard,
        ])
            ->header('Cache-Control', 'public, max-age=3600')
            ->header('ETag', hash('sha256', json_encode($giftCard)));
    }
}
