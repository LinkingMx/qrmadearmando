<?php

use Illuminate\Support\Facades\Route;

// Health check endpoint
Route::get('/health', function () {
    return response()->json(['status' => 'ok']);
});

// API v1 - Offline-first PWA endpoints
Route::prefix('v1')->group(function () {
    // Public endpoints - accessible without authentication
    Route::prefix('public')->group(function () {
        // Gift card lookup by legacy_id (for guest mode scanning)
        Route::get('gift-cards/search', [\App\Http\Controllers\Api\V1\GiftCardController::class, 'search']);
        Route::get('categories', [\App\Http\Controllers\Api\V1\CategoryController::class, 'index']);
    });

    // Authenticated endpoints - require valid session
    Route::middleware(['auth:sanctum'])->group(function () {
        // User's own data and gift card (for offline dashboard)
        Route::get('me', [\App\Http\Controllers\Api\V1\UserController::class, 'me']);
        Route::get('me/transactions', [\App\Http\Controllers\Api\V1\UserController::class, 'transactions']);

        // Gift card data - for caching
        Route::get('gift-cards', [\App\Http\Controllers\Api\V1\GiftCardController::class, 'index']);

        // Debit processing - transactional
        Route::post('debit', [\App\Http\Controllers\Api\V1\DebitController::class, 'process']);

        // Offline sync - syncs pending transactions
        Route::post('sync/transactions', [\App\Http\Controllers\Api\V1\SyncController::class, 'syncTransactions']);
        Route::get('sync/status', [\App\Http\Controllers\Api\V1\SyncController::class, 'status']);
    });
});

// Legacy scanner endpoints (kept for backward compatibility)
Route::middleware(['auth', 'verified', 'has.branch'])->group(function () {
    Route::prefix('scanner')->group(function () {
        Route::post('lookup', [\App\Http\Controllers\ScannerController::class, 'lookupGiftCard']);
        Route::post('process-debit', [\App\Http\Controllers\ScannerController::class, 'processDebit']);
        Route::get('branch-transactions', [\App\Http\Controllers\ScannerController::class, 'branchTransactions']);
    });
});
