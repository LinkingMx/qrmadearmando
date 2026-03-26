# Jerarquia Organizacional, Alcance de QR y Roles de Usuario

**Version:** 1.0.0
**Date:** 2026-02-08
**Branch:** `implement-shield`
**Status:** Implementado y testeado (103 tests passing)

---

## 1. Resumen General

Este documento describe la implementacion del sistema de jerarquia organizacional (Cadena > Marca > Sucursal), el control de alcance para QR Codes y la diferenciacion de tipos de usuario mediante roles. Sirve como guia tecnica para que otro agente realice pruebas end-to-end desde el navegador.

### 1.1 Componentes Implementados

| Componente | Descripcion | URLs Admin |
|---|---|---|
| **Cadenas** | Entidad raiz de la jerarquia | `/admin/chains` |
| **Marcas** | Agrupacion de sucursales bajo una cadena | `/admin/brands` |
| **Sucursales** | Punto fisico, pertenece a una marca | `/admin/branches` |
| **QR Codes** | Tarjetas con alcance configurable | `/admin/gift-cards` |
| **Usuarios** | Empleados y Terminales de sucursal | `/admin/users` |
| **Scanner** | Interfaz de cobro (solo BranchTerminal) | `/scanner` |

---

## 2. Arquitectura de Datos

### 2.1 Jerarquia Organizacional

```
Chain (Cadena)
  └── Brand (Marca)
        └── Branch (Sucursal)
              └── User (Usuario asignado)
```

**Relaciones:**
- `Chain` hasMany `Brand` (una cadena tiene muchas marcas)
- `Brand` belongsTo `Chain` + hasMany `Branch` (una marca pertenece a una cadena, tiene muchas sucursales)
- `Branch` belongsTo `Brand` (una sucursal pertenece a una marca)

### 2.2 Tablas de Base de Datos

| Tabla | Campos Clave | Restricciones |
|---|---|---|
| `chains` | `id`, `name` | `name` UNIQUE |
| `brands` | `id`, `chain_id`, `name` | `(chain_id, name)` UNIQUE, FK a chains |
| `branches` | `id`, `brand_id`, `name` | `brand_id` NOT NULL, FK a brands |
| `gift_cards` | `scope`, `chain_id`, `brand_id` | Campos de alcance agregados |
| `branch_gift_card` | `branch_id`, `gift_card_id` | Tabla pivot para scope=branch |

### 2.3 Datos Iniciales (Migracion)

La migracion crea automaticamente:
- **Cadena:** "Cadenas Don Carlos" (id=1)
- **Marca:** "Mochomos" (id=1, bajo cadena "Cadenas Don Carlos")
- Todas las sucursales existentes se asignan a la marca "Mochomos"
- Todos los QR codes existentes se actualizan a `scope=chain`, `chain_id=1`

---

## 3. Sistema de Alcance de QR Codes

### 3.1 Enum `GiftCardScope`

| Valor | Etiqueta en UI | Color Badge | Descripcion |
|---|---|---|---|
| `chain` | Cadena | `success` (verde) | QR usable en TODAS las sucursales de la cadena |
| `brand` | Marca | `warning` (amarillo) | QR usable en sucursales de una marca especifica |
| `branch` | Sucursal | `primary` (azul) | QR usable solo en sucursales especificas (many-to-many) |

### 3.2 Campos Condicionales por Scope

| Scope | Campo Visible | Obligatorio |
|---|---|---|
| `chain` | Select "Cadena" (`chain_id`) | Si |
| `brand` | Select "Marca" (`brand_id`) | Si |
| `branch` | Multi-Select "Sucursales" (pivot `branch_gift_card`) | Si |

### 3.3 Logica de Validacion (`canBeUsedAtBranch`)

```
scope=chain  -> gift_card.chain_id === branch.brand.chain_id
scope=brand  -> gift_card.brand_id === branch.brand_id
scope=branch -> gift_card.branches contiene branch.id (pivot table)
```

