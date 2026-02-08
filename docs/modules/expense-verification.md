# Expense Verification Module

## Overview

The Expense Verification Module is a comprehensive system for managing and verifying travel expenses in the admin panel. It supports Mexican fiscal receipts (CFDI) parsing, an 8-state workflow, and complete audit trail functionality.

## Features

- **CFDI XML Processing**: Automatic parsing of Mexican fiscal receipts (versions 3.3 and 4.0)
- **8-State Workflow**: Complete workflow from draft to closure with state machine management
- **Reimbursement Tracking**: Automatic detection of reimbursement needs
- **Native Export**: Export verifications to XLSX/CSV using Filament's native capabilities
- **Policy-Based Authorization**: Role-based access control for all operations
- **Audit Trail**: Complete history of all actions with Spatie Activity Log

## Architecture

### Models

#### ExpenseVerification

Main model representing an expense verification record.

```php
// Location: app/Models/ExpenseVerification.php

// Key relationships
$verification->travelRequest    // BelongsTo TravelRequest
$verification->receipts         // HasMany ExpenseReceipt
$verification->fiscalReceipts   // HasMany ExpenseReceipt (filtered)
$verification->nonDeductibleReceipts // HasMany ExpenseReceipt (filtered)
$verification->creator          // BelongsTo User
$verification->approver         // BelongsTo User
```

#### ExpenseReceipt

Represents individual receipts (fiscal or non-deductible).

```php
// Location: app/Models/ExpenseReceipt.php

// Types
ExpenseReceipt::TYPE_FISCAL = 'fiscal'          // CFDI receipts
ExpenseReceipt::TYPE_NON_DEDUCTIBLE = 'non_deductible'  // Regular receipts
```

### State Machine

The module uses Spatie Model States for workflow management.

```
States:
- Draft: Initial state, editable
- PendingReview: Submitted for review
- Approved: Approved by travel team
- Rejected: Rejected, can be revised
- Revision: Being revised after rejection
- NeedsHighAuth: Escalated for high authority approval
- HighAuthApproved: Approved by high authority
- Closed: Final state, can be reopened
```

#### State Transitions

```
Draft -> PendingReview (submit)
PendingReview -> Approved (approve)
PendingReview -> Rejected (reject)
PendingReview -> NeedsHighAuth (escalate)
Rejected -> Revision (revise)
Revision -> PendingReview (resubmit)
NeedsHighAuth -> HighAuthApproved (high auth approve)
Approved -> Closed (close)
HighAuthApproved -> Closed (close)
Closed -> Draft (reopen)
```

### Services

#### CfdiParserService

Parses CFDI XML files and extracts relevant data.

```php
use App\Services\CfdiParserService;

$parser = new CfdiParserService();

// Parse XML content
$data = $parser->parse($xmlContent);

// Parse from storage path
$data = $parser->parseFromPath('cfdi/file.xml', 'local');

// Extract summary only
$summary = $parser->extractSummary($xmlContent);

// Validate structure
$isValid = $parser->isValidStructure($xmlContent);
```

**Returned Data Structure:**
```php
[
    'version' => '4.0',
    'uuid' => 'ABCD1234-5678-90AB-CDEF-123456789ABC',
    'fecha' => '2024-01-15T10:30:00',
    'total' => 1500.00,
    'moneda' => 'MXN',
    'emisor' => [
        'rfc' => 'ABC123456789',
        'nombre' => 'Empresa Proveedora SA de CV',
        'regimen_fiscal' => '601',
    ],
    'receptor' => [...],
    'conceptos' => [...],
    'impuestos' => [...],
]
```

### Events

The module emits events for key workflow actions:

- `ExpenseVerificationCreated`
- `ExpenseVerificationSubmitted`
- `ExpenseVerificationApproved`
- `ExpenseVerificationRejected`
- `ExpenseVerificationEscalated`
- `ExpenseVerificationClosed`
- `ExpenseVerificationReopened`
- `ExpenseVerificationReimbursementMade`

