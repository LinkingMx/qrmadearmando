<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class EmployeeDashboardController extends Controller
{
    /**
     * Display the employee dashboard with their gift card data
     */
    public function index()
    {
        $user = auth()->user();
        $giftCard = $user->giftCards()->first();

        // Prepare gift card data for rendering
        $giftCardData = null;
        if ($giftCard) {
            // Check for QR image files - prefer legacy_id QR
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

            $giftCardData = [
                'id' => $giftCard->id,
                'legacy_id' => $giftCard->legacy_id,
                'balance' => (float) $giftCard->balance,
                'status' => $giftCard->status,
                'expiry_date' => $giftCard->expiry_date?->format('d/m/Y'),
                'qr_image_path' => $qrImagePath,
                'category' => [
                    'id' => $giftCard->category->id,
                    'name' => $giftCard->category->name,
                ],
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'avatar' => $user->avatar_url,
                ],
            ];
        }

        return Inertia::render('dashboard', [
            'giftCard' => $giftCardData,
        ]);
    }

    /**
     * Get paginated transactions for the authenticated user's gift card
     */
    public function transactions(): JsonResponse
    {
        $user = auth()->user();
        $giftCard = $user->giftCards()->first();

        if (! $giftCard) {
            return response()->json([
                'data' => [],
                'meta' => [
                    'current_page' => 1,
                    'last_page' => 1,
                    'per_page' => 10,
                    'total' => 0,
                    'from' => null,
                    'to' => null,
                ],
            ]);
        }

        $transactions = Transaction::where('gift_card_id', $giftCard->id)
            ->with('branch')
            ->latest()
            ->paginate(10);

        $typeLabels = [
            'credit' => 'Crédito',
            'debit' => 'Débito',
            'adjustment' => 'Ajuste',
        ];

        return response()->json([
            'data' => $transactions->map(function ($transaction) use ($typeLabels) {
                return [
                    'id' => $transaction->id,
                    'created_at' => $transaction->created_at->format('d/m/Y H:i:s'),
                    'type' => $transaction->type,
                    'type_label' => $typeLabels[$transaction->type] ?? $transaction->type,
                    'amount' => (float) $transaction->amount,
                    'balance_after' => (float) $transaction->balance_after,
                    'branch_name' => $transaction->branch?->name ?? 'N/A',
                    'description' => $transaction->description ?? '-',
                ];
            })->values()->toArray(),
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
}