**Archivo:** `app/Models/GiftCard.php:117-126`

### 3.4 Doble Validacion (Defensa en Profundidad)

1. **ScannerController** (`app/Http/Controllers/ScannerController.php:110-122`): Retorna JSON 422 con mensaje en espanol
2. **TransactionService** (`app/Services/TransactionService.php:50-51`): Lanza `InvalidArgumentException`

---

## 4. Sistema de Roles

### 4.1 Roles Disponibles

| Rol | Descripcion | Acceso Scanner | Acceso Admin |
|---|---|---|---|
| `super_admin` | Administrador total | No directamente | Si, total |
| `Employee` | Empleado humano | No | Limitado |
| `BranchTerminal` | Terminal/punto de venta | Si | Limitado |

### 4.2 Middleware `RequiresBranch`

**Archivo:** `app/Http/Middleware/RequiresBranch.php`
**Alias:** `has.branch`

Validaciones en orden:
1. Usuario autenticado (si no, redirect a `/login`)
2. Usuario tiene `branch_id` asignado (si no, redirect a `/dashboard` con error)
3. Usuario tiene rol `BranchTerminal` (si no, redirect a `/dashboard` con error)

### 4.3 Rutas del Scanner

| Metodo | Ruta | Middleware | Descripcion |
|---|---|---|---|
| GET | `/scanner` | `auth`, `verified`, `has.branch` | Pagina del scanner |
| POST | `/api/scanner/lookup` | `auth`, `verified`, `has.branch` | Buscar QR por legacy_id o UUID |
| POST | `/api/scanner/process-debit` | `auth`, `verified`, `has.branch` | Procesar cobro/debito |
| GET | `/api/scanner/branch-transactions` | `auth`, `verified`, `has.branch` | Historial de transacciones |

---

## 5. Guia de Pruebas desde Navegador

### 5.1 Prerequisitos

- Aplicacion corriendo en `http://qrmadearmando.test`
- Admin panel en `http://qrmadearmando.test/admin`
- Usuario admin con acceso al panel (super_admin)

### 5.2 Prueba 1: Verificar Jerarquia Existente

**Objetivo:** Confirmar que la migracion creo los datos iniciales correctamente.

| Paso | Accion | URL | Resultado Esperado |
|---|---|---|---|
| 1 | Navegar a Cadenas | `/admin/chains` | Debe existir "Cadenas Don Carlos" con al menos 1 marca |
| 2 | Navegar a Marcas | `/admin/brands` | Debe existir "Mochomos" bajo cadena "Cadenas Don Carlos" |
| 3 | Navegar a Sucursales | `/admin/branches` | Todas las sucursales deben mostrar marca "Mochomos" |

### 5.3 Prueba 2: Crear Jerarquia Nueva

**Objetivo:** Crear una cadena completa con marcas y sucursales.

| Paso | Accion | URL | Datos | Resultado Esperado |
|---|---|---|---|---|
| 1 | Crear cadena | `/admin/chains/create` | Nombre: "Mi Empresa Test" | Redirect a `/admin/chains`, aparece en lista |
| 2 | Crear marca | `/admin/brands/create` | Cadena: "Mi Empresa Test", Nombre: "Don Carlos" | Redirect a `/admin/brands`, aparece en lista |
| 3 | Crear segunda marca | `/admin/brands/create` | Cadena: "Mi Empresa Test", Nombre: "La Vaca" | Aparece en lista con 0 sucursales |
| 4 | Crear sucursal | `/admin/branches/create` | Marca: "Don Carlos", Nombre: "Don Carlos Centro" | Redirect a `/admin/branches` |
| 5 | Crear segunda sucursal | `/admin/branches/create` | Marca: "Don Carlos", Nombre: "Don Carlos Norte" | Aparece con badge marca "Don Carlos" |
| 6 | Crear sucursal otra marca | `/admin/branches/create` | Marca: "La Vaca", Nombre: "La Vaca Sur" | Aparece con badge marca "La Vaca" |
| 7 | Verificar contadores | `/admin/brands` | - | "Don Carlos" muestra 2 sucursales, "La Vaca" muestra 1 |

