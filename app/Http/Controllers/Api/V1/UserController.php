<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\GiftCard;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class UserController extends Controller
{
    /**
     * Get authenticated user's data and their gift card
     * Used for offline caching in PWA
     */
    public function me(): JsonResponse
    {
        $user = auth()->user();
        $giftCard = $user->giftCards()->first();

        if (! $giftCard) {
            return response()->json([
                'data' => null,
                'message' => 'No tienes una tarjeta QR asignada',
            ], 404);
        }

        // Generate QR image path - prefer legacy QR for backward compatibility
        $qrImagePath = null;
        if ($giftCard->qr_image_path) {
            $legacyQrPath = 'qr-codes/'.$giftCard->id.'_legacy.svg';
            $uuidQrPath = 'qr-codes/'.$giftCard->id.'_uuid.svg';

            if (Storage::disk('public')->exists($legacyQrPath)) {
                $qrImagePath = Storage::url($legacyQrPath);
            } elseif (Storage::disk('public')->exists($uuidQrPath)) {
                $qrImagePath = Storage::url($uuidQrPath);
            }
        }

        return response()->json([
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'avatar' => $user->avatar ? Storage::url($user->avatar) : null,
                ],
                'gift_card' => [
                    'id' => $giftCard->id,
                    'legacy_id' => $giftCard->legacy_id,
                    'balance' => (float) $giftCard->balance,
                    'status' => $giftCard->status,
                    'expiry_date' => $giftCard->expiry_date?->format('d/m/Y'),
                    'qr_image_path' => $qrImagePath,
                ],
            ],
        ])
            ->header('Cache-Control', 'private, max-age=300')
            ->header('ETag', hash('sha256', json_encode([
                'user_id' => $user->id,
                'balance' => $giftCard->balance,
                'status' => $giftCard->status,
            ])));
    }

    /**
     * Get paginated transactions for the authenticated user's gift card
     */
    public function transactions(Request $request): JsonResponse
    {
        $giftCard = GiftCard::where('user_id', auth()->id())->first();

        if (! $giftCard) {
            return response()->json([
                'error' => 'No tienes una tarjeta QR asignada.',
            ], 404);
        }

        $transactions = $giftCard->transactions()
            ->with(['branch', 'admin'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json([
            'data' => $transactions->map(function ($transaction) {
                return [
                    'id' => $transaction->id,
                    'created_at' => $transaction->created_at->format('d/m/Y H:i'),
                    'type' => $transaction->type,
                    'type_label' => $this->getTypeLabel($transaction->type),
                    'amount' => (float) $transaction->amount,
                    'balance_after' => (float) $transaction->balance_after,
                    'branch_name' => $transaction->branch?->name ?? 'N/A',
                    'description' => $transaction->description ?? '-',
                ];
            }),
            'meta' => [
                'current_page' => $transactions->currentPage(),
                'last_page' => $transactions->lastPage(),
                'per_page' => $transactions->perPage(),
                'total' => $transactions->total(),
                'from' => $transactions->firstItem(),
                'to' => $transactions->lastItem(),
            ],
        ]);
    }

    /**
     * Get human-readable type label
     */
    private function getTypeLabel(string $type): string
    {
        return match ($type) {
            'credit' => 'Carga',
            'debit' => 'Descuento',
            'adjustment' => 'Ajuste',
            default => $type,
        };
    }
}
