<?php

namespace App\Http\Controllers;

use Inertia\Inertia;

class EmployeeDashboardController extends Controller
{
    /**
     * Display the employee dashboard
     * Note: Data is now loaded via useOfflineGiftCard() hook with API endpoint /api/v1/me
     * This enables offline-first functionality with NetworkFirst caching
     */
    public function index()
    {
        return Inertia::render('dashboard');
    }

    /**
     * Get paginated transactions for the authenticated user's gift card
     */
    public function transactions(Request $request)
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
