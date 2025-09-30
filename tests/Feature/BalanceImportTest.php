<?php

use App\Imports\BalanceImport;
use App\Models\Branch;
use App\Models\GiftCard;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('public');

    // Create admin user
    $this->admin = User::factory()->create();

    // Create branches
    $this->branch = Branch::factory()->create(['name' => 'Sucursal Test']);

    // Create gift cards
    $this->giftCard1 = GiftCard::factory()->create([
        'status' => true,
        'balance' => 1000,
    ]);

    $this->giftCard2 = GiftCard::factory()->create([
        'status' => true,
        'balance' => 500,
    ]);

    $this->giftCard3 = GiftCard::factory()->create([
        'status' => false, // Inactive
        'balance' => 100,
    ]);
});

test('can import balances with positive amounts', function () {
    $data = [
        ['uuid', 'monto', 'descripcion', 'sucursal'],
        [$this->giftCard1->id, '500', 'Bono mensual', ''],
        [$this->giftCard2->id, '250.50', 'Carga inicial', ''],
    ];

    $filePath = createTestExcel($data);

    $import = new BalanceImport($this->admin->id, true, 2, 1); // startRow=2, headingRow=1 // startRow = 2 for test files
    Excel::import($import, $filePath);

    $stats = $import->getStats();

    expect($stats['processed'])->toBe(2)
        ->and($stats['errors'])->toBe(0)
        ->and($stats['total_credited'])->toBe(750.50)
        ->and($stats['total_debited'])->toBe(0.0)
        ->and((float) $this->giftCard1->fresh()->balance)->toBe(1500.00)
        ->and((float) $this->giftCard2->fresh()->balance)->toBe(750.50);

    unlink($filePath);
});

test('can import balances with negative amounts', function () {
    $data = [
        ['uuid', 'monto', 'descripcion', 'sucursal'],
        [$this->giftCard1->id, '-200', 'Descuento comida', 'Sucursal Test'],
        [$this->giftCard2->id, '-100.50', 'Ajuste', 'Sucursal Test'],
    ];

    $filePath = createTestExcel($data);

    $import = new BalanceImport($this->admin->id, true, 2, 1); // startRow=2, headingRow=1
    Excel::import($import, $filePath);

    $stats = $import->getStats();

    expect($stats['processed'])->toBe(2)
        ->and($stats['errors'])->toBe(0)
        ->and($stats['total_credited'])->toBe(0.0)
        ->and($stats['total_debited'])->toBe(300.50)
        ->and((float) $this->giftCard1->fresh()->balance)->toBe(800.00)
        ->and((float) $this->giftCard2->fresh()->balance)->toBe(399.50);

    unlink($filePath);
});

test('negative amounts require branch', function () {
    $data = [
        ['uuid', 'monto', 'descripcion', 'sucursal'],
        [$this->giftCard1->id, '-200', 'Descuento', ''], // Missing branch
    ];

    $filePath = createTestExcel($data);

    $import = new BalanceImport($this->admin->id, true, 2, 1); // startRow=2, headingRow=1
    Excel::import($import, $filePath);

    $stats = $import->getStats();
    $errors = $import->getErrors();

    expect($stats['errors'])->toBe(1)
        ->and($stats['processed'])->toBe(0)
        ->and($errors[0]['error'])->toContain('Sucursal es requerida');

    unlink($filePath);
});

test('prevents negative balance', function () {
    $data = [
        ['uuid', 'monto', 'descripcion', 'sucursal'],
        [$this->giftCard2->id, '-600', 'Descuento excesivo', 'Sucursal Test'], // Only has 500
    ];

    $filePath = createTestExcel($data);

    $import = new BalanceImport($this->admin->id, true, 2, 1); // startRow=2, headingRow=1
    Excel::import($import, $filePath);

    $stats = $import->getStats();
    $errors = $import->getErrors();

    expect($stats['errors'])->toBe(1)
        ->and($stats['processed'])->toBe(0)
        ->and($errors[0]['error'])->toContain('Insufficient balance')
        ->and((float) $this->giftCard2->fresh()->balance)->toBe(500.00); // Unchanged

    unlink($filePath);
});

test('validates uuid exists', function () {
    $fakeUuid = '123e4567-e89b-12d3-a456-426614174000';

    $data = [
        ['uuid', 'monto', 'descripcion', 'sucursal'],
        [$fakeUuid, '+500', 'Test', ''],
    ];

    $filePath = createTestExcel($data);

    $import = new BalanceImport($this->admin->id, true, 2, 1); // startRow=2, headingRow=1
    Excel::import($import, $filePath);

    $stats = $import->getStats();
    $errors = $import->getErrors();

    expect($stats['errors'])->toBe(1)
        ->and($stats['processed'])->toBe(0)
        ->and($errors[0]['error'])->toBe('QR Empleado no encontrado');

    unlink($filePath);
});