### 5.4 Prueba 3: Proteccion de Eliminacion

**Objetivo:** Verificar que no se pueden eliminar entidades con dependencias.

| Paso | Accion | URL | Resultado Esperado |
|---|---|---|---|
| 1 | Ver cadena "Mi Empresa Test" | `/admin/chains` | El boton eliminar NO debe aparecer (tiene marcas) |
| 2 | Ver marca "Don Carlos" | `/admin/brands` | El boton eliminar NO debe aparecer (tiene sucursales) |
| 3 | Crear marca vacia | `/admin/brands/create` (Nombre: "Marca Temporal") | Se crea correctamente |
| 4 | Eliminar marca vacia | `/admin/brands` (click trash en "Marca Temporal") | Se elimina exitosamente con notificacion |

### 5.5 Prueba 4: QR Code con Scope Cadena

**Objetivo:** Crear un QR con alcance de cadena.

| Paso | Accion | URL | Datos | Resultado Esperado |
|---|---|---|---|---|
| 1 | Crear QR | `/admin/gift-cards/create` | Categoria: seleccionar cualquiera | Formulario muestra seccion "Alcance de Uso" |
| 2 | Seleccionar scope | Mismo formulario | Tipo de Alcance: "Cadena (Todas las sucursales)" | Aparece campo "Cadena" |
| 3 | Seleccionar cadena | Mismo formulario | Cadena: "Cadenas Don Carlos" | Campo seleccionado |
| 4 | Guardar | Click "Crear" | - | Redirect a lista, badge verde "Cadena" en tabla |

### 5.6 Prueba 5: QR Code con Scope Marca

**Objetivo:** Crear un QR con alcance de marca.

| Paso | Accion | URL | Datos | Resultado Esperado |
|---|---|---|---|---|
| 1 | Crear QR | `/admin/gift-cards/create` | Categoria: seleccionar cualquiera | Formulario carga |
| 2 | Cambiar scope | Mismo formulario | Tipo de Alcance: "Marca (Sucursales de la marca)" | Campo "Cadena" desaparece, aparece campo "Marca" |
| 3 | Seleccionar marca | Mismo formulario | Marca: "Don Carlos" | Campo seleccionado |
| 4 | Guardar | Click "Crear" | - | Badge amarillo "Marca" en tabla |

### 5.7 Prueba 6: QR Code con Scope Sucursal

**Objetivo:** Crear un QR con alcance de sucursal especifica.

| Paso | Accion | URL | Datos | Resultado Esperado |
|---|---|---|---|---|
| 1 | Crear QR | `/admin/gift-cards/create` | Categoria: seleccionar cualquiera | Formulario carga |
| 2 | Cambiar scope | Mismo formulario | Tipo de Alcance: "Sucursal (Especifica)" | Campo "Marca" desaparece, aparece multi-select "Sucursales" |
| 3 | Seleccionar sucursales | Mismo formulario | Sucursales: "Don Carlos Centro" y "Don Carlos Norte" | Dos opciones seleccionadas |
| 4 | Guardar | Click "Crear" | - | Badge azul "Sucursal" en tabla |

### 5.8 Prueba 7: Campos Condicionales (Reactividad)

**Objetivo:** Verificar que los campos cambian dinamicamente al cambiar scope.

| Paso | Accion | Resultado Esperado |
|---|---|---|
| 1 | En formulario QR, seleccionar scope "Cadena" | Solo aparece campo "Cadena" |
| 2 | Cambiar scope a "Marca" | Campo "Cadena" desaparece, aparece "Marca" |
| 3 | Cambiar scope a "Sucursal" | Campo "Marca" desaparece, aparece multi-select "Sucursales" |
| 4 | Volver a scope "Cadena" | Vuelve a mostrar solo "Cadena" |

