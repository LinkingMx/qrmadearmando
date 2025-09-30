<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use App\Imports\UsersImport;
use App\Services\UserImportService;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Maatwebsite\Excel\Facades\Excel;

class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('import')
                ->label('Importar Usuarios')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('info')
                ->form([
                    Forms\Components\FileUpload::make('excel')
                        ->label('Archivo Excel')
                        ->acceptedFileTypes([
                            'application/vnd.ms-excel',
                            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                        ])
                        ->required()
                        ->helperText('Descarga la plantilla para ver el formato correcto.')
                        ->columnSpanFull(),
                    Forms\Components\Select::make('photo_mode')
                        ->label('Modo de Fotos')
                        ->options([
                            'zip' => 'Subir ZIP con fotos',
                            'url' => 'URLs en el Excel',
                            'none' => 'Sin fotos',
                        ])
                        ->default('zip')
                        ->required()
                        ->reactive()
                        ->helperText('Selecciona cómo deseas importar las fotos de los usuarios'),
                    Forms\Components\FileUpload::make('zip')
                        ->label('Archivo ZIP con Fotos')
                        ->acceptedFileTypes(['application/zip', 'application/x-zip-compressed'])
                        ->visible(fn (Forms\Get $get) => $get('photo_mode') === 'zip')
                        ->helperText('Las fotos deben tener el mismo nombre que aparece en la columna "foto" del Excel'),
                    Forms\Components\Toggle::make('update_existing')
                        ->label('Actualizar usuarios existentes')
                        ->helperText('Si está activado, actualizará los usuarios que ya existen (por email)')
                        ->default(false),
                ])
                ->action(function (array $data) {
                    try {
                        $importService = new UserImportService();

                        // Handle ZIP photos if provided
                        if ($data['photo_mode'] === 'zip' && !empty($data['zip'])) {
                            $zipFile = $data['zip'];
                            $zipPath = storage_path('app/public/' . $zipFile);

                            if (!file_exists($zipPath)) {
                                throw new \Exception('Archivo ZIP no encontrado.');
                            }

                            $uploadedFile = new \Illuminate\Http\UploadedFile(
                                $zipPath,
                                basename($zipFile),
                                'application/zip',
                                null,
                                true
                            );

                            $importService->extractPhotosFromZip($uploadedFile);

                            Notification::make()
                                ->info()
                                ->title('Fotos extraídas')
                                ->body(count($importService->getExtractedPhotoNames()) . ' fotos encontradas en el ZIP')
                                ->send();
                        }

                        // Import users from Excel
                        $import = new UsersImport($importService, $data['update_existing']);
                        Excel::import($import, storage_path('app/public/' . $data['excel']));

                        // Clean up temporary files
                        $importService->cleanup();

                        // Get statistics
                        $stats = $import->getStats();

                        // Show results
                        if ($stats['errors'] > 0) {
                            $errorReport = $this->generateErrorReport($import);

                            Notification::make()
                                ->warning()
                                ->title('Importación completada con errores')
                                ->body("Creados: {$stats['created']}, Actualizados: {$stats['updated']}, Errores: {$stats['errors']}")
                                ->persistent()
                                ->actions([
                                    \Filament\Notifications\Actions\Action::make('download_errors')
                                        ->button()
                                        ->url(route('download.import-errors', ['file' => $errorReport]))
                                        ->openUrlInNewTab(),
                                ])
                                ->send();
                        } else {
                            Notification::make()
                                ->success()
                                ->title('Importación exitosa')
                                ->body("Se importaron {$stats['created']} usuarios correctamente" .
                                       ($stats['updated'] > 0 ? " y se actualizaron {$stats['updated']}" : ''))
                                ->send();
                        }

                        // Show generated passwords if any
                        $createdWithPasswords = array_filter($import->getCreated(), fn($u) => !empty($u['password']));
                        if (count($createdWithPasswords) > 0) {
                            $passwordReport = $this->generatePasswordReport($createdWithPasswords);

                            Notification::make()
                                ->info()
                                ->title('Contraseñas generadas')
                                ->body('Se generaron contraseñas automáticas. Descarga el reporte.')
                                ->persistent()
                                ->actions([
                                    \Filament\Notifications\Actions\Action::make('download_passwords')
                                        ->button()
                                        ->label('Descargar Contraseñas')
                                        ->url(route('download.import-passwords', ['file' => $passwordReport]))
                                        ->openUrlInNewTab(),
                                ])
                                ->send();
                        }
                    } catch (\Exception $e) {
                        Notification::make()
                            ->danger()
                            ->title('Error en la importación')
                            ->body($e->getMessage())
                            ->send();
                    }
                }),
            Actions\Action::make('download_template')
                ->label('Descargar Plantilla')
                ->icon('heroicon-o-document-arrow-down')
                ->color('gray')
                ->url(route('download.users-template'))
                ->openUrlInNewTab(),
            Actions\CreateAction::make(),
        ];
    }

    protected function generateErrorReport(UsersImport $import): string
    {
        $errors = $import->getErrors();
        $filename = 'errores_importacion_' . now()->format('Y-m-d_His') . '.xlsx';
        $path = storage_path('app/public/temp/' . $filename);

        if (!file_exists(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Headers
        $sheet->setCellValue('A1', 'Fila');
        $sheet->setCellValue('B1', 'Error');
        $sheet->setCellValue('C1', 'Nombre');
        $sheet->setCellValue('D1', 'Email');
        $sheet->setCellValue('E1', 'Sucursal');
        $sheet->setCellValue('F1', 'Foto');

        // Data
        $row = 2;
        foreach ($errors as $error) {
            $sheet->setCellValue('A' . $row, $error['row']);
            $sheet->setCellValue('B' . $row, $error['error']);
            $sheet->setCellValue('C' . $row, $error['data']['nombre'] ?? '');
            $sheet->setCellValue('D' . $row, $error['data']['email'] ?? '');
            $sheet->setCellValue('E' . $row, $error['data']['sucursal'] ?? '');
            $sheet->setCellValue('F' . $row, $error['data']['foto'] ?? '');
            $row++;
        }

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save($path);

        return $filename;
    }

    protected function generatePasswordReport(array $users): string
    {
        $filename = 'contrasenas_generadas_' . now()->format('Y-m-d_His') . '.xlsx';
        $path = storage_path('app/public/temp/' . $filename);

        if (!file_exists(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Headers
        $sheet->setCellValue('A1', 'Nombre');
        $sheet->setCellValue('B1', 'Email');
        $sheet->setCellValue('C1', 'Contraseña Generada');

        // Data
        $row = 2;
        foreach ($users as $user) {
            $sheet->setCellValue('A' . $row, $user['name']);
            $sheet->setCellValue('B' . $row, $user['email']);
            $sheet->setCellValue('C' . $row, $user['password']);
            $row++;
        }

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save($path);

        return $filename;
    }
}
