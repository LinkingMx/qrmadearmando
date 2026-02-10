<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Models\GiftCard;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GiftCardController extends Controller
{
    use ApiResponse;

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
        return $this->paginated($giftCards)
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
            return $this->error(
                'MISSING_PARAMETER',
                'Se requiere legacy_id o id',
                400
            );
        }

        // Search by legacy_id first (QR code format)
        $giftCard = GiftCard::where('legacy_id', $identifier)
            ->orWhere('id', $identifier)
            ->with('category')
            ->firstOrFail();

        // Check if card is active
        if (! $giftCard->status) {
            return $this->error(
                'INACTIVE_CARD',
                'Gift card está inactivo',
                403
            );
        }

        // Format response with proper types (balance as float)
        $data = [
            'id' => $giftCard->id,
            'legacy_id' => $giftCard->legacy_id,
            'status' => $giftCard->status,
            'balance' => floatval($giftCard->balance),
            'expiry_date' => $giftCard->expiry_date?->format('Y-m-d'),
            'qr_image_path' => $giftCard->qr_image_path,
            'category' => $giftCard->category ? [
                'id' => $giftCard->category->id,
                'name' => $giftCard->category->name,
                'prefix' => $giftCard->category->prefix,
                'nature' => $giftCard->category->nature->value,
            ] : null,
        ];

        // Set cache headers for offline use (1 hour)
        return $this->success($data)
            ->header('Cache-Control', 'public, max-age=3600')
            ->header('ETag', hash('sha256', json_encode($data)));
    }
}
