# Implementation Plan: Sistema de Validación CFDI Robusto

**Date**: 2026-02-04
**Planner**: Agent 1 (Opus 4.5)
**Branch**: `feature/cfdi-validation-system`
**Status**: Awaiting Approval

---

## Summary

Implementar un sistema completo de validación de CFDI que reemplace el parser manual actual, integrando tres librerías del ecosistema phpCfdi:
1. **eclipxe13/cfdiutils** - Validación estructural y XSD
2. **phpcfdi/sat-estado-cfdi** - Consulta de estatus SAT y verificación EFOS
3. **phpcfdi/cfditopdf** - Generación de PDF representación impresa

---

## Requirements Confirmed

- [x] Reemplazar CfdiParserService con validación robusta
- [x] Validar estructura XML contra esquemas XSD del SAT
- [x] Verificar firma (Sello) y Timbre Fiscal Digital
- [x] Consultar estatus en WebService del SAT (Vigente/Cancelado)
- [x] Verificar EFOS (lista negra de empresas fantasma)
- [x] Generar PDF del CFDI para visualización
- [x] Mostrar alertas visuales en Filament según resultado
- [x] Validación asíncrona para no bloquear al usuario

---

## Technology Stack

- **Language**: PHP 8.3
- **Framework**: Laravel 12 + Filament v4
- **New Dependencies**:
  - `eclipxe/cfdiutils` ^3.0
  - `phpcfdi/sat-estado-cfdi` ^1.0
  - `phpcfdi/cfditopdf` ^0.5

---

## Architecture Overview

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                        CFDI VALIDATION SYSTEM                                │
├─────────────────────────────────────────────────────────────────────────────┤
│                                                                              │
│  ┌─────────────────┐    ┌─────────────────┐    ┌─────────────────┐         │
│  │  CfdiParser     │    │  CfdiValidator  │    │  CfdiPdfService │         │
│  │  Service        │    │  Service        │    │                 │         │
│  │  (cfdiutils)    │    │  (sat-estado)   │    │  (cfditopdf)    │         │
│  └────────┬────────┘    └────────┬────────┘    └────────┬────────┘         │
│           │                      │                      │                   │
│           └──────────────────────┼──────────────────────┘                   │
│                                  │                                          │
│                                  ▼                                          │
│                    ┌─────────────────────────┐                              │
│                    │   CfdiService (Facade)  │                              │
│                    │   - parse()             │                              │
│                    │   - validate()          │                              │
│                    │   - validateWithSat()   │                              │
│                    │   - generatePdf()       │                              │
│                    └─────────────────────────┘                              │
│                                                                              │
└─────────────────────────────────────────────────────────────────────────────┘
```

---

## Files to Create/Modify

### New Files

| File Path | Purpose |
|-----------|---------|
| `app/Services/Cfdi/CfdiService.php` | Facade principal que orquesta los servicios |
| `app/Services/Cfdi/CfdiParserService.php` | Parser usando cfdiutils (reemplaza el actual) |
| `app/Services/Cfdi/CfdiValidatorService.php` | Validación estructural XSD y firma |
| `app/Services/Cfdi/SatValidatorService.php` | Consulta SAT y verificación EFOS |
| `app/Services/Cfdi/CfdiPdfService.php` | Generación de PDF |
| `app/DTOs/Cfdi/CfdiData.php` | DTO para datos extraídos del CFDI |
| `app/DTOs/Cfdi/ValidationResult.php` | DTO para resultado de validación estructural |
| `app/DTOs/Cfdi/SatValidationResult.php` | DTO para resultado de validación SAT |
| `app/Enums/CfdiValidationStatus.php` | Enum para estados de validación |
| `app/Enums/SatStatus.php` | Enum para estados del SAT |
| `app/Enums/EfosStatus.php` | Enum para estados EFOS |
| `app/Jobs/ValidateCfdiWithSatJob.php` | Job para validación asíncrona con SAT |
| `app/Events/CfdiValidated.php` | Evento cuando se valida un CFDI |
| `app/Listeners/UpdateReceiptAfterSatValidation.php` | Listener para actualizar receipt |
| `database/migrations/XXXX_add_sat_validation_fields_to_expense_receipts_table.php` | Nuevos campos |
| `config/cfdi.php` | Configuración del sistema CFDI |
| `tests/Unit/Services/CfdiParserServiceTest.php` | Tests del parser |
| `tests/Unit/Services/CfdiValidatorServiceTest.php` | Tests de validación |
| `tests/Feature/CfdiValidationFlowTest.php` | Tests del flujo completo |

### Modified Files

| File Path | Changes |
|-----------|---------|
| `app/Services/CfdiParserService.php` | Deprecar y redirigir a nuevo servicio |
| `app/Models/ExpenseReceipt.php` | Agregar campos y métodos de validación SAT |
| `app/Filament/Resources/ExpenseVerifications/Schemas/ExpenseVerificationForm.php` | Integrar validación y alertas |
| `app/Providers/AppServiceProvider.php` | Registrar servicios |
| `composer.json` | Agregar dependencias |

---

## Database Changes

### Migration: Add SAT Validation Fields to ExpenseReceipts

```php
Schema::table('expense_receipts', function (Blueprint $table) {
    // Structural validation
    $table->string('validation_status')->default('pending')->after('status');
    $table->json('validation_errors')->nullable()->after('validation_status');
    $table->timestamp('validated_at')->nullable()->after('validation_errors');

    // SAT validation
    $table->string('sat_status')->nullable()->after('validated_at'); // vigente, cancelado, no_encontrado
    $table->string('sat_cancellation_status')->nullable()->after('sat_status');
    $table->boolean('sat_validated')->default(false)->after('sat_cancellation_status');
    $table->timestamp('sat_validated_at')->nullable()->after('sat_validated');
    $table->json('sat_response')->nullable()->after('sat_validated_at');

    // EFOS check
    $table->boolean('emisor_in_efos')->default(false)->after('sat_response');
    $table->boolean('receptor_in_efos')->default(false)->after('emisor_in_efos');

    // PDF generation
    $table->string('pdf_file_path')->nullable()->after('receptor_in_efos');
    $table->timestamp('pdf_generated_at')->nullable()->after('pdf_file_path');

    // Index for queries
    $table->index(['validation_status', 'sat_status']);
});
```

---

## Step-by-Step Implementation

### Step 1: Install Dependencies
**Objective**: Agregar las tres librerías al proyecto
**Actions**:
```bash
composer require eclipxe/cfdiutils phpcfdi/sat-estado-cfdi phpcfdi/cfditopdf
```

**Verificar compatibilidad**:
- eclipxe/cfdiutils requiere PHP 8.0+
- phpcfdi/sat-estado-cfdi requiere PHP 8.1+
- phpcfdi/cfditopdf requiere PHP 8.1+

---

### Step 2: Create Configuration File
**Objective**: Centralizar configuración del sistema CFDI
**File**: `config/cfdi.php`

```php
<?php