### 5.9 Prueba 8: Filtros en Tabla de QR Codes

**Objetivo:** Verificar que los filtros funcionan correctamente.

| Paso | Accion | URL | Resultado Esperado |
|---|---|---|---|
| 1 | Navegar a QR Codes | `/admin/gift-cards` | Lista todos los QR codes |
| 2 | Filtrar por Alcance: "Cadena" | Click filtro "Alcance" > "Cadena" | Solo muestra QRs con badge verde "Cadena" |
| 3 | Filtrar por Cadena | Click filtro "Cadena" > seleccionar | Solo muestra QRs de esa cadena |
| 4 | Filtrar por Marca | Click filtro "Marca" > seleccionar | Solo muestra QRs de esa marca |
| 5 | Limpiar filtros | Click "Reset" o limpiar filtros | Muestra todos los QR codes |

### 5.10 Prueba 9: Crear Usuario BranchTerminal

**Objetivo:** Crear un usuario tipo terminal de sucursal con acceso al scanner.

| Paso | Accion | URL | Datos | Resultado Esperado |
|---|---|---|---|---|
| 1 | Crear usuario | `/admin/users/create` | Nombre: "Terminal Don Carlos Centro", Email: unico, Password: seguro | Formulario carga |
| 2 | Asignar sucursal | Mismo formulario | Sucursal: "Don Carlos Centro" | Seleccionada |
| 3 | Asignar rol | Mismo formulario | Roles: "BranchTerminal" | Helper text visible: "Employee: Empleado humano \| BranchTerminal: Terminal de sucursal (acceso a scanner)" |
| 4 | Activar usuario | Mismo formulario | Estado: Activo (toggle ON) | Toggle encendido |
| 5 | Guardar | Click "Crear" | - | Usuario creado exitosamente |

### 5.11 Prueba 10: Acceso al Scanner por Rol

**Objetivo:** Verificar que solo BranchTerminal puede acceder al scanner.

| Paso | Accion | URL | Resultado Esperado |
|---|---|---|---|
| 1 | Login como BranchTerminal | `/login` (credenciales del usuario terminal) | Accede al dashboard |
| 2 | Navegar a Scanner | `/scanner` | Pagina del scanner carga correctamente, muestra nombre de sucursal |
| 3 | Logout | - | Session cerrada |
| 4 | Login como Employee | `/login` (usuario con rol Employee) | Accede al dashboard |
| 5 | Navegar a Scanner | `/scanner` | **REDIRECT** a `/dashboard` con mensaje de error |
| 6 | Logout | - | Session cerrada |
| 7 | Login como usuario sin rol | `/login` (usuario sin roles) | Accede al dashboard |
| 8 | Navegar a Scanner | `/scanner` | **REDIRECT** a `/dashboard` con mensaje de error |
| 9 | Login como usuario sin sucursal | `/login` (BranchTerminal sin branch_id) | Accede al dashboard |
| 10 | Navegar a Scanner | `/scanner` | **REDIRECT** a `/dashboard` con mensaje de error |

### 5.12 Prueba 11: Scanner - Validacion de Scope (Cobro Permitido)

**Prerequisitos:**
- Login como usuario "Terminal Don Carlos Centro" (BranchTerminal, branch: "Don Carlos Centro")
- QR tipo Marca "Don Carlos" creado con saldo > 0 (agregar saldo via admin si es necesario)

| Paso | Accion | Resultado Esperado |
|---|---|---|
| 1 | Navegar a `/scanner` | Scanner carga, muestra sucursal "Don Carlos Centro" |
| 2 | Buscar QR tipo Marca "Don Carlos" (usar legacy_id) | QR encontrado, muestra informacion y saldo |
| 3 | Ingresar monto menor al saldo | Campo de monto acepta valor |
| 4 | Confirmar cobro | Transaccion exitosa, muestra folio, saldo actualizado |

