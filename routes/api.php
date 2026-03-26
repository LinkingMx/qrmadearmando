<?php

use App\Http\Controllers\Api\V1\CategoryController;
use App\Http\Controllers\Api\V1\DebitController;
use App\Http\Controllers\Api\V1\GiftCardController;
use App\Http\Controllers\Api\V1\SyncController;
use App\Http\Controllers\Api\V1\UserController;
use App\Http\Controllers\ScannerController;
use Illuminate\Support\Facades\Route;

// Health check endpoint
Route::get('/health', function () {
    return response()->json(['status' => 'ok']);
});

// API v1 - Offline-first PWA endpoints
Route::prefix('v1')->group(function () {
    // Public endpoints - accessible without authentication (rate limited)
    Route::prefix('public')->middleware('throttle:30,1')->group(function () {
        // Gift card lookup by legacy_id (for guest mode scanning)
        Route::get('gift-cards/search', [GiftCardController::class, 'search']);
        Route::get('categories', [CategoryController::class, 'index']);
    });

    // Authenticated endpoints - require valid session
    Route::middleware(['auth:sanctum'])->group(function () {
        // User's own data and gift card (for offline dashboard)
        Route::get('me', [UserController::class, 'me']);
        Route::get('me/transactions', [UserController::class, 'transactions']);

        // Gift card data - for caching
        Route::get('gift-cards', [GiftCardController::class, 'index']);

        // Debit processing - transactional
        Route::post('debit', [DebitController::class, 'process']);

        // Offline sync - syncs pending transactions
        Route::post('sync/transactions', [SyncController::class, 'syncTransactions']);
        Route::get('sync/status', [SyncController::class, 'status']);
    });
});

// Legacy scanner endpoints (kept for backward compatibility)
Route::middleware(['auth', 'verified', 'has.branch'])->group(function () {
    Route::prefix('scanner')->group(function () {
        Route::post('lookup', [ScannerController::class, 'lookupGiftCard']);
        Route::post('process-debit', [ScannerController::class, 'processDebit']);
        Route::get('branch-transactions', [ScannerController::class, 'branchTransactions']);
    });
});