return [
    /*
    |--------------------------------------------------------------------------
    | CFDI Validation Settings
    |--------------------------------------------------------------------------
    */

    'validation' => [
        // Habilitar validación estructural
        'structural_enabled' => env('CFDI_STRUCTURAL_VALIDATION', true),

        // Habilitar validación SAT
        'sat_enabled' => env('CFDI_SAT_VALIDATION', true),

        // Timeout para consulta SAT (segundos)
        'sat_timeout' => env('CFDI_SAT_TIMEOUT', 30),

        // Reintentos en caso de fallo
        'sat_retries' => env('CFDI_SAT_RETRIES', 3),

        // Cache de resultados SAT (segundos)
        'sat_cache_ttl' => env('CFDI_SAT_CACHE_TTL', 3600),
    ],

    'pdf' => [
        // Habilitar generación de PDF
        'enabled' => env('CFDI_PDF_ENABLED', true),

        // Directorio de almacenamiento
        'storage_path' => 'expense-receipts/cfdi-pdf',

        // Disco de almacenamiento
        'disk' => env('CFDI_PDF_DISK', 'private'),
    ],

    'alerts' => [
        // Bloquear CFDIs cancelados
        'block_cancelled' => env('CFDI_BLOCK_CANCELLED', true),

        // Alertar sobre EFOS (no bloquear, solo alertar)
        'warn_efos' => env('CFDI_WARN_EFOS', true),

        // Bloquear EFOS (además de alertar)
        'block_efos' => env('CFDI_BLOCK_EFOS', false),
    ],
];
```

---

### Step 3: Create DTOs
**Objective**: Estructuras de datos tipadas para resultados

**File**: `app/DTOs/Cfdi/CfdiData.php`
```php
<?php

declare(strict_types=1);

namespace App\DTOs\Cfdi;

readonly class CfdiData
{
    public function __construct(
        public string $version,
        public string $uuid,
        public string $fecha,
        public ?string $fechaTimbrado,
        public ?string $serie,
        public ?string $folio,
        public float $subtotal,
        public float $total,
        public float $descuento,
        public string $moneda,
        public float $tipoCambio,
        public string $tipoComprobante,
        public ?string $formaPago,
        public ?string $metodoPago,
        public array $emisor,
        public array $receptor,
        public array $conceptos,
        public array $impuestos,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(...$data);
    }

    public function toArray(): array
    {
        return get_object_vars($this);
    }
}
```

**File**: `app/DTOs/Cfdi/ValidationResult.php`
```php
<?php

declare(strict_types=1);

namespace App\DTOs\Cfdi;

use App\Enums\CfdiValidationStatus;

readonly class ValidationResult
{
    public function __construct(
        public CfdiValidationStatus $status,
        public bool $isValid,
        public array $errors = [],
        public array $warnings = [],
        public ?CfdiData $data = null,
    ) {}

    public function hasErrors(): bool
    {
        return count($this->errors) > 0;
    }

    public function hasWarnings(): bool
    {
        return count($this->warnings) > 0;
    }
}
```

**File**: `app/DTOs/Cfdi/SatValidationResult.php`
```php
<?php

declare(strict_types=1);

namespace App\DTOs\Cfdi;

use App\Enums\EfosStatus;
use App\Enums\SatStatus;