### 5.13 Prueba 12: Scanner - Validacion de Scope (Cobro Rechazado)

**Prerequisitos:**
- Login como usuario "Terminal Don Carlos Centro" (BranchTerminal, branch: "Don Carlos Centro")
- QR tipo Marca "Mochomos" creado con saldo > 0

| Paso | Accion | Resultado Esperado |
|---|---|---|
| 1 | Navegar a `/scanner` | Scanner carga |
| 2 | Buscar QR tipo Marca "Mochomos" (el terminal es de "Don Carlos") | QR encontrado |
| 3 | Intentar cobrar | Error 422: "Este QR es tipo Marca y solo funciona en sucursales de la marca asignada." |

### 5.14 Prueba 13: Scanner - Scope Cadena (Cobro Universal)

**Prerequisitos:**
- Login como "Terminal Don Carlos Centro"
- QR tipo Cadena de "Cadenas Don Carlos" con saldo > 0

| Paso | Accion | Resultado Esperado |
|---|---|---|
| 1 | Buscar QR tipo Cadena | QR encontrado |
| 2 | Cobrar | Transaccion exitosa (cadena QR == cadena del terminal) |

### 5.15 Prueba 14: Scanner - Scope Sucursal Especifica

**Prerequisitos:**
- Login como "Terminal Don Carlos Centro"
- QR tipo Sucursal asignado SOLO a "Don Carlos Norte" (no a "Don Carlos Centro")

| Paso | Accion | Resultado Esperado |
|---|---|---|
| 1 | Buscar QR tipo Sucursal | QR encontrado |
| 2 | Intentar cobrar | Error 422: "Este QR es tipo Sucursal y no esta asignado a esta ubicacion." |

---

## 6. API del Scanner - Referencia Tecnica

### 6.1 POST `/api/scanner/lookup`

**Request:**
```json
{
  "identifier": "EMCAD000001"
}
```

**Response 200 (QR encontrado):**
```json
{
  "gift_card": {
    "id": "uuid-aqui",
    "legacy_id": "EMCAD000001",
    "user": { "name": "Nombre", "avatar": null },
    "balance": 1500.00,
    "status": true,
    "expiry_date": "31/12/2027",
    "qr_image_path": "/storage/qr-codes/uuid_uuid.svg"
  }
}
```

**Response 404:** `{ "error": "QR no encontrado..." }`
**Response 422:** `{ "error": "Este QR esta inactivo..." }`

### 6.2 POST `/api/scanner/process-debit`

**Request:**
```json
{
  "gift_card_id": "uuid-del-qr",
  "amount": 50.00,
  "reference": "REF-001",
  "description": "Consumo en restaurante"
}
```

**Response 200 (Exitoso):**
```json
{
  "success": true,
  "transaction": {
    "id": 1,
    "folio": "TRX-20260208-000001",
    "gift_card": {
      "id": "uuid",
      "legacy_id": "EMCAD000001",
      "user": { "name": "Nombre", "avatar": null },
      "balance": 1450.00,
      "status": true
    },
    "amount": 50.00,
    "balance_before": 1500.00,
    "balance_after": 1450.00,
    "reference": "REF-001",
    "description": "Consumo en restaurante",
    "created_at": "08/02/2026 14:30:00",
    "branch_name": "Don Carlos Centro",
    "cashier_name": "Terminal Don Carlos Centro"
  }
}
```

**Response 422 (Errores de validacion):**
```json
{ "error": "Este QR es tipo Marca y solo funciona en sucursales de la marca asignada." }
{ "error": "Este QR es tipo Cadena y no puede usarse en esta sucursal." }
{ "error": "Este QR es tipo Sucursal y no esta asignado a esta ubicacion." }
{ "error": "Este QR esta inactivo y no puede ser utilizado." }
{ "error": "Saldo insuficiente. Saldo disponible: $500.00" }
```