### Observer

`ExpenseVerificationObserver` handles model lifecycle events:

- Generates UUID on creation
- Sets creator from authenticated user
- Logs activity changes

## Filament Resource

### Location

```
app/Filament/Resources/ExpenseVerifications/
├── ExpenseVerificationResource.php
├── Schemas/
│   └── ExpenseVerificationForm.php
├── Tables/
│   └── ExpenseVerificationsTable.php
└── Pages/
    ├── ListExpenseVerifications.php
    └── ViewExpenseVerification.php
```

### Navigation

- **Group**: Operaciones
- **Label**: Comprobaciones de Gastos
- **Icon**: heroicon-o-document-check
- **Badge**: Shows count of active verifications

### Table Features

1. **Columns**: Folio, Travel Request, Status, Total Verified, Reimbursement Status, Created At
2. **Filters**: By status, by reimbursement status, by creator
3. **Actions**: View, Edit, Delete, Export
4. **Bulk Actions**: Export, Delete

### Form Features

1. **Travel Request Selection**: Filtered to only show requests pending verification
2. **Non-Deductible Receipts**: Repeater with photo upload
3. **Fiscal Receipts (CFDI)**: Repeater with XML parsing and auto-populate

### View Page

The view page displays:
- General information section
- Travel request details
- Financial summary (advance vs verified amounts)
- Receipts list (fiscal and non-deductible)
- Activity history timeline
- Workflow actions in header

## Testing

### Test Files

```
tests/
├── Unit/
│   └── CfdiParserServiceTest.php (7 tests)
└── Feature/
    ├── ExpenseVerificationTest.php (16 tests)
    └── ExpenseVerificationWorkflowTest.php (40 tests)
```

### Running Tests

```bash
# Run all module tests
php artisan test tests/Feature/ExpenseVerificationTest.php \
    tests/Feature/ExpenseVerificationWorkflowTest.php \
    tests/Unit/CfdiParserServiceTest.php

# Run specific test file
php artisan test tests/Feature/ExpenseVerificationWorkflowTest.php
```

### Test Coverage

- **State Transitions**: All valid and invalid transitions
- **Business Methods**: Submit, approve, reject, escalate, close, reopen, archive
- **Reimbursement Logic**: Detection and marking
- **Permission Checks**: Role-based access control
- **Audit Trail**: Logging of all actions
- **CFDI Parsing**: Valid XML, invalid XML, missing UUID, tax handling

## Database Schema

### expense_verifications

```sql
CREATE TABLE expense_verifications (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    uuid CHAR(36) UNIQUE NOT NULL,
    travel_request_id BIGINT UNSIGNED NOT NULL,
    created_by BIGINT UNSIGNED NOT NULL,
    status VARCHAR(255) DEFAULT 'draft',

    -- Review/Approval
    submitted_at TIMESTAMP NULL,
    approved_at TIMESTAMP NULL,
    rejected_at TIMESTAMP NULL,
    approved_by BIGINT UNSIGNED NULL,
    approval_notes TEXT NULL,

    -- Escalation
    escalated_at TIMESTAMP NULL,
    escalated_by BIGINT UNSIGNED NULL,
    escalation_notes TEXT NULL,

    -- High Authority
    high_auth_approved_at TIMESTAMP NULL,
    high_auth_approved_by BIGINT UNSIGNED NULL,
    high_auth_notes TEXT NULL,

    -- Closure
    closed_at TIMESTAMP NULL,
    closed_by BIGINT UNSIGNED NULL,
    closure_notes TEXT NULL,

    -- Reimbursement
    reimbursement_status VARCHAR(255) NULL,
    reimbursement_made BOOLEAN DEFAULT FALSE,
    reimbursement_made_at TIMESTAMP NULL,
    reimbursement_made_by BIGINT UNSIGNED NULL,
    reimbursement_amount DECIMAL(12,2) NULL,
    reimbursement_notes TEXT NULL,
    reimbursement_attachments JSON NULL,

    -- Reopening
    is_reopened BOOLEAN DEFAULT FALSE,
    reopened_at TIMESTAMP NULL,
    reopened_by BIGINT UNSIGNED NULL,
    reopening_reason TEXT NULL,

    -- Administrative
    administrative_notes TEXT NULL,
    audit_log JSON NULL,

    -- Archive
    is_archived BOOLEAN DEFAULT FALSE,
    archived_at TIMESTAMP NULL,
    archived_by BIGINT UNSIGNED NULL,

    -- Timestamps
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    deleted_at TIMESTAMP NULL
);
```

