<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProcessDebitRequest;
use App\Models\GiftCard;
use App\Models\Transaction;
use App\Services\TransactionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class ScannerController extends Controller
{
    protected TransactionService $transactionService;

    public function __construct(TransactionService $transactionService)
    {
        $this->transactionService = $transactionService;
    }

    /**
     * Display the scanner page
     */
    public function index()
    {
        return Inertia::render('scanner', [
            'branch' => auth()->user()->branch,
            'user' => auth()->user()->only(['id', 'name', 'email']),
        ]);
    }

    /**
     * Lookup a gift card by legacy_id or UUID
     */
    public function lookupGiftCard(Request $request)
    {
        $request->validate([
            'identifier' => 'required|string',
        ]);

        $identifier = trim($request->identifier);

        // Search by legacy_id or UUID
        $giftCard = GiftCard::where('legacy_id', $identifier)
            ->orWhere('id', $identifier)
            ->with('user')
            ->first();

        if (!$giftCard) {
            return response()->json([
                'error' => 'QR no encontrado. Verifique el código e intente nuevamente.'
            ], 404);
        }

        if (!$giftCard->status) {
            return response()->json([
                'error' => 'Este QR está inactivo y no puede ser utilizado.'
            ], 422);
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

        return response()->json([
            'gift_card' => [
                'id' => $giftCard->id,
                'legacy_id' => $giftCard->legacy_id,
                'user' => $giftCard->user ? [
                    'name' => $giftCard->user->name,
                    'avatar' => $giftCard->user->avatar
                        ? Storage::url($giftCard->user->avatar)
                        : null,
                ] : null,
                'balance' => (float) $giftCard->balance,
                'status' => $giftCard->status,
                'expiry_date' => $giftCard->expiry_date?->format('d/m/Y'),
                'qr_image_path' => $qrImagePath,
            ],
        ]);
    }

    /**
     * Process a debit transaction from scanner
     */
    public function processDebit(ProcessDebitRequest $request)
    {
        try {
            $giftCard = GiftCard::findOrFail($request->gift_card_id);
            $branch = auth()->user()->branch;

            // Check if gift card is active
            if (!$giftCard->status) {
                return response()->json([
                    'error' => 'Este QR está inactivo y no puede ser utilizado.'
                ], 422);
            }

            // Validate sufficient balance
            if ($giftCard->balance < $request->amount) {
                return response()->json([
                    'error' => 'Saldo insuficiente. Saldo disponible: $' . number_format($giftCard->balance, 2)
                ], 422);
            }

            // Process debit using TransactionService
            $transaction = $this->transactionService->debit(
                $giftCard,
                $request->amount,
                $request->description ?? 'Descuento desde Scanner',
                auth()->id(),
                $branch->id
            );

            // Generate unique folio
            $folio = 'TRX-' . now()->format('Ymd') . '-' . str_pad($transaction->id, 6, '0', STR_PAD_LEFT);

            // Refresh gift card to get updated balance
            $giftCard->refresh();

            return response()->json([
                'success' => true,
                'transaction' => [
                    'id' => $transaction->id,
                    'folio' => $folio,
                    'gift_card' => [
                        'id' => $giftCard->id,
                        'legacy_id' => $giftCard->legacy_id,
                        'user' => $giftCard->user ? [
                            'name' => $giftCard->user->name,
                            'avatar' => $giftCard->user->avatar
                                ? Storage::url($giftCard->user->avatar)
                                : null,
                        ] : null,
                        'balance' => (float) $giftCard->balance,
                        'status' => $giftCard->status,
                    ],
                    'amount' => (float) $transaction->amount,
                    'balance_before' => (float) $transaction->balance_before,
                    'balance_after' => (float) $transaction->balance_after,
                    'reference' => $request->reference,
                    'description' => $transaction->description,
                    'created_at' => $transaction->created_at->format('d/m/Y H:i:s'),
                    'branch_name' => $branch->name,
                    'cashier_name' => auth()->user()->name,
                ],
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al procesar la transacción. Por favor intente nuevamente.'
            ], 500);
        }
    }

    /**
     * Get paginated transactions for the authenticated user's branch
     */
    public function branchTransactions(Request $request)
    {
        $branchId = auth()->user()->branch_id;

        $transactions = Transaction::with(['giftCard.user', 'admin', 'branch'])
            ->where('branch_id', $branchId)
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return response()->json([
            'data' => $transactions->map(function ($transaction) {
                // Generate folio same way as processDebit
                $folio = 'TRX-' . $transaction->created_at->format('Ymd') . '-' . str_pad($transaction->id, 6, '0', STR_PAD_LEFT);

                // Generate reference same way as processDebit
                $reference = 'REF-' . str_pad($transaction->id, 6, '0', STR_PAD_LEFT);

                return [
                    'id' => $transaction->id,
                    'folio' => $folio,
                    'gift_card' => [
                        'id' => $transaction->giftCard->id,
                        'legacy_id' => $transaction->giftCard->legacy_id,
                        'user' => $transaction->giftCard->user ? [
                            'name' => $transaction->giftCard->user->name,
                            'avatar' => $transaction->giftCard->user->avatar
                                ? Storage::url($transaction->giftCard->user->avatar)
                                : null,
                        ] : null,
                        'balance' => (float) $transaction->giftCard->balance,
                        'status' => $transaction->giftCard->status,
                    ],
                    'amount' => (float) $transaction->amount,
                    'balance_before' => (float) $transaction->balance_before,
                    'balance_after' => (float) $transaction->balance_after,
                    'reference' => $reference,
                    'description' => $transaction->description,
                    'created_at' => $transaction->created_at->format('d/m/Y H:i:s'),
                    'branch_name' => $transaction->branch->name,
                    'cashier_name' => $transaction->admin->name,
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
}