---

## 7. Estructura de Archivos

### 7.1 Modelos

| Archivo | Descripcion |
|---|---|
| `app/Models/Chain.php` | Cadena empresarial, proteccion de eliminacion |
| `app/Models/Brand.php` | Marca, proteccion de eliminacion (branches + giftCards) |
| `app/Models/Branch.php` | Sucursal, relacion `brand()` |
| `app/Models/GiftCard.php` | Metodo `canBeUsedAtBranch()`, relaciones `chain()`, `brand()`, `branches()` |

### 7.2 Filament Resources (Admin Panel)

| Archivo | Ruta Admin | Grupo Nav |
|---|---|---|
| `app/Filament/Resources/ChainResource.php` | `/admin/chains` | Organizacion (sort: 0) |
| `app/Filament/Resources/BrandResource.php` | `/admin/brands` | Organizacion (sort: 1) |
| `app/Filament/Resources/BranchResource.php` | `/admin/branches` | Organizacion (sort: 2) |
| `app/Filament/Resources/GiftCardResource.php` | `/admin/gift-cards` | Administracion de QR (sort: 1) |
| `app/Filament/Resources/UserResource.php` | `/admin/users` | Administracion de sistema |

### 7.3 Servicios y Middleware

| Archivo | Descripcion |
|---|---|
| `app/Services/TransactionService.php` | Validacion de scope en `debit()` via `validateScope()` |
| `app/Http/Controllers/ScannerController.php` | Validacion de scope en `processDebit()` antes de cobrar |
| `app/Http/Middleware/RequiresBranch.php` | Valida branch_id + rol BranchTerminal |

### 7.4 Migraciones

| Archivo | Descripcion |
|---|---|
| `2026_02_08_100001_create_chains_table.php` | Tabla `chains` |
| `2026_02_08_100002_create_brands_table.php` | Tabla `brands` con unique compuesto |
| `2026_02_08_100003_add_brand_id_to_branches_table.php` | Agrega `brand_id` nullable a branches |
| `2026_02_08_100004_migrate_existing_branches_to_default_brand.php` | Crea "Cadenas Don Carlos" + "Mochomos", migra datos |
| `2026_02_08_100005_add_scope_fields_to_gift_cards_table.php` | Agrega `scope`, `chain_id`, `brand_id` a gift_cards |
| `2026_02_08_100006_create_branch_gift_card_table.php` | Tabla pivot para scope=branch |
| `2026_02_08_100007_migrate_existing_gift_cards_to_chain_scope.php` | Migra QRs existentes a scope=chain |

### 7.5 Enum

| Archivo | Descripcion |
|---|---|
| `app/Enums/GiftCardScope.php` | Enum con valores `chain`, `brand`, `branch` + labels en espanol |

### 7.6 Tests

| Archivo | Tests | Descripcion |
|---|---|---|
| `tests/Feature/GiftCardScopeTest.php` | 15 | `canBeUsedAtBranch()` para los 3 scopes + enum |
| `tests/Feature/ChainBrandManagementTest.php` | 16 | CRUD y proteccion de eliminacion |
| `tests/Feature/ScannerScopeValidationTest.php` | 11 | TransactionService + Scanner HTTP |
| `tests/Feature/BranchTerminalRoleTest.php` | 5 | Acceso al scanner por rol |
| `tests/Feature/GiftCardTest.php` | 28 | Tests existentes actualizados |
| `tests/Feature/TransactionTest.php` | 17 | Tests existentes actualizados |
| `tests/Feature/BalanceImportTest.php` | 11 | Tests existentes actualizados |

**Comando para ejecutar todos:**
```bash
vendor/bin/pest tests/Feature/GiftCardScopeTest.php tests/Feature/ChainBrandManagementTest.php tests/Feature/ScannerScopeValidationTest.php tests/Feature/BranchTerminalRoleTest.php tests/Feature/GiftCardTest.php tests/Feature/TransactionTest.php tests/Feature/BalanceImportTest.php
```