readonly class SatValidationResult
{
    public function __construct(
        public SatStatus $status,
        public bool $isVigente,
        public bool $isCancelado,
        public ?string $cancellationStatus,
        public EfosStatus $emisorEfos,
        public EfosStatus $receptorEfos,
        public array $rawResponse = [],
    ) {}

    public function hasEfosAlert(): bool
    {
        return $this->emisorEfos === EfosStatus::Included
            || $this->receptorEfos === EfosStatus::Included;
    }

    public function canBeUsed(): bool
    {
        return $this->isVigente && !$this->hasEfosAlert();
    }
}
```

---

### Step 4: Create Enums
**Objective**: Estados tipados para validación

**File**: `app/Enums/CfdiValidationStatus.php`
```php
<?php

declare(strict_types=1);

namespace App\Enums;

enum CfdiValidationStatus: string
{
    case Pending = 'pending';
    case Valid = 'valid';
    case Invalid = 'invalid';
    case Error = 'error';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pendiente',
            self::Valid => 'Válido',
            self::Invalid => 'Inválido',
            self::Error => 'Error',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending => 'gray',
            self::Valid => 'success',
            self::Invalid => 'danger',
            self::Error => 'warning',
        };
    }
}
```

**File**: `app/Enums/SatStatus.php`
```php
<?php

declare(strict_types=1);

namespace App\Enums;

enum SatStatus: string
{
    case Vigente = 'vigente';
    case Cancelado = 'cancelado';
    case NoEncontrado = 'no_encontrado';
    case Pending = 'pending';
    case Error = 'error';

    public function label(): string
    {
        return match ($this) {
            self::Vigente => 'Vigente',
            self::Cancelado => 'Cancelado',
            self::NoEncontrado => 'No Encontrado',
            self::Pending => 'Pendiente',
            self::Error => 'Error de Consulta',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Vigente => 'success',
            self::Cancelado => 'danger',
            self::NoEncontrado => 'warning',
            self::Pending => 'gray',
            self::Error => 'warning',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Vigente => 'heroicon-o-check-badge',
            self::Cancelado => 'heroicon-o-x-circle',
            self::NoEncontrado => 'heroicon-o-question-mark-circle',
            self::Pending => 'heroicon-o-clock',
            self::Error => 'heroicon-o-exclamation-triangle',
        };
    }
}
```

**File**: `app/Enums/EfosStatus.php`
```php
<?php

declare(strict_types=1);

namespace App\Enums;

enum EfosStatus: string
{
    case Included = 'included';   // En lista negra
    case Excluded = 'excluded';   // Limpio
    case Unknown = 'unknown';     // No verificado

    public function label(): string
    {
        return match ($this) {
            self::Included => 'En Lista EFOS ⚠️',
            self::Excluded => 'Sin Alertas',
            self::Unknown => 'No Verificado',
        };
    }

    public function isAlert(): bool
    {
        return $this === self::Included;
    }
}
```

---

### Step 5: Create Parser Service (cfdiutils)
**Objective**: Reemplazar parser manual con cfdiutils
**File**: `app/Services/Cfdi/CfdiParserService.php`

```php
<?php

declare(strict_types=1);

namespace App\Services\Cfdi;

use App\DTOs\Cfdi\CfdiData;
use App\Exceptions\CfdiParserException;
use CfdiUtils\Cfdi;
use CfdiUtils\Cleaner\Cleaner;
use CfdiUtils\Nodes\XmlNodeUtils;
use Illuminate\Support\Facades\Log;

class CfdiParserService
{
    /**
     * Parse CFDI XML and extract data.
     */
    public function parse(string $xmlContent): CfdiData
    {
        try {
            // Clean XML from invalid namespaces
            $cleanXml = Cleaner::staticClean($xmlContent);

            // Load CFDI
            $cfdi = Cfdi::newFromString($cleanXml);
            $comprobante = $cfdi->getNode();

            // Extract TimbreFiscalDigital
            $timbre = $comprobante->searchNode('cfdi:Complemento', 'tfd:TimbreFiscalDigital');

            if (!$timbre) {
                throw new CfdiParserException('El XML no contiene TimbreFiscalDigital');
            }

            // Extract Emisor
            $emisorNode = $comprobante->searchNode('cfdi:Emisor');
            $emisor = [
                'rfc' => $emisorNode['Rfc'] ?? '',
                'nombre' => $emisorNode['Nombre'] ?? '',
                'regimen_fiscal' => $emisorNode['RegimenFiscal'] ?? null,
            ];

            // Extract Receptor
            $receptorNode = $comprobante->searchNode('cfdi:Receptor');
            $receptor = [
                'rfc' => $receptorNode['Rfc'] ?? '',
                'nombre' => $receptorNode['Nombre'] ?? '',
                'uso_cfdi' => $receptorNode['UsoCFDI'] ?? null,
                'domicilio_fiscal' => $receptorNode['DomicilioFiscalReceptor'] ?? null,
                'regimen_fiscal' => $receptorNode['RegimenFiscalReceptor'] ?? null,
            ];

            // Extract Conceptos
            $conceptos = $this->extractConceptos($comprobante);

            // Extract Impuestos
            $impuestos = $this->extractImpuestos($comprobante);

            return new CfdiData(
                version: $comprobante['Version'] ?? $comprobante['version'] ?? '',
                uuid: strtoupper($timbre['UUID'] ?? ''),
                fecha: $comprobante['Fecha'] ?? '',
                fechaTimbrado: $timbre['FechaTimbrado'] ?? null,
                serie: $comprobante['Serie'] ?? null,
                folio: $comprobante['Folio'] ?? null,
                subtotal: (float) ($comprobante['SubTotal'] ?? 0),
                total: (float) ($comprobante['Total'] ?? 0),
                descuento: (float) ($comprobante['Descuento'] ?? 0),
                moneda: $comprobante['Moneda'] ?? 'MXN',
                tipoCambio: (float) ($comprobante['TipoCambio'] ?? 1),
                tipoComprobante: $comprobante['TipoDeComprobante'] ?? '',
                formaPago: $comprobante['FormaPago'] ?? null,
                metodoPago: $comprobante['MetodoPago'] ?? null,
                emisor: $emisor,
                receptor: $receptor,
                conceptos: $conceptos,
                impuestos: $impuestos,
            );

        } catch (CfdiParserException $e) {
            throw $e;
        } catch (\Throwable $e) {
            Log::error('CFDI Parser Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw new CfdiParserException(
                'Error al procesar el XML del CFDI: ' . $e->getMessage(),
                previous: $e
            );
        }
    }

