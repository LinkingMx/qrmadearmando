<?php

namespace App\Http\Controllers;

use App\Models\GiftCard;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class EmployeeDashboardController extends Controller
{
    /**
     * Display the employee dashboard
     */
    public function index()
    {
        $giftCard = GiftCard::where('user_id', auth()->id())
            ->with('user')
            ->first();

        if (!$giftCard) {
            return redirect()->route('dashboard')
                ->with('error', 'No tienes una tarjeta QR asignada.');
        }

        // Generate QR image path - prefer UUID QR
        $qrImagePath = null;
        if ($giftCard->qr_image_path) {
            $uuidQrPath = 'qr-codes/' . $giftCard->id . '_uuid.svg';
            $legacyQrPath = 'qr-codes/' . $giftCard->id . '_legacy.svg';

            if (Storage::disk('public')->exists($uuidQrPath)) {
                $qrImagePath = Storage::url($uuidQrPath);
            } elseif (Storage::disk('public')->exists($legacyQrPath)) {
                $qrImagePath = Storage::url($legacyQrPath);
            }
        }

        return Inertia::render('dashboard', [
            'giftCard' => [
                'id' => $giftCard->id,
                'legacy_id' => $giftCard->legacy_id,
                'balance' => (float) $giftCard->balance,
                'status' => $giftCard->status,
                'expiry_date' => $giftCard->expiry_date?->format('d/m/Y'),
                'qr_image_path' => $qrImagePath,
                'user' => [
                    'name' => $giftCard->user->name,
                    'email' => $giftCard->user->email,
                    'avatar' => $giftCard->user->avatar
                        ? Storage::url($giftCard->user->avatar)
                        : null,
                ],
            ],
        ]);
    }

    /**
     * Get paginated transactions for the authenticated user's gift card
     */
    public function transactions(Request $request)
    {
        $giftCard = GiftCard::where('user_id', auth()->id())->first();

        if (!$giftCard) {
            return response()->json([
                'error' => 'No tienes una tarjeta QR asignada.'
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
        return match($type) {
            'credit' => 'Carga',
            'debit' => 'Descuento',
            'adjustment' => 'Ajuste',
            default => $type,
        };
    }
}
