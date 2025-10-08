<?php

namespace App\Exports;

use App\Models\User;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class UsersExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithColumnWidths, WithEvents
{
    protected ?Collection $users = null;
    protected array $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function collection()
    {
        $query = User::with(['branch'])
            ->withCount('giftCards');

        // Apply filters
        if (!empty($this->filters['branch_id'])) {
            $query->where('branch_id', $this->filters['branch_id']);
        }

        if (!empty($this->filters['email_verified'])) {
            if ($this->filters['email_verified'] === 'yes') {
                $query->whereNotNull('email_verified_at');
            } elseif ($this->filters['email_verified'] === 'no') {
                $query->whereNull('email_verified_at');
            }
        }

        if (!empty($this->filters['two_factor'])) {
            if ($this->filters['two_factor'] === 'yes') {
                $query->whereNotNull('two_factor_confirmed_at');
            } elseif ($this->filters['two_factor'] === 'no') {
                $query->whereNull('two_factor_confirmed_at');
            }
        }

        if (!empty($this->filters['has_gift_cards'])) {
            $query->has('giftCards');
        }

        $this->users = $query->orderBy('name')->get();

        return $this->users;
    }

    public function headings(): array
    {
        return [
            'ID',
            'Nombre',
            'Email',
            'Sucursal',
            'Email Verificado',
            '2FA Activado',
            'Total QR Asignados',
            'Fecha Creación',
            'Fecha Actualización',
        ];
    }

    public function map($user): array
    {
        return [
            $user->id,
            $user->name,
            $user->email,
            $user->branch?->name ?? 'Sin asignar',
            $user->email_verified_at ? 'Sí' : 'No',
            $user->two_factor_confirmed_at ? 'Sí' : 'No',
            $user->gift_cards_count,
            $user->created_at->format('d/m/Y H:i'),
            $user->updated_at->format('d/m/Y H:i'),
        ];
    }

    public function styles($sheet)
    {
        return [];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 8,   // ID
            'B' => 30,  // Nombre
            'C' => 35,  // Email
            'D' => 25,  // Sucursal
            'E' => 18,  // Email Verificado
            'F' => 16,  // 2FA
            'G' => 18,  // Total QR
            'H' => 20,  // Fecha Creación
            'I' => 20,  // Fecha Actualización
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                $lastRow = $sheet->getHighestRow();

                // Header styling
                $sheet->getStyle('A1:I1')->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'size' => 11,
                        'color' => ['rgb' => 'FFFFFF'],
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '4F46E5'],
                    ],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                        ],
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                    ],
                ]);

                // Data rows styling
                if ($lastRow > 1) {
                    $sheet->getStyle("A2:I{$lastRow}")->applyFromArray([
                        'borders' => [
                            'allBorders' => [
                                'borderStyle' => Border::BORDER_THIN,
                            ],
                        ],
                    ]);

                    // Center align ID and counts
                    $sheet->getStyle("A2:A{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $sheet->getStyle("G2:G{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                    // Center align status columns
                    $sheet->getStyle("E2:F{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                    // Color code email verified
                    for ($row = 2; $row <= $lastRow; $row++) {
                        $emailVerified = $sheet->getCell("E{$row}")->getValue();
                        if ($emailVerified === 'Sí') {
                            $sheet->getStyle("E{$row}")->applyFromArray([
                                'font' => [
                                    'color' => ['rgb' => '16A34A'],
                                    'bold' => true,
                                ],
                            ]);
                        } else {
                            $sheet->getStyle("E{$row}")->applyFromArray([
                                'font' => [
                                    'color' => ['rgb' => '71717A'],
                                ],
                            ]);
                        }

                        // Color code 2FA
                        $twoFactor = $sheet->getCell("F{$row}")->getValue();
                        if ($twoFactor === 'Sí') {
                            $sheet->getStyle("F{$row}")->applyFromArray([
                                'font' => [
                                    'color' => ['rgb' => '2563EB'],
                                    'bold' => true,
                                ],
                            ]);
                        } else {
                            $sheet->getStyle("F{$row}")->applyFromArray([
                                'font' => [
                                    'color' => ['rgb' => '71717A'],
                                ],
                            ]);
                        }

                        // Highlight users with QR cards
                        $qrCount = intval($sheet->getCell("G{$row}")->getValue());
                        if ($qrCount > 0) {
                            $sheet->getStyle("G{$row}")->applyFromArray([
                                'font' => [
                                    'color' => ['rgb' => '16A34A'],
                                    'bold' => true,
                                ],
                            ]);
                        }
                    }

                    // Add summary row
                    $summaryRow = $lastRow + 2;
                    $sheet->setCellValue("A{$summaryRow}", 'RESUMEN');
                    $sheet->setCellValue("B{$summaryRow}", 'Total Usuarios:');
                    $sheet->setCellValue("C{$summaryRow}", ($lastRow - 1));

                    $sheet->setCellValue("D{$summaryRow}", 'Total QR:');
                    $sheet->setCellValue("E{$summaryRow}", "=SUM(G2:G{$lastRow})");

                    $verifiedCount = 0;
                    $twoFactorCount = 0;
                    foreach ($this->users as $user) {
                        if ($user->email_verified_at) $verifiedCount++;
                        if ($user->two_factor_confirmed_at) $twoFactorCount++;
                    }

                    $sheet->setCellValue("F{$summaryRow}", 'Verificados:');
                    $sheet->setCellValue("G{$summaryRow}", $verifiedCount);

                    $sheet->setCellValue("H{$summaryRow}", '2FA Activo:');
                    $sheet->setCellValue("I{$summaryRow}", $twoFactorCount);

                    $sheet->getStyle("A{$summaryRow}:I{$summaryRow}")->applyFromArray([
                        'font' => [
                            'bold' => true,
                        ],
                        'fill' => [
                            'fillType' => Fill::FILL_SOLID,
                            'startColor' => ['rgb' => 'F3F4F6'],
                        ],
                    ]);
                }
            },
        ];
    }
}