    /**
     * Get clean XML content.
     */
    public function cleanXml(string $xmlContent): string
    {
        return Cleaner::staticClean($xmlContent);
    }

    /**
     * Extract conceptos from comprobante.
     */
    private function extractConceptos($comprobante): array
    {
        $conceptosNode = $comprobante->searchNode('cfdi:Conceptos');
        if (!$conceptosNode) {
            return [];
        }

        $conceptos = [];
        foreach ($conceptosNode->searchNodes('cfdi:Concepto') as $concepto) {
            $conceptos[] = [
                'clave_prod_serv' => $concepto['ClaveProdServ'] ?? '',
                'no_identificacion' => $concepto['NoIdentificacion'] ?? null,
                'cantidad' => (float) ($concepto['Cantidad'] ?? 1),
                'clave_unidad' => $concepto['ClaveUnidad'] ?? '',
                'unidad' => $concepto['Unidad'] ?? null,
                'descripcion' => $concepto['Descripcion'] ?? '',
                'valor_unitario' => (float) ($concepto['ValorUnitario'] ?? 0),
                'importe' => (float) ($concepto['Importe'] ?? 0),
                'descuento' => (float) ($concepto['Descuento'] ?? 0),
            ];
        }

        return $conceptos;
    }

    /**
     * Extract impuestos from comprobante.
     */
    private function extractImpuestos($comprobante): array
    {
        $impuestosNode = $comprobante->searchNode('cfdi:Impuestos');

        return [
            'total_trasladados' => (float) ($impuestosNode['TotalImpuestosTrasladados'] ?? 0),
            'total_retenidos' => (float) ($impuestosNode['TotalImpuestosRetenidos'] ?? 0),
            'traslados' => [],
            'retenciones' => [],
        ];
    }
}
```

---

### Step 6: Create Validator Service (cfdiutils)
**Objective**: Validación estructural contra XSD
**File**: `app/Services/Cfdi/CfdiValidatorService.php`

```php
<?php

declare(strict_types=1);

namespace App\Services\Cfdi;

use App\DTOs\Cfdi\ValidationResult;
use App\Enums\CfdiValidationStatus;
use CfdiUtils\Cfdi;
use CfdiUtils\CfdiValidator40;
use CfdiUtils\CfdiValidator33;
use CfdiUtils\Cleaner\Cleaner;
use Illuminate\Support\Facades\Log;

class CfdiValidatorService
{
    public function __construct(
        private readonly CfdiParserService $parser
    ) {}

    /**
     * Validate CFDI structure, XSD and signatures.
     */
    public function validate(string $xmlContent): ValidationResult
    {
        try {
            // Clean XML first
            $cleanXml = Cleaner::staticClean($xmlContent);

            // Load CFDI
            $cfdi = Cfdi::newFromString($cleanXml);

            // Detect version and get appropriate validator
            $version = $cfdi->getVersion();
            $validator = $this->getValidatorForVersion($version);

            // Run validation
            $asserts = $validator->validate($cfdi->getSource(), $cfdi->getNode());

            // Collect errors and warnings
            $errors = [];
            $warnings = [];

            foreach ($asserts as $assert) {
                $item = [
                    'code' => $assert->getCode(),
                    'title' => $assert->getTitle(),
                    'message' => $assert->getExplain(),
                    'status' => $assert->getStatus()->name(),
                ];

                if ($assert->getStatus()->isError()) {
                    $errors[] = $item;
                } elseif ($assert->getStatus()->isWarning()) {
                    $warnings[] = $item;
                }
            }

            // Parse data if valid
            $data = null;
            $isValid = count($errors) === 0;

            if ($isValid) {
                $data = $this->parser->parse($cleanXml);
            }

            return new ValidationResult(
                status: $isValid ? CfdiValidationStatus::Valid : CfdiValidationStatus::Invalid,
                isValid: $isValid,
                errors: $errors,
                warnings: $warnings,
                data: $data,
            );

        } catch (\Throwable $e) {
            Log::error('CFDI Validation Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return new ValidationResult(
                status: CfdiValidationStatus::Error,
                isValid: false,
                errors: [[
                    'code' => 'EXCEPTION',
                    'title' => 'Error de Validación',
                    'message' => $e->getMessage(),
                    'status' => 'ERROR',
                ]],
            );
        }
    }