test('validates gift card is active', function () {
    $data = [
        ['uuid', 'monto', 'descripcion', 'sucursal'],
        [$this->giftCard3->id, '+500', 'Test', ''], // Inactive
    ];

    $filePath = createTestExcel($data);

    $import = new BalanceImport($this->admin->id, true, 2, 1); // startRow=2, headingRow=1
    Excel::import($import, $filePath);

    $stats = $import->getStats();
    $errors = $import->getErrors();

    expect($stats['errors'])->toBe(1)
        ->and($stats['processed'])->toBe(0)
        ->and($errors[0]['error'])->toContain('inactivo');

    unlink($filePath);
});

test('allows multiple charges to same qr', function () {
    $data = [
        ['uuid', 'monto', 'descripcion', 'sucursal'],
        [$this->giftCard1->id, '500', 'Primera carga', ''],
        [$this->giftCard1->id, '300', 'Segunda carga', ''],
        [$this->giftCard1->id, '-200', 'Descuento', 'Sucursal Test'],
    ];

    $filePath = createTestExcel($data);

    $import = new BalanceImport($this->admin->id, true, 2, 1); // startRow=2, headingRow=1
    Excel::import($import, $filePath);

    $stats = $import->getStats();

    expect($stats['processed'])->toBe(3)
        ->and($stats['errors'])->toBe(0)
        ->and((float) $this->giftCard1->fresh()->balance)->toBe(1600.00); // 1000 + 500 + 300 - 200

    unlink($filePath);
});

test('prevents multiple charges when disabled', function () {
    $data = [
        ['uuid', 'monto', 'descripcion', 'sucursal'],
        [$this->giftCard1->id, '500', 'Primera carga', ''],
        [$this->giftCard1->id, '300', 'Segunda carga', ''], // Should error
    ];

    $filePath = createTestExcel($data);

    $import = new BalanceImport($this->admin->id, false, 2, 1); // startRow=2, headingRow=1 // Disable multiple
    Excel::import($import, $filePath);

    $stats = $import->getStats();
    $errors = $import->getErrors();

    expect($stats['processed'])->toBe(1)
        ->and($stats['errors'])->toBe(1)
        ->and($errors[0]['error'])->toContain('duplicado')
        ->and((float) $this->giftCard1->fresh()->balance)->toBe(1500.00); // Only first one

    unlink($filePath);
});

test('validates branch exists', function () {
    $data = [
        ['uuid', 'monto', 'descripcion', 'sucursal'],
        [$this->giftCard1->id, '-100', 'Test', 'Sucursal Inexistente'],
    ];

    $filePath = createTestExcel($data);

    $import = new BalanceImport($this->admin->id, true, 2, 1); // startRow=2, headingRow=1
    Excel::import($import, $filePath);

    $stats = $import->getStats();
    $errors = $import->getErrors();

    expect($stats['errors'])->toBe(1)
        ->and($stats['processed'])->toBe(0)
        ->and($errors[0]['error'])->toContain('no encontrada');

    unlink($filePath);
});

test('calculates statistics correctly', function () {
    $data = [
        ['uuid', 'monto', 'descripcion', 'sucursal'],
        [$this->giftCard1->id, '1000', 'Carga', ''],
        [$this->giftCard1->id, '500', 'Otra carga', ''],
        [$this->giftCard1->id, '-300', 'Descuento', 'Sucursal Test'],
        [$this->giftCard2->id, '250.50', 'Carga', ''],
    ];

    $filePath = createTestExcel($data);

    $import = new BalanceImport($this->admin->id, true, 2, 1); // startRow=2, headingRow=1
    Excel::import($import, $filePath);

    $stats = $import->getStats();

    expect($stats['processed'])->toBe(4)
        ->and($stats['errors'])->toBe(0)
        ->and($stats['total_credited'])->toBe(1750.50)
        ->and($stats['total_debited'])->toBe(300.00)
        ->and($stats['net_change'])->toBe(1450.50);

    unlink($filePath);
});

test('accepts amounts with explicit plus sign', function () {
    $data = [
        ['uuid', 'monto', 'descripcion', 'sucursal'],
        [$this->giftCard1->id, '+500', 'Con signo +', ''],
        [$this->giftCard2->id, '300', 'Sin signo', ''],
    ];

    $filePath = createTestExcel($data);

    $import = new BalanceImport($this->admin->id, true, 2, 1); // startRow=2, headingRow=1
    Excel::import($import, $filePath);

    $stats = $import->getStats();

    expect($stats['processed'])->toBe(2)
        ->and($stats['errors'])->toBe(0)
        ->and((float) $this->giftCard1->fresh()->balance)->toBe(1500.00)
        ->and((float) $this->giftCard2->fresh()->balance)->toBe(800.00);

    unlink($filePath);
});

// Helper function to create test Excel files
function createTestExcel(array $data): string
{
    $filePath = storage_path('app/public/test_balance_import_' . uniqid() . '.xlsx');
    $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    foreach ($data as $rowIndex => $row) {
        foreach ($row as $colIndex => $value) {
            $sheet->setCellValueByColumnAndRow($colIndex + 1, $rowIndex + 1, $value);
        }
    }

    $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
    $writer->save($filePath);

    return $filePath;
}
