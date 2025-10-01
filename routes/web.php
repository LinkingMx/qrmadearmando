<?php

use App\Exports\UsersTemplateExport;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;

Route::get('/', function () {
    return Inertia::render('welcome');
})->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', [\App\Http\Controllers\EmployeeDashboardController::class, 'index'])
        ->name('dashboard');

    Route::get('api/my-transactions', [\App\Http\Controllers\EmployeeDashboardController::class, 'transactions']);

    // Scanner routes
    Route::middleware('has.branch')->group(function () {
        Route::get('scanner', [\App\Http\Controllers\ScannerController::class, 'index'])
            ->name('scanner');

        Route::prefix('api/scanner')->group(function () {
            Route::post('lookup', [\App\Http\Controllers\ScannerController::class, 'lookupGiftCard']);
            Route::post('process-debit', [\App\Http\Controllers\ScannerController::class, 'processDebit']);
        });
    });
});

// Download routes for imports
Route::get('/download/users-template', function () {
    return Excel::download(new UsersTemplateExport(), 'plantilla_usuarios.xlsx');
})->name('download.users-template')->middleware('auth');

Route::get('/download/import-errors/{file}', function ($file) {
    $path = storage_path('app/public/temp/' . $file);
    if (!file_exists($path)) {
        abort(404);
    }
    return response()->download($path)->deleteFileAfterSend();
})->name('download.import-errors')->middleware('auth');

Route::get('/download/import-passwords/{file}', function ($file) {
    $path = storage_path('app/public/temp/' . $file);
    if (!file_exists($path)) {
        abort(404);
    }
    return response()->download($path)->deleteFileAfterSend();
})->name('download.import-passwords')->middleware('auth');

// Balance import routes
Route::get('/download/balance-template', function () {
    return Excel::download(new \App\Exports\BalanceTemplateExport(), 'plantilla_carga_saldos.xlsx');
})->name('download.balance-template')->middleware('auth');

Route::get('/download/balance-report/{file}', function ($file) {
    $path = storage_path('app/public/temp/' . $file);
    if (!file_exists($path)) {
        abort(404);
    }
    return response()->download($path)->deleteFileAfterSend();
})->name('download.balance-report')->middleware('auth');

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