---

## 8. Flujo de Validacion Completo

```
Usuario BranchTerminal escanea QR en /scanner
    |
    v
ScannerController.processDebit()
    |
    ├── 1. Verificar QR activo (status=true)
    |       └── Si inactivo → 422 "Este QR esta inactivo..."
    |
    ├── 2. Validar alcance (canBeUsedAtBranch)
    |       ├── scope=chain: chain_id == branch.brand.chain_id?
    |       ├── scope=brand: brand_id == branch.brand_id?
    |       └── scope=branch: branch en pivot table?
    |       └── Si falla → 422 con mensaje segun scope
    |
    ├── 3. Verificar saldo >= monto
    |       └── Si insuficiente → 422 "Saldo insuficiente..."
    |
    v
TransactionService.debit()
    |
    ├── 4. Validar amount > 0
    ├── 5. Validar branch_id presente
    ├── 6. validateScope() (segunda capa de validacion)
    ├── 7. DB::transaction { actualizar saldo, crear Transaction }
    |
    v
Response 200 con folio y saldo actualizado
```

---

## 9. Notas para el Agente de Pruebas

### 9.1 Orden Recomendado de Pruebas

1. Verificar datos de migracion (Prueba 1)
2. Crear jerarquia nueva (Prueba 2)
3. Probar proteccion de eliminacion (Prueba 3)
4. Crear QRs con los 3 scopes (Pruebas 4, 5, 6)
5. Verificar reactividad de campos (Prueba 7)
6. Probar filtros (Prueba 8)
7. Crear usuarios con diferentes roles (Prueba 9)
8. Verificar acceso al scanner por rol (Prueba 10)
9. Probar scanner con cobros permitidos y rechazados (Pruebas 11-14)

### 9.2 Datos Minimos para Pruebas de Scanner

Para probar el scanner end-to-end necesitas:
1. Una cadena (ya existe "Cadenas Don Carlos")
2. Al menos 2 marcas bajo esa cadena
3. Al menos 1 sucursal por marca
4. Un usuario con rol `BranchTerminal` asignado a una sucursal
5. QRs con diferentes scopes y saldo > 0

### 9.3 Elementos de UI Importantes

**Navegacion lateral del admin:**
- Grupo "Organizacion": Cadenas > Marcas > Sucursales
- Grupo "Administracion de QR": QR Codes
- Grupo "Administracion de sistema": Usuarios

**Formulario QR Code - Seccion "Alcance de Uso":**
- Select "Tipo de Alcance" con icono `heroicon-o-globe-alt`
- Campos condicionales aparecen/desaparecen via Livewire (`->live()`)
- Multi-select para sucursales permite seleccion multiple

**Tabla QR Codes - Columna "Alcance":**
- Badge verde: Cadena
- Badge amarillo: Marca
- Badge azul: Sucursal

**Scanner (`/scanner`):**
- Muestra nombre de sucursal del usuario actual
- Campo de busqueda acepta legacy_id o UUID
- Mensajes de error aparecen en formato JSON en la interfaz

### 9.4 Credenciales y Usuarios

El admin debe crear los usuarios de prueba manualmente desde `/admin/users`. Los campos importantes son:
- **Nombre**: Identificativo
- **Email**: Unico
- **Password**: Minimo requerido
- **Sucursal**: Obligatoria para BranchTerminal
- **Roles**: "BranchTerminal" para acceso a scanner
- **Estado**: Activo (toggle ON)

### 9.5 Agregar Saldo a QR Codes

Para que los QRs tengan saldo para las pruebas de scanner:
1. Ir a `/admin/gift-cards`
2. Editar un QR code
3. Ir a la pestana/relacion "Transacciones"
4. O usar la funcionalidad de carga masiva de saldos

---

**Documento generado el 2026-02-08. Branch: `implement-shield`.**
