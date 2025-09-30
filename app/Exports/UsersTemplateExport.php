<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;

class UsersTemplateExport implements FromArray, WithHeadings, WithStyles, WithColumnWidths
{
    public function array(): array
    {
        return [
            [
                'Juan Pérez García',
                'juan.perez@empresa.com',
                'Password123',
                'Sucursal Centro',
                'juan.perez.jpg',
            ],
            [
                'María López Hernández',
                'maria.lopez@empresa.com',
                '',
                'Sucursal Norte',
                'https://ejemplo.com/fotos/maria.jpg',
            ],
            [
                'Carlos Rodríguez',
                'carlos@empresa.com',
                '',
                '',
                'carlos.png',
            ],
        ];
    }

    public function headings(): array
    {
        return [
            'nombre',
            'email',
            'contrasena',
            'sucursal',
            'foto',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Header row styling
        $sheet->getStyle('A1:E1')->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 12,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '4F46E5'],
            ],
            'font' => [
                'color' => ['rgb' => 'FFFFFF'],
                'bold' => true,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                ],
            ],
        ]);

        // Example rows styling
        $sheet->getStyle('A2:E4')->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                ],
            ],
        ]);

        return [];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 30,  // nombre
            'B' => 35,  // email
            'C' => 20,  // contraseña
            'D' => 25,  // sucursal
            'E' => 40,  // foto
        ];
    }
}
