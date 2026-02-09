<?php

namespace App\Http\Controllers;

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
            ];
        }

        return Inertia::render('dashboard', [
            'giftCard' => $giftCardData,
        ]);
    }
}