    /**
     * Quick structure check without full validation.
     */
    public function isValidStructure(string $xmlContent): bool
    {
        try {
            $cleanXml = Cleaner::staticClean($xmlContent);
            $cfdi = Cfdi::newFromString($cleanXml);

            // Check minimum required data exists
            $node = $cfdi->getNode();
            $timbre = $node->searchNode('cfdi:Complemento', 'tfd:TimbreFiscalDigital');

            return $timbre !== null && !empty($timbre['UUID']);
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Get validator instance for CFDI version.
     */
    private function getValidatorForVersion(string $version): CfdiValidator40|CfdiValidator33
    {
        return match ($version) {
            '4.0' => new CfdiValidator40(),
            '3.3' => new CfdiValidator33(),
            default => new CfdiValidator40(),
        };
    }
}
```

---

### Step 7: Create SAT Validator Service (sat-estado-cfdi)
**Objective**: Consulta de estatus SAT y verificación EFOS
**File**: `app/Services/Cfdi/SatValidatorService.php`

```php
<?php

declare(strict_types=1);

namespace App\Services\Cfdi;

use App\DTOs\Cfdi\CfdiData;
use App\DTOs\Cfdi\SatValidationResult;
use App\Enums\EfosStatus;
use App\Enums\SatStatus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use PhpCfdi\SatEstadoCfdi\Consumer;
use PhpCfdi\SatEstadoCfdi\Expression;

class SatValidatorService
{
    private Consumer $consumer;

    public function __construct()
    {
        $this->consumer = new Consumer();
    }

    /**
     * Validate CFDI with SAT WebService.
     */
    public function validate(CfdiData $cfdiData): SatValidationResult
    {
        $cacheKey = "sat_status_{$cfdiData->uuid}";
        $cacheTtl = config('cfdi.validation.sat_cache_ttl', 3600);

        return Cache::remember($cacheKey, $cacheTtl, function () use ($cfdiData) {
            return $this->executeValidation($cfdiData);
        });
    }

    /**
     * Validate without cache.
     */
    public function validateFresh(CfdiData $cfdiData): SatValidationResult
    {
        $cacheKey = "sat_status_{$cfdiData->uuid}";
        Cache::forget($cacheKey);

        return $this->validate($cfdiData);
    }

    /**
     * Execute the actual validation with retries.
     */
    private function executeValidation(CfdiData $cfdiData): SatValidationResult
    {
        $retries = config('cfdi.validation.sat_retries', 3);

        return retry($retries, function () use ($cfdiData) {
            return $this->doValidation($cfdiData);
        }, 1000); // 1s between retries
    }

    /**
     * Perform the SAT validation.
     */
    private function doValidation(CfdiData $cfdiData): SatValidationResult
    {
        try {
            // Build expression based on CFDI version
            $expression = $this->buildExpression($cfdiData);

            // Execute query
            $response = $this->consumer->execute($expression);

            // Parse response
            $cfdiStatus = $response->cfdi();
            $efosStatus = $response->efos();
            $cancellation = $response->cancellation();

            // Determine SAT status
            $satStatus = match (true) {
                $cfdiStatus->isVigente() => SatStatus::Vigente,
                $cfdiStatus->isCancelado() => SatStatus::Cancelado,
                $cfdiStatus->isNotFound() => SatStatus::NoEncontrado,
                default => SatStatus::Error,
            };

            // Determine EFOS status
            $emisorEfos = $efosStatus->isExcluded() ? EfosStatus::Excluded : EfosStatus::Included;

            return new SatValidationResult(
                status: $satStatus,
                isVigente: $cfdiStatus->isVigente(),
                isCancelado: $cfdiStatus->isCancelado(),
                cancellationStatus: $cancellation->name(),
                emisorEfos: $emisorEfos,
                receptorEfos: EfosStatus::Unknown, // SAT no reporta receptor
                rawResponse: [
                    'cfdi' => $cfdiStatus->name(),
                    'efos' => $efosStatus->name(),
                    'cancellation' => $cancellation->name(),
                ],
            );

        } catch (\Throwable $e) {
            Log::error('SAT Validation Error', [
                'uuid' => $cfdiData->uuid,
                'error' => $e->getMessage(),
            ]);

            return new SatValidationResult(
                status: SatStatus::Error,
                isVigente: false,
                isCancelado: false,
                cancellationStatus: null,
                emisorEfos: EfosStatus::Unknown,
                receptorEfos: EfosStatus::Unknown,
                rawResponse: ['error' => $e->getMessage()],
            );
        }
    }

    /**
     * Build expression for SAT query.
     */
    private function buildExpression(CfdiData $cfdiData): Expression
    {
        return match ($cfdiData->version) {
            '4.0' => Expression::createFromCfdi40(
                uuid: $cfdiData->uuid,
                rfcEmisor: $cfdiData->emisor['rfc'],
                rfcReceptor: $cfdiData->receptor['rfc'],
                total: number_format($cfdiData->total, 2, '.', ''),
            ),
            '3.3' => Expression::createFromCfdi33(
                uuid: $cfdiData->uuid,
                rfcEmisor: $cfdiData->emisor['rfc'],
                rfcReceptor: $cfdiData->receptor['rfc'],
                total: number_format($cfdiData->total, 2, '.', ''),
            ),
            default => Expression::createFromCfdi40(
                uuid: $cfdiData->uuid,
                rfcEmisor: $cfdiData->emisor['rfc'],
                rfcReceptor: $cfdiData->receptor['rfc'],
                total: number_format($cfdiData->total, 2, '.', ''),
            ),
        };
    }
}
```

---

### Step 8: Create PDF Service (cfditopdf)
**Objective**: Generar PDF del CFDI
**File**: `app/Services/Cfdi/CfdiPdfService.php`

```php
<?php

declare(strict_types=1);

namespace App\Services\Cfdi;

use CfdiUtils\Cleaner\Cleaner;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PhpCfdi\CfdiToPdf\Converter;
use PhpCfdi\CfdiToPdf\PdfMaker\Html2PdfBuilder;

class CfdiPdfService
{
    private Converter $converter;

    public function __construct()
    {
        $this->converter = new Converter(new Html2PdfBuilder());
    }

    /**
     * Generate PDF from CFDI XML.
     *
     * @return string Path to generated PDF
     */
    public function generate(string $xmlContent, ?string $filename = null): string
    {
        // Clean XML
        $cleanXml = Cleaner::staticClean($xmlContent);

        // Generate filename if not provided
        $filename = $filename ?? Str::uuid() . '.pdf';

        // Get storage path
        $storagePath = config('cfdi.pdf.storage_path', 'expense-receipts/cfdi-pdf');
        $disk = config('cfdi.pdf.disk', 'private');

        // Create temp file
        $tempPath = storage_path('app/temp/' . $filename);

        // Ensure temp directory exists
        if (!is_dir(dirname($tempPath))) {
            mkdir(dirname($tempPath), 0755, true);
        }

        // Generate PDF
        $this->converter->createPdfAs($cleanXml, $tempPath);

        // Move to storage
        $finalPath = "{$storagePath}/{$filename}";
        Storage::disk($disk)->put($finalPath, file_get_contents($tempPath));

        // Clean temp file
        @unlink($tempPath);

        return $finalPath;
    }

    /**
     * Generate PDF and return as string (for download).
     */
    public function generateRaw(string $xmlContent): string
    {
        $cleanXml = Cleaner::staticClean($xmlContent);

        $tempPath = storage_path('app/temp/' . Str::uuid() . '.pdf');

        if (!is_dir(dirname($tempPath))) {
            mkdir(dirname($tempPath), 0755, true);
        }

        $this->converter->createPdfAs($cleanXml, $tempPath);

        $content = file_get_contents($tempPath);
        @unlink($tempPath);

        return $content;
    }
}
```

---

### Step 9: Create Main Facade Service
**Objective**: Servicio unificado que orquesta todos los servicios
**File**: `app/Services/Cfdi/CfdiService.php`

```php
<?php

declare(strict_types=1);

namespace App\Services\Cfdi;

use App\DTOs\Cfdi\CfdiData;
use App\DTOs\Cfdi\SatValidationResult;
use App\DTOs\Cfdi\ValidationResult;
use App\Enums\SatStatus;

class CfdiService
{
    public function __construct(
        private readonly CfdiParserService $parser,
        private readonly CfdiValidatorService $validator,
        private readonly SatValidatorService $satValidator,
        private readonly CfdiPdfService $pdfService,
    ) {}

    /**
     * Parse CFDI without validation.
     */
    public function parse(string $xmlContent): CfdiData
    {
        return $this->parser->parse($xmlContent);
    }

    /**
     * Validate CFDI structure (XSD, firma, etc).
     */
    public function validate(string $xmlContent): ValidationResult
    {
        return $this->validator->validate($xmlContent);
    }

    /**
     * Validate CFDI with SAT WebService.
     */
    public function validateWithSat(CfdiData $cfdiData): SatValidationResult
    {
        if (!config('cfdi.validation.sat_enabled', true)) {
            return new SatValidationResult(
                status: SatStatus::Pending,
                isVigente: false,
                isCancelado: false,
                cancellationStatus: null,
                emisorEfos: \App\Enums\EfosStatus::Unknown,
                receptorEfos: \App\Enums\EfosStatus::Unknown,
            );
        }

        return $this->satValidator->validate($cfdiData);
    }

    /**
     * Full validation: structure + SAT.
     */
    public function fullValidation(string $xmlContent): array
    {
        // Step 1: Structural validation
        $structuralResult = $this->validate($xmlContent);

        if (!$structuralResult->isValid) {
            return [
                'structural' => $structuralResult,
                'sat' => null,
                'isValid' => false,
            ];
        }

        // Step 2: SAT validation
        $satResult = $this->validateWithSat($structuralResult->data);

        return [
            'structural' => $structuralResult,
            'sat' => $satResult,
            'isValid' => $structuralResult->isValid && $satResult->isVigente,
            'hasEfosAlert' => $satResult->hasEfosAlert(),
        ];
    }

    /**
     * Generate PDF from CFDI.
     */
    public function generatePdf(string $xmlContent, ?string $filename = null): string
    {
        return $this->pdfService->generate($xmlContent, $filename);
    }

    /**
     * Quick structure check.
     */
    public function isValidStructure(string $xmlContent): bool
    {
        return $this->validator->isValidStructure($xmlContent);
    }
}
```

---

### Step 10: Create Background Job for SAT Validation
**Objective**: Validación asíncrona para no bloquear UI
**File**: `app/Jobs/ValidateCfdiWithSatJob.php`

```php
<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Events\CfdiValidated;
use App\Models\ExpenseReceipt;
use App\Services\Cfdi\CfdiService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ValidateCfdiWithSatJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60;

    public function __construct(
        public ExpenseReceipt $receipt
    ) {}

    public function handle(CfdiService $cfdiService): void
    {
        try {
            // Get XML content
            $xmlContent = Storage::disk('public')->get($this->receipt->xml_file_path);

            if (!$xmlContent) {
                Log::error('CFDI XML not found', ['receipt_id' => $this->receipt->id]);
                return;
            }

            // Run full validation
            $result = $cfdiService->fullValidation($xmlContent);

            // Update receipt with results
            $this->receipt->update([
                'validation_status' => $result['structural']->status->value,
                'validation_errors' => $result['structural']->errors,
                'validated_at' => now(),
                'sat_status' => $result['sat']?->status->value,
                'sat_cancellation_status' => $result['sat']?->cancellationStatus,
                'sat_validated' => true,
                'sat_validated_at' => now(),
                'sat_response' => $result['sat']?->rawResponse,
                'emisor_in_efos' => $result['sat']?->emisorEfos->isAlert() ?? false,
            ]);

            // Generate PDF if enabled
            if (config('cfdi.pdf.enabled', true) && $result['structural']->isValid) {
                $pdfPath = $cfdiService->generatePdf(
                    $xmlContent,
                    "cfdi-{$this->receipt->cfdi_uuid}.pdf"
                );

                $this->receipt->update([
                    'pdf_file_path' => $pdfPath,
                    'pdf_generated_at' => now(),
                ]);
            }

            // Dispatch event
            event(new CfdiValidated($this->receipt, $result));

        } catch (\Throwable $e) {
            Log::error('CFDI SAT Validation Job Failed', [
                'receipt_id' => $this->receipt->id,
                'error' => $e->getMessage(),
            ]);

            $this->receipt->update([
                'sat_status' => 'error',
                'sat_response' => ['error' => $e->getMessage()],
            ]);
        }
    }
}
```

---

### Step 11: Update ExpenseReceipt Model
**Objective**: Agregar campos y métodos de validación
**File**: `app/Models/ExpenseReceipt.php` (modificar)

```php
// Agregar a $fillable
'validation_status',
'validation_errors',
'validated_at',
'sat_status',
'sat_cancellation_status',
'sat_validated',
'sat_validated_at',
'sat_response',
'emisor_in_efos',
'receptor_in_efos',
'pdf_file_path',
'pdf_generated_at',

// Agregar a $casts
'validation_errors' => 'array',
'validated_at' => 'datetime',
'sat_validated' => 'boolean',
'sat_validated_at' => 'datetime',
'sat_response' => 'array',
'emisor_in_efos' => 'boolean',
'receptor_in_efos' => 'boolean',
'pdf_generated_at' => 'datetime',

// Agregar métodos
public function isValidated(): bool
{
    return $this->validation_status === 'valid';
}

public function isSatValidated(): bool
{
    return $this->sat_validated && $this->sat_status === 'vigente';
}

public function hasEfosAlert(): bool
{
    return $this->emisor_in_efos || $this->receptor_in_efos;
}

public function canBeUsedForDeduction(): bool
{
    return $this->isValidated()
        && $this->isSatValidated()
        && !$this->hasEfosAlert();
}
```

---

### Step 12: Integrate with Filament Form
**Objective**: Mostrar validación y alertas en el formulario
**File**: Modificar `ExpenseVerificationForm.php`

```php
// Después de FileUpload del XML, agregar:

Placeholder::make('validation_status_display')
    ->label('Estado de Validación')
    ->content(function ($record, $state) {
        if (!$record?->cfdi_uuid) {
            return 'Pendiente de carga';
        }

        $badges = [];

        // Structural validation
        $structuralColor = match ($record->validation_status) {
            'valid' => 'success',
            'invalid' => 'danger',
            default => 'gray',
        };
        $badges[] = "<span class='badge badge-{$structuralColor}'>Estructura: {$record->validation_status}</span>";

        // SAT status
        if ($record->sat_validated) {
            $satColor = match ($record->sat_status) {
                'vigente' => 'success',
                'cancelado' => 'danger',
                default => 'warning',
            };
            $badges[] = "<span class='badge badge-{$satColor}'>SAT: {$record->sat_status}</span>";
        }

        // EFOS alert
        if ($record->emisor_in_efos) {
            $badges[] = "<span class='badge badge-danger'>⚠️ EFOS</span>";
        }

        return new HtmlString(implode(' ', $badges));
    })
    ->visible(fn ($record) => $record?->cfdi_uuid),
```

---

### Step 13: Create Database Migration
**File**: `database/migrations/XXXX_add_sat_validation_fields_to_expense_receipts_table.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('expense_receipts', function (Blueprint $table) {
            // Structural validation
            $table->string('validation_status')->default('pending')->after('status');
            $table->json('validation_errors')->nullable()->after('validation_status');
            $table->timestamp('validated_at')->nullable()->after('validation_errors');

            // SAT validation
            $table->string('sat_status')->nullable()->after('validated_at');
            $table->string('sat_cancellation_status')->nullable()->after('sat_status');
            $table->boolean('sat_validated')->default(false)->after('sat_cancellation_status');
            $table->timestamp('sat_validated_at')->nullable()->after('sat_validated');
            $table->json('sat_response')->nullable()->after('sat_validated_at');

            // EFOS check
            $table->boolean('emisor_in_efos')->default(false)->after('sat_response');
            $table->boolean('receptor_in_efos')->default(false)->after('emisor_in_efos');

            // PDF generation
            $table->string('pdf_file_path')->nullable()->after('receptor_in_efos');
            $table->timestamp('pdf_generated_at')->nullable()->after('pdf_file_path');

            // Indexes
            $table->index(['validation_status', 'sat_status']);
            $table->index('emisor_in_efos');
        });
    }

    public function down(): void
    {
        Schema::table('expense_receipts', function (Blueprint $table) {
            $table->dropIndex(['validation_status', 'sat_status']);
            $table->dropIndex(['emisor_in_efos']);

            $table->dropColumn([
                'validation_status',
                'validation_errors',
                'validated_at',
                'sat_status',
                'sat_cancellation_status',
                'sat_validated',
                'sat_validated_at',
                'sat_response',
                'emisor_in_efos',
                'receptor_in_efos',
                'pdf_file_path',
                'pdf_generated_at',
            ]);
        });
    }
};
```

---

### Step 14: Register Services
**File**: `app/Providers/AppServiceProvider.php`

```php
// En el método register()
$this->app->singleton(\App\Services\Cfdi\CfdiParserService::class);
$this->app->singleton(\App\Services\Cfdi\CfdiValidatorService::class);
$this->app->singleton(\App\Services\Cfdi\SatValidatorService::class);
$this->app->singleton(\App\Services\Cfdi\CfdiPdfService::class);
$this->app->singleton(\App\Services\Cfdi\CfdiService::class);
```

---

### Step 15: Write Tests
**Objective**: Cobertura completa

**Files**:
- `tests/Unit/Services/CfdiParserServiceTest.php`
- `tests/Unit/Services/CfdiValidatorServiceTest.php`
- `tests/Unit/Services/SatValidatorServiceTest.php`
- `tests/Feature/CfdiValidationFlowTest.php`

---

## Testing Requirements

### Unit Tests
| Test File | Test Cases |
|-----------|------------|
| `CfdiParserServiceTest.php` | - test_parses_cfdi_40<br>- test_parses_cfdi_33<br>- test_throws_on_invalid_xml<br>- test_throws_on_missing_uuid |
| `CfdiValidatorServiceTest.php` | - test_validates_valid_cfdi<br>- test_detects_invalid_structure<br>- test_detects_invalid_signature<br>- test_returns_errors_array |
| `SatValidatorServiceTest.php` | - test_validates_vigente_cfdi<br>- test_detects_cancelled_cfdi<br>- test_detects_efos_emisor<br>- test_caches_results<br>- test_handles_sat_timeout |

### Feature Tests
| Test File | Test Cases |
|-----------|------------|
| `CfdiValidationFlowTest.php` | - test_full_validation_flow<br>- test_form_shows_validation_status<br>- test_job_updates_receipt<br>- test_pdf_is_generated |

---

## Documentation Requirements

- [ ] Actualizar `docs/modules/expense-verification.md`
- [ ] Crear `docs/services/cfdi-validation.md`
- [ ] Documentar configuración en `config/cfdi.php`

---

## Git Commits Planned

1. `chore(deps): add cfdiutils, sat-estado-cfdi and cfditopdf packages`
2. `feat(cfdi): add configuration file and enums`
3. `feat(cfdi): add DTOs for validation results`
4. `feat(cfdi): add parser service using cfdiutils`
5. `feat(cfdi): add structural validator service`
6. `feat(cfdi): add SAT validator service with EFOS check`
7. `feat(cfdi): add PDF generation service`
8. `feat(cfdi): add main CfdiService facade`
9. `feat(cfdi): add background job for SAT validation`
10. `feat(cfdi): add migration for validation fields`
11. `feat(cfdi): integrate validation with Filament form`
12. `test(cfdi): add unit and feature tests`
13. `docs(cfdi): add validation system documentation`

---

## Risk Assessment

| Risk | Mitigation |
|------|------------|
| SAT WebService caído | Cache + reintentos + graceful degradation |
| XML con namespaces inválidos | cfdiutils Cleaner los limpia automáticamente |
| Alto volumen de consultas SAT | Cache de 1 hora + Job asíncrono |
| Incompatibilidad de versiones | Probado en CI con PHP 8.3 |

---

## Approval Request

El plan cubre:
- ✅ Reemplazo del parser manual con cfdiutils
- ✅ Validación estructural completa (XSD, firma, sello)
- ✅ Consulta de estatus SAT
- ✅ Verificación EFOS (lista negra)
- ✅ Generación de PDF
- ✅ Integración con Filament (badges, alertas)
- ✅ Validación asíncrona (Job)
- ✅ Tests completos
- ✅ Documentación

**Estimated complexity**: High
**Estimated steps**: 15 pasos de implementación

---

⏳ **Awaiting user approval to proceed**