### expense_receipts

```sql
CREATE TABLE expense_receipts (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    expense_verification_id BIGINT UNSIGNED NOT NULL,
    receipt_type ENUM('fiscal', 'non_deductible') NOT NULL,

    -- Amounts
    total_amount DECIMAL(12,2) NOT NULL,
    applied_amount DECIMAL(12,2) NOT NULL,
    currency VARCHAR(3) DEFAULT 'MXN',

    -- Supplier
    supplier_name VARCHAR(255) NULL,
    supplier_rfc VARCHAR(13) NULL,

    -- Receipt Details
    receipt_date DATE NULL,
    concept TEXT NULL,

    -- CFDI Specific
    cfdi_uuid CHAR(36) NULL,
    cfdi_version VARCHAR(5) NULL,
    cfdi_concepts JSON NULL,
    xml_file_path VARCHAR(255) NULL,

    -- Images
    receipt_images JSON NULL,

    -- Status
    status VARCHAR(50) DEFAULT 'pending',
    is_applicable BOOLEAN DEFAULT TRUE,

    -- Timestamps
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);
```

## Factories

### Creating Test Data

```php
// Create a verification in draft state
$verification = ExpenseVerification::factory()->draft()->create();

// Create with specific travel request
$verification = ExpenseVerification::factory()->create([
    'travel_request_id' => $travelRequest->id,
]);

// Create in different states
$pending = ExpenseVerification::factory()->pendingReview()->create();
$approved = ExpenseVerification::factory()->approved()->create();
$rejected = ExpenseVerification::factory()->rejected()->create();

// Create with reimbursement status
$needsReimbursement = ExpenseVerification::factory()
    ->pendingReimbursement()
    ->create();

// Create receipts
$fiscal = ExpenseReceipt::factory()
    ->fiscal()
    ->withAmount(1500)
    ->create(['expense_verification_id' => $verification->id]);

$nonDeductible = ExpenseReceipt::factory()
    ->nonDeductible()
    ->withAmount(500)
    ->create(['expense_verification_id' => $verification->id]);
```

## Authorization

### Policy Methods

```php
// ExpenseVerificationPolicy

viewAny(User $user)      // Can view list
view(User $user, ...)    // Can view specific record
create(User $user)       // Can create new
update(User $user, ...)  // Can edit (checks state)
delete(User $user, ...)  // Can delete
approve(User $user, ...) // Can approve (travel team only)
reject(User $user, ...)  // Can reject (travel team only)
escalate(User $user, ...) // Can escalate
highAuthApprove(User $user, ...) // Can approve with high auth
markReimbursement(User $user, ...) // Can mark reimbursement (treasury only)
reopen(User $user, ...)  // Can reopen
archive(User $user, ...) // Can archive
```

### Role Requirements

| Action | Required Role |
|--------|---------------|
| View | Any authenticated user |
| Create | Any authenticated user |
| Edit | Creator only (in draft/revision) |
| Approve/Reject | Travel Team |
| High Auth Approve | Special Authorization |
| Mark Reimbursement | Treasury Team |
| Reopen | Travel Team or Treasury Team |
| Archive | Travel Team or Treasury Team |

## Blade Components

### expense-verification-summary

Displays financial summary table.

```blade
<x-filament.expense-verification-summary :record="$verification" />
```

### travel-request-info

Displays travel request details card.

```blade
<x-filament.travel-request-info :record="$verification->travelRequest" />
```
