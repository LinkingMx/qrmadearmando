<?php

use App\Exports\BalanceTemplateExport;
use App\Exports\UsersTemplateExport;
use App\Http\Controllers\EmployeeDashboardController;
use App\Http\Controllers\PushSubscriptionController;
use App\Http\Controllers\ScannerController;
use Illuminate\Support\Facades\Route;
use Maatwebsite\Excel\Facades\Excel;

Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('dashboard');
    }

    return redirect()->route('login');
})->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', [EmployeeDashboardController::class, 'index'])
        ->name('dashboard');

    Route::get('api/my-transactions', [EmployeeDashboardController::class, 'transactions']);

    // Push subscription routes
    Route::middleware('throttle:5,1')->group(function () {
        Route::post('api/push-subscriptions', [PushSubscriptionController::class, 'store']);
        Route::delete('api/push-subscriptions', [PushSubscriptionController::class, 'destroy']);
    });

    // Scanner routes
    Route::middleware('has.branch')->group(function () {
        Route::get('scanner', [ScannerController::class, 'index'])
            ->name('scanner');

        Route::prefix('api/scanner')->group(function () {
            Route::post('lookup', [ScannerController::class, 'lookupGiftCard']);
            Route::post('process-debit', [ScannerController::class, 'processDebit']);
            Route::get('branch-transactions', [ScannerController::class, 'branchTransactions']);
        });
    });
});

// Secure temp file download handler (prevents path traversal)
$secureTempDownload = function (string $file) {
    $file = basename($file);
    $basePath = realpath(storage_path('app/public/temp'));

    if (! $basePath) {
        abort(404);
    }

    $path = $basePath.'/'.$file;
    $resolvedPath = realpath($path);

    if ($resolvedPath === false || ! str_starts_with($resolvedPath, $basePath)) {
        abort(404);
    }

    return response()->download($resolvedPath)->deleteFileAfterSend();
};

// Download routes for imports
Route::get('/download/users-template', function () {
    return Excel::download(new UsersTemplateExport, 'plantilla_usuarios.xlsx');
})->name('download.users-template')->middleware('auth');

Route::get('/download/import-errors/{file}', $secureTempDownload)
    ->where('file', '[a-zA-Z0-9._-]+')
    ->name('download.import-errors')->middleware('auth');

Route::get('/download/import-passwords/{file}', $secureTempDownload)
    ->where('file', '[a-zA-Z0-9._-]+')
    ->name('download.import-passwords')->middleware('auth');

// Balance import routes
Route::get('/download/balance-template', function () {
    return Excel::download(new BalanceTemplateExport, 'plantilla_carga_saldos.xlsx');
})->name('download.balance-template')->middleware('auth');

Route::get('/download/balance-report/{file}', $secureTempDownload)
    ->where('file', '[a-zA-Z0-9._-]+')
    ->name('download.balance-report')->middleware('auth');

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
