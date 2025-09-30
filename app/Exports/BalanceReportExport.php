<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class BalanceReportExport implements WithMultipleSheets
{
    protected array $stats;
    protected array $processed;
    protected array $errors;

    public function __construct(array $stats, array $processed, array $errors)
    {
        $this->stats = $stats;
        $this->processed = $processed;
        $this->errors = $errors;
    }

    public function sheets(): array
    {
        return [
            new BalanceReportSummarySheet($this->stats),
            new BalanceReportDetailSheet($this->processed),
            new BalanceReportErrorsSheet($this->errors),
        ];
    }
}
