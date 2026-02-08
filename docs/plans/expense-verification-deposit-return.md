# Implementation Plan: Comprobante de Depósito de Devolución + Notificaciones Email

**Date**: 2026-02-04
**Planner**: Agent 1 (Opus 4.5)
**Branch**: `feature/expense-verification-deposit-return`
**Status**: Awaiting Approval

## Summary

Implementar una nueva sección en el formulario de comprobación de gastos para capturar el comprobante de depósito cuando el empleado debe devolver dinero sobrante del anticipo. Además, crear sistema completo de notificaciones por correo electrónico para todos los eventos del workflow. Ajustar la lógica de cierre según el flujo de negocio correcto.

## Flujo de Negocio (Clarificado)

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                           FLUJO DE COMPROBACIÓN                              │
├─────────────────────────────────────────────────────────────────────────────┤
│                                                                              │
│  1. USUARIO carga comprobantes                                               │
│     └── Si hay sobrante del anticipo → sube comprobante de depósito         │
│     └── Envía a revisión                                                     │
│              │                                                               │
│              ▼                                                               │
│  2. EQUIPO VIAJES revisa                                                     │
│     ├── APRUEBA:                                                             │
│     │   ├── Sin reembolso pendiente → CERRADA ✅                            │
│     │   └── Con reembolso pendiente → "Aprobada - Esperando Reembolso"      │
│     │                                      │                                 │
│     │                                      ▼                                 │
│     │                          TESORERÍA carga reembolso → CERRADA ✅       │
│     │                                                                        │
│     ├── RECHAZA → vuelve al USUARIO con motivo                              │
│     │                                                                        │
│     └── ESCALA → elige entidad superior (configurada por Tesorería)         │
│              │                                                               │
│              ▼                                                               │
│  3. ENTIDAD SUPERIOR revisa                                                  │
│     ├── APRUEBA con motivo → vuelve a EQUIPO VIAJES (para decisión final)   │
│     └── RECHAZA con motivo → vuelve al USUARIO (para corregir)              │
│                                                                              │
└─────────────────────────────────────────────────────────────────────────────┘
```

## Requirements Confirmed

- [x] Sección de comprobante de depósito visible cuando `getReturnAmount() > 0`
- [x] Usuario sube el comprobante de devolución AL MOMENTO de cargar sus comprobantes
- [x] Campos requeridos: Archivo PDF/imagen, monto depositado, fecha de depósito
- [x] Notificaciones email según el flujo:
  - Usuario envía → Equipo Viajes
  - Viajes aprueba (sin reembolso) → Usuario (cierre)
  - Viajes aprueba (con reembolso) → Usuario + Tesorería
  - Viajes rechaza → Usuario (con motivo)
  - Viajes escala → Entidad Superior elegida
  - Entidad Superior decide → Equipo Viajes (con motivo, vuelve a revisión)
  - Tesorería hace reembolso → Usuario (cierre definitivo)
- [x] Cierre automático cuando: aprobada + sin reembolsos pendientes
- [x] Cierre por tesorería cuando: aprobada + reembolso completado

---

## Technology Stack

- **Language**: PHP 8.3
- **Framework**: Laravel 12 + Filament v4
- **Email**: Laravel Mail con templates Blade
- **Queue**: Laravel Queue para envío asíncrono
- **Testing**: Pest v4

---

## Files to Create/Modify

### New Files

| File Path | Purpose |
|-----------|---------|
| `app/Notifications/ExpenseVerificationNotification.php` | Notificación genérica para todos los eventos |
| `app/Listeners/ExpenseVerification/SendNotificationOnSubmitted.php` | Usuario envía → notifica Equipo Viajes |
| `app/Listeners/ExpenseVerification/SendNotificationOnApproved.php` | Viajes aprueba → notifica Usuario (+Tesorería si hay reembolso) |
| `app/Listeners/ExpenseVerification/SendNotificationOnRejected.php` | Viajes rechaza → notifica Usuario |
| `app/Listeners/ExpenseVerification/SendNotificationOnEscalated.php` | Viajes escala → notifica Entidad Superior elegida |
| `app/Listeners/ExpenseVerification/SendNotificationOnHighAuthApproved.php` | Entidad Superior aprueba → notifica Equipo Viajes |
| `app/Listeners/ExpenseVerification/SendNotificationOnHighAuthRejected.php` | Entidad Superior rechaza → notifica Usuario |
| `app/Listeners/ExpenseVerification/SendNotificationOnClosed.php` | Comprobación cerrada → notifica Usuario |
| `app/Listeners/ExpenseVerification/SendNotificationOnReimbursementMade.php` | Tesorería hace reembolso → notifica Usuario |
| `resources/views/mail/expense-verification/notification.blade.php` | Template de correo |
| `database/migrations/XXXX_add_return_deposit_fields_to_expense_verifications_table.php` | Migración para campos de devolución |
| `tests/Feature/ExpenseVerificationNotificationTest.php` | Tests de notificaciones |
| `tests/Feature/ExpenseVerificationDepositReturnTest.php` | Tests del comprobante de devolución |

### Modified Files

| File Path | Changes |
|-----------|---------|
| `app/Models/ExpenseVerification.php` | Agregar campos y métodos para comprobante de devolución |
| `app/Filament/Resources/ExpenseVerifications/Schemas/ExpenseVerificationForm.php` | Nueva sección de comprobante de devolución |
| `app/Providers/EventServiceProvider.php` | Registrar listeners para eventos |
| `app/Observers/ExpenseVerificationObserver.php` | Disparar eventos en cambios de estado |
| `app/States/ExpenseVerification/Approved.php` | Validar condiciones de cierre |
| `app/States/ExpenseVerification/HighAuthApproved.php` | Validar condiciones de cierre |
| `docs/modules/expense-verification.md` | Documentar nuevas funcionalidades |

---

## Database Changes

### Migration: Add Return Deposit Fields

```php
Schema::table('expense_verifications', function (Blueprint $table) {
    // Return deposit fields (when employee returns surplus)
    $table->boolean('return_deposit_required')->default(false)->after('reimbursement_attachments');
    $table->boolean('return_deposit_made')->default(false)->after('return_deposit_required');
    $table->decimal('return_deposit_amount', 12, 2)->nullable()->after('return_deposit_made');
    $table->date('return_deposit_date')->nullable()->after('return_deposit_amount');
    $table->string('return_deposit_file_path')->nullable()->after('return_deposit_date');
    $table->text('return_deposit_notes')->nullable()->after('return_deposit_file_path');
    $table->timestamp('return_deposit_verified_at')->nullable()->after('return_deposit_notes');
    $table->foreignId('return_deposit_verified_by')->nullable()->after('return_deposit_verified_at');
});
```

---

## Step-by-Step Implementation

### Step 1: Database Migration
**Objective**: Agregar campos para comprobante de devolución
**Files**: Nueva migración
**Actions**:
1. Crear migración con `php artisan make:migration add_return_deposit_fields_to_expense_verifications_table --table=expense_verifications`
2. Agregar campos: `return_deposit_required`, `return_deposit_made`, `return_deposit_amount`, `return_deposit_date`, `return_deposit_file_path`, `return_deposit_notes`, `return_deposit_verified_at`, `return_deposit_verified_by`
3. Ejecutar migración `php artisan migrate`

### Step 2: Update ExpenseVerification Model
**Objective**: Agregar campos y métodos al modelo
**Files**: `app/Models/ExpenseVerification.php`
**Actions**:
1. Agregar nuevos campos a `$fillable`
2. Agregar casts para los nuevos campos
3. Agregar relación `returnDepositVerifiedByUser()`
4. Agregar método `needsReturnDeposit(): bool`
5. Agregar método `hasReturnDepositCompleted(): bool`
6. Agregar método `markReturnDepositMade()`
7. Agregar método `verifyReturnDeposit()`
8. Modificar método `canBeClosed(): bool` para validar pendientes financieros
9. Agregar constantes `RETURN_DEPOSIT_PENDING` y `RETURN_DEPOSIT_COMPLETED`

### Step 3: Create Form Section for Return Deposit
**Objective**: Nueva sección en formulario Filament para que el USUARIO suba su comprobante
**Files**: `app/Filament/Resources/ExpenseVerifications/Schemas/ExpenseVerificationForm.php`
**Actions**:
1. Agregar nueva `Section::make('Comprobante de Devolución de Anticipo')` después de la sección CFDI
2. Descripción: "Si el total de gastos es menor al anticipo recibido, debes devolver la diferencia y subir el comprobante"
3. Hacer la sección condicional con `->visible()` que calcule dinámicamente si hay sobrante:
   - Usar `->live()` en los campos de recibos para recalcular
   - Mostrar cuando suma de `applied_amount` < anticipo del travel request
4. Agregar campos:
   - `Placeholder` mostrando el monto a devolver (calculado dinámicamente)
   - `FileUpload` para el comprobante (`return_deposit_file_path`) - requerido si hay sobrante
   - `TextInput` para monto depositado (`return_deposit_amount`) - requerido, mínimo = monto a devolver
   - `DatePicker` para fecha de depósito (`return_deposit_date`) - requerido
   - `Textarea` para notas opcionales (`return_deposit_notes`)
5. Agregar validación:
   - Si hay sobrante y no hay comprobante → error al enviar
   - Monto depositado debe ser >= monto a devolver

### Step 4: Create Notification Class
**Objective**: Crear notificación genérica para email y database
**Files**: `app/Notifications/ExpenseVerificationNotification.php`
**Actions**:
1. Crear con `php artisan make:notification ExpenseVerificationNotification`
2. Implementar canales: `mail` y `database`
3. Crear método `toMail()` con template dinámico según tipo de evento
4. Crear método `toDatabase()` para notificaciones in-app
5. Definir constantes para tipos: `SUBMITTED`, `APPROVED`, `REJECTED`, `ESCALATED`, `HIGH_AUTH_APPROVED`, `CLOSED`, `REIMBURSEMENT_MADE`, `RETURN_DEPOSIT_REQUIRED`

### Step 5: Create Email Template
**Objective**: Template de correo profesional
**Files**: `resources/views/mail/expense-verification/notification.blade.php`
**Actions**:
1. Crear template con componentes de Laravel Mail
2. Incluir: logo, título dinámico, folio, resumen financiero, botón de acción, footer
3. Soportar diferentes colores según tipo de evento (verde=aprobado, rojo=rechazado, etc.)

### Step 6: Create Event Listeners
**Objective**: Crear listeners para cada evento según flujo de negocio
**Files**: `app/Listeners/ExpenseVerification/*.php`
**Actions**:
1. Crear listener `SendNotificationOnSubmitted`:
   - Destinatario: Usuarios con rol `is_travel_team`
   - Contenido: Nueva comprobación pendiente de revisión
2. Crear listener `SendNotificationOnApproved`:
   - Destinatario: Usuario solicitante
   - Si `needsReimbursement()`: también notifica a usuarios con rol `is_treasury_team`
   - Si NO hay reembolso pendiente: marca como cerrada automáticamente
3. Crear listener `SendNotificationOnRejected`:
   - Destinatario: Usuario solicitante
   - Contenido: Incluye motivo del rechazo
4. Crear listener `SendNotificationOnEscalated`:
   - Destinatario: Entidad superior ELEGIDA por el usuario de viajes
   - Contenido: Solicitud de revisión especial con motivo
5. Crear listener `SendNotificationOnHighAuthApproved`:
   - Destinatario: Usuarios con rol `is_travel_team`
   - Contenido: Entidad superior APROBÓ con motivo
   - Estado vuelve a "Revisión por Viajes" para decisión final
6. Crear listener `SendNotificationOnHighAuthRejected`:
   - Destinatario: Usuario solicitante
   - Contenido: Entidad superior RECHAZÓ con motivo
   - Estado vuelve a "Borrador/Revisión" para que el usuario corrija
7. Crear listener `SendNotificationOnClosed`:
   - Destinatario: Usuario solicitante
   - Contenido: Comprobación cerrada exitosamente
8. Crear listener `SendNotificationOnReimbursementMade`:
   - Destinatario: Usuario solicitante
   - Contenido: Reembolso realizado, comprobación cerrada
   - Acción: Cierra la comprobación automáticamente

### Step 7: Register Events and Listeners
**Objective**: Conectar eventos con listeners
**Files**: `app/Providers/EventServiceProvider.php`
**Actions**:
1. Registrar mapeo de eventos a listeners en `$listen`
2. Agregar evento `ReturnDepositRequired` y su listener

### Step 8: Update Observer to Dispatch Events
**Objective**: Disparar eventos automáticamente en cambios de estado
**Files**: `app/Observers/ExpenseVerificationObserver.php`
**Actions**:
1. En método `updated()`, detectar cambios de estado y disparar evento correspondiente
2. Detectar cuando se marca reembolso hecho
3. Detectar cuando `getReturnAmount() > 0` después de aprobación para marcar `return_deposit_required`

### Step 9: Update Closure Logic
**Objective**: Implementar cierre automático y manual según flujo
**Files**: Estados, modelo y listeners
**Actions**:
1. **Cierre automático al aprobar** (sin reembolso pendiente):
   - En listener `SendNotificationOnApproved`, si `!needsReimbursement()`:
     - Transicionar estado a `Closed`
     - Disparar evento `Closed`
2. **Cierre manual por Tesorería** (con reembolso):
   - En acción de "Registrar Reembolso", después de guardar:
     - Transicionar estado a `Closed`
     - Disparar evento `ReimbursementMade` (que a su vez cierra)
3. **Validaciones de cierre** en `canBeClosed()`:
   - Estado debe ser `approved` o `high_auth_approved`
   - Si `needsReimbursement()` → `reimbursement_made` debe ser `true`
   - El comprobante de devolución ya fue validado al momento de enviar (usuario lo sube antes)
4. **Estado intermedio**: Agregar estado visual "Aprobada - Esperando Reembolso" cuando:
   - `status = approved` Y `needsReimbursement()` Y `!reimbursement_made`

### Step 10: Update View Page
**Objective**: Mostrar información del comprobante de devolución en vista
**Files**: `app/Filament/Resources/ExpenseVerifications/Pages/ViewExpenseVerification.php`
**Actions**:
1. Agregar infolist section para mostrar datos de devolución cuando aplique
2. Agregar acción para verificar el depósito de devolución (equipo tesorería)

### Step 11: Write Tests
**Objective**: Cobertura completa de nuevas funcionalidades
**Files**: Tests
**Actions**:
1. Test: sección visible solo cuando hay sobrante
2. Test: validación de monto mínimo en depósito
3. Test: notificación enviada en cada evento
4. Test: no se puede cerrar sin completar devolución
5. Test: no se puede cerrar sin completar reembolso
6. Test: flujo completo de devolución

---

## Testing Requirements

### Unit Tests
| Test File | Test Cases |
|-----------|------------|
| `tests/Unit/ExpenseVerificationReturnTest.php` | - test_needs_return_deposit_when_surplus<br>- test_no_return_deposit_when_no_surplus<br>- test_can_be_closed_validates_financial_status |

### Feature Tests
| Test File | Test Cases |
|-----------|------------|
| `tests/Feature/ExpenseVerificationDepositReturnTest.php` | - test_return_deposit_section_visible_when_surplus<br>- test_return_deposit_section_hidden_when_no_surplus<br>- test_cannot_submit_without_deposit_when_surplus<br>- test_deposit_amount_must_be_at_least_surplus |
| `tests/Feature/ExpenseVerificationNotificationTest.php` | - test_travel_team_notified_on_submit<br>- test_user_notified_on_approval<br>- test_treasury_notified_when_reimbursement_needed<br>- test_user_notified_on_rejection_with_reason<br>- test_selected_authority_notified_on_escalation<br>- test_travel_team_notified_on_high_auth_approval<br>- test_user_notified_on_high_auth_rejection<br>- test_user_notified_on_closure<br>- test_user_notified_on_reimbursement |
| `tests/Feature/ExpenseVerificationClosureTest.php` | - test_auto_closes_when_approved_without_reimbursement<br>- test_stays_open_when_approved_with_pending_reimbursement<br>- test_closes_after_reimbursement_made<br>- test_high_auth_approval_returns_to_travel_team<br>- test_high_auth_rejection_returns_to_user |

### Manual Testing Checklist

**Flujo 1: Sin reembolso pendiente (cierre automático)**
- [ ] Usuario crea comprobación con gastos = anticipo
- [ ] Usuario envía → verificar email a equipo viajes
- [ ] Viajes aprueba → verificar email a usuario + cierre automático

**Flujo 2: Con devolución de sobrante**
- [ ] Usuario crea comprobación con gastos < anticipo
- [ ] Verificar que sección de devolución aparece
- [ ] Intentar enviar sin comprobante → debe fallar
- [ ] Subir comprobante de depósito y enviar
- [ ] Viajes aprueba → verificar cierre automático

**Flujo 3: Con reembolso pendiente**
- [ ] Usuario crea comprobación con gastos > anticipo
- [ ] Viajes aprueba → verificar email a usuario + tesorería
- [ ] Verificar estado "Aprobada - Esperando Reembolso"
- [ ] Tesorería registra reembolso → verificar email + cierre

**Flujo 4: Escalamiento - Aprobación**
- [ ] Viajes escala a entidad superior elegida
- [ ] Verificar email a entidad superior
- [ ] Entidad superior APRUEBA con motivo
- [ ] Verificar email a equipo viajes (vuelve para decisión final)
- [ ] Viajes puede aprobar/rechazar

**Flujo 4b: Escalamiento - Rechazo**
- [ ] Viajes escala a entidad superior elegida
- [ ] Entidad superior RECHAZA con motivo
- [ ] Verificar email al USUARIO (para corregir)
- [ ] Usuario puede editar y reenviar

**Flujo 5: Rechazo**
- [ ] Viajes rechaza con motivo
- [ ] Verificar email a usuario con motivo
- [ ] Usuario puede editar y reenviar

---

## Documentation Requirements

### Technical Documentation
- [ ] Actualizar `docs/modules/expense-verification.md` con flujo de devolución
- [ ] Documentar nuevos campos de base de datos
- [ ] Documentar eventos y listeners

### User Documentation
- [ ] Guía: Cómo subir comprobante de devolución
- [ ] Guía: Entendiendo las notificaciones por email
- [ ] FAQ: ¿Por qué no puedo cerrar mi comprobación?

### Git Commits
Planned commits:
1. `feat(expense-verification): add return deposit database fields`
2. `feat(expense-verification): add return deposit form section`
3. `feat(expense-verification): add notification system for workflow events`
4. `feat(expense-verification): add closure validation for financial status`
5. `test(expense-verification): add tests for return deposit and notifications`
6. `docs(expense-verification): update module documentation`

---

## Risk Assessment

### Potential Risks

| Risk | Mitigation |
|------|------------|
| Emails no se envían en producción | Usar queue con retry, logging de fallos |
| Usuario sube archivo muy grande | Validación de tamaño máximo (5MB) |
| Cálculo incorrecto de monto a devolver | Tests unitarios extensivos del cálculo |
| Notificaciones duplicadas | Verificar que eventos se disparan una sola vez |

### Dependencies
- Configuración de servidor SMTP válida
- Queue worker corriendo para emails asíncronos
- Storage configurado para archivos

### Rollback Plan
1. Revertir migración: `php artisan migrate:rollback --step=1`
2. Quitar listeners de EventServiceProvider
3. Checkout al branch main

---

## Approval Request

El plan cubre:
- ✅ Sección de comprobante de devolución (usuario sube al crear/editar)
- ✅ Campos: archivo, monto, fecha (requeridos si hay sobrante)
- ✅ Notificaciones email según flujo de negocio:
  - Usuario envía → Viajes
  - Viajes aprueba → Usuario (+Tesorería si hay reembolso)
  - Viajes rechaza → Usuario
  - Viajes escala → Entidad superior elegida
  - Entidad superior APRUEBA → Viajes (vuelve a revisión)
  - Entidad superior RECHAZA → Usuario (para corregir)
  - Tesorería reembolsa → Usuario
- ✅ Cierre automático cuando aprobada sin reembolso pendiente
- ✅ Estado intermedio "Aprobada - Esperando Reembolso"
- ✅ Tests completos para todos los flujos
- ✅ Documentación

**Estimated complexity**: Medium-High
**Estimated steps**: 11 pasos de implementación

---

⏳ **Awaiting user approval to proceed to Developer Agent**

Please respond with:
- "approved" / "proceed" / "adelante" to continue
- Questions or modification requests
