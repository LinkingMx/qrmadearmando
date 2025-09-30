<?php

use App\Imports\UsersImport;
use App\Models\Branch;
use App\Models\User;
use App\Services\UserImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('public');

    // Create a branch for testing
    $this->branch = Branch::factory()->create(['name' => 'Sucursal Test']);
});

test('can import users from excel without photos', function () {
    // Create a simple CSV-like structure
    $data = [
        ['nombre', 'email', 'contrasena', 'sucursal', 'foto'],
        ['Test User 1', 'test1@example.com', 'password123', 'Sucursal Test', ''],
        ['Test User 2', 'test2@example.com', '', '', ''],
    ];

    // Create temp Excel file
    $filePath = storage_path('app/public/test_import.xlsx');
    $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    foreach ($data as $rowIndex => $row) {
        foreach ($row as $colIndex => $value) {
            $sheet->setCellValueByColumnAndRow($colIndex + 1, $rowIndex + 1, $value);
        }
    }

    $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
    $writer->save($filePath);

    $importService = new UserImportService();
    $import = new UsersImport($importService, false);

    Excel::import($import, $filePath);

    $stats = $import->getStats();

    expect($stats['created'])->toBe(2)
        ->and($stats['errors'])->toBe(0)
        ->and(User::count())->toBe(2)
        ->and(User::where('email', 'test1@example.com')->exists())->toBeTrue()
        ->and(User::where('email', 'test2@example.com')->exists())->toBeTrue();

    // Clean up
    unlink($filePath);
    $importService->cleanup();
});

test('import validates required fields', function () {
    $data = [
        ['nombre', 'email', 'contrasena', 'sucursal', 'foto'],
        ['', 'invalid@example.com', '', '', ''], // Missing name
        ['Valid Name', '', '', '', ''], // Missing email
    ];

    $filePath = storage_path('app/public/test_import_invalid.xlsx');
    $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    foreach ($data as $rowIndex => $row) {
        foreach ($row as $colIndex => $value) {
            $sheet->setCellValueByColumnAndRow($colIndex + 1, $rowIndex + 1, $value);
        }
    }

    $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
    $writer->save($filePath);

    $importService = new UserImportService();
    $import = new UsersImport($importService, false);

    Excel::import($import, $filePath);

    $stats = $import->getStats();

    expect($stats['errors'])->toBe(2)
        ->and($stats['created'])->toBe(0)
        ->and(User::count())->toBe(0);

    unlink($filePath);
    $importService->cleanup();
});

test('import does not create duplicate emails', function () {
    // Create existing user
    User::factory()->create(['email' => 'existing@example.com', 'name' => 'Existing User']);

    $data = [
        ['nombre', 'email', 'contrasena', 'sucursal', 'foto'],
        ['New Name', 'existing@example.com', '', '', ''],
    ];

    $filePath = storage_path('app/public/test_import_duplicate.xlsx');
    $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    foreach ($data as $rowIndex => $row) {
        foreach ($row as $colIndex => $value) {
            $sheet->setCellValueByColumnAndRow($colIndex + 1, $rowIndex + 1, $value);
        }
    }

    $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
    $writer->save($filePath);

    $importService = new UserImportService();
    $import = new UsersImport($importService, false);

    Excel::import($import, $filePath);

    $stats = $import->getStats();

    expect($stats['errors'])->toBe(1)
        ->and($stats['created'])->toBe(0)
        ->and(User::count())->toBe(1)
        ->and(User::first()->name)->toBe('Existing User'); // Not updated

    unlink($filePath);
    $importService->cleanup();
});

test('import can update existing users when flag is set', function () {
    // Create existing user
    $existingUser = User::factory()->create([
        'email' => 'existing@example.com',
        'name' => 'Old Name',
    ]);

    $data = [
        ['nombre', 'email', 'contrasena', 'sucursal', 'foto'],
        ['New Name', 'existing@example.com', '', 'Sucursal Test', ''],
    ];

    $filePath = storage_path('app/public/test_import_update.xlsx');
    $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    foreach ($data as $rowIndex => $row) {
        foreach ($row as $colIndex => $value) {
            $sheet->setCellValueByColumnAndRow($colIndex + 1, $rowIndex + 1, $value);
        }
    }

    $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
    $writer->save($filePath);

    $importService = new UserImportService();
    $import = new UsersImport($importService, true); // Enable update

    Excel::import($import, $filePath);

    $stats = $import->getStats();

    expect($stats['updated'])->toBe(1)
        ->and($stats['created'])->toBe(0)
        ->and($stats['errors'])->toBe(0)
        ->and(User::count())->toBe(1)
        ->and(User::first()->name)->toBe('New Name')
        ->and(User::first()->branch_id)->toBe($this->branch->id);

    unlink($filePath);
    $importService->cleanup();
});

test('import assigns branch correctly', function () {
    $data = [
        ['nombre', 'email', 'contrasena', 'sucursal', 'foto'],
        ['User With Branch', 'user@example.com', '', 'Sucursal Test', ''],
    ];

    $filePath = storage_path('app/public/test_import_branch.xlsx');
    $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    foreach ($data as $rowIndex => $row) {
        foreach ($row as $colIndex => $value) {
            $sheet->setCellValueByColumnAndRow($colIndex + 1, $rowIndex + 1, $value);
        }
    }

    $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
    $writer->save($filePath);

    $importService = new UserImportService();
    $import = new UsersImport($importService, false);

    Excel::import($import, $filePath);

    $user = User::where('email', 'user@example.com')->first();

    expect($user)->not->toBeNull()
        ->and($user->branch_id)->toBe($this->branch->id)
        ->and($user->branch->name)->toBe('Sucursal Test');

    unlink($filePath);
    $importService->cleanup();
});
