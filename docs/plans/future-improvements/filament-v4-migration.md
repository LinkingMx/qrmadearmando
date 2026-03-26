# Migracion de Filament v3 a v4

**Status:** 📋 Planificado
**Priority:** 🔴 Alta
**Estimated Impact:** Mejora de rendimiento 2-3x en tablas, MFA nativo, SPA mode, nuevos componentes
**Date Created:** 2026-02-08
**Current Version:** Filament v3.3.41
**Target Version:** Filament v4.x

---

## 1. Overview

Plan de migracion seguro para actualizar Filament de v3.3.41 a v4.x. Filament v4 trae mejoras significativas de rendimiento, nuevos componentes y funcionalidades, pero requiere cambios en plugins, layout y metodos de tabla.

### 1.1 Requisitos del Sistema

| Requisito | Actual | Requerido v4 | Estado |
|---|---|---|---|
| PHP | 8.2+ | >= 8.2 | ✅ Compatible |
| Laravel | 12.0 | >= 11.28 | ✅ Compatible |
| Tailwind CSS | 4.0 | >= 4.1 (si tema custom) | ⚠️ Verificar |

---

## 2. Inventario del Proyecto

### 2.1 Resources (7)

| Resource | Archivo | Complejidad |
|---|---|---|
| UserResource | `app/Filament/Resources/UserResource.php` | Alta (FileUpload, Grid, custom actions, toggle_active) |
| GiftCardResource | `app/Filament/Resources/GiftCardResource.php` | Alta (live(), afterStateUpdated, conditional visibility, View component) |
| TransactionResource | `app/Filament/Resources/TransactionResource.php` | Media (reactive select, date filters, export) |
| BranchResource | `app/Filament/Resources/BranchResource.php` | Baja (Section, Select, TextInput) |
| BrandResource | `app/Filament/Resources/BrandResource.php` | Baja (Section, Select, TextInput, custom delete) |
| ChainResource | `app/Filament/Resources/ChainResource.php` | Baja (Section, TextInput, custom delete) |
| GiftCardCategoryResource | `app/Filament/Resources/GiftCardCategoryResource.php` | Baja (Section, TextInput, Select) |

### 2.2 Pages (21)

- 20 Resource Pages (List/Create/Edit para cada resource)
- 1 Custom Auth Page: `app/Filament/Pages/Auth/Login.php`

**Pages con logica custom:**
- `UserResource/Pages/CreateUser.php` - `mutateFormDataBeforeCreate()`, `afterCreate()`, custom redirect, custom notification
- `TransactionResource/Pages/ListTransactions.php` - `defaultSort`

### 2.3 RelationManagers (2)

| Manager | Parent | Funcionalidad Custom |
|---|---|---|
| `TransactionsRelationManager` | GiftCard | Export action (Excel), date range filtering, view-only form |
| `UsersRelationManager` | Branch | Create/Edit/Delete actions, password confirmation |

### 2.4 Custom Views (3)

| Vista | Uso |
|---|---|
| `resources/views/filament/qr-codes.blade.php` | QR codes en formulario GiftCard |
| `resources/views/filament/table-qr-codes.blade.php` | QR thumbnails en tabla GiftCard |
| `resources/views/filament/pages/auth/login.blade.php` | Login page custom |

### 2.5 Panel Provider

- `app/Providers/Filament/AdminPanelProvider.php`
- Tema custom con colores (Navy/Cream/Gold), dark mode, logo custom
- Middleware custom: `EnsureUserIsActive`
- Plugins: FilamentShield
- Sidebar collapsible, home route a GiftCard index

### 2.6 Config Files (3)

- `config/filament.php`
- `config/filament-shield.php`
- `config/filament-logger.php`

### 2.7 Plugins (2)

| Plugin | Version Actual | Version v4 | Riesgo |
|---|---|---|---|
| `bezhansalleh/filament-shield` | ^3.9 | ^4.0 | 🔴 ALTO - Rewrite completo |
| `z3d0x/filament-logger` | ^0.8.0 | ABANDONADO | 🔴 ALTO - Requiere reemplazo |

---

## 3. Breaking Changes que Afectan el Proyecto

### 3.1 Criticos (Requieren cambio manual)

#### 3.1.1 Section ya no ocupa ancho completo

**Impacto:** TODOS los 7 resources usan `Section::make()`.

```php
// v3: Section ocupa ancho completo por defecto
Section::make('Titulo')->schema([...])

// v4: Section ocupa 1 columna por defecto, debe agregar:
Section::make('Titulo')->schema([...])->columnSpanFull()
```

**Solucion global (recomendada):** Agregar en `AppServiceProvider::boot()`:
```php
use Filament\Schemas\Components\Section;
Section::configureUsing(fn (Section $section) => $section->columnSpanFull());
```

#### 3.1.2 `actions()` renombrado a `recordActions()` en tablas

**Impacto:** TODOS los resources con acciones en tabla.

```php
// v3
->actions([EditAction::make(), DeleteAction::make()])

// v4
->recordActions([EditAction::make(), DeleteAction::make()])
```

**Nota:** El script automatico de upgrade maneja este cambio.

#### 3.1.3 Filtros diferidos por defecto

**Impacto:** 5 resources con filtros (GiftCard, Transaction, Branch, Brand, User).
Los filtros ahora requieren click en "Aplicar" en vez de filtrar instantaneamente.

**Revertir globalmente si no se desea:**
```php
use Filament\Tables\Table;
Table::configureUsing(fn (Table $table) => $table->deferFilters(false));
```

#### 3.1.4 filament-logger abandonado

**Impacto:** El paquete `z3d0x/filament-logger` no tiene soporte para v4.

**Solucion:**
```bash
composer remove z3d0x/filament-logger
composer require jacobtims/filament-logger
```

Luego actualizar el registro del plugin en `AdminPanelProvider.php`:
```php
// v3 (z3d0x)
\Z3d0X\FilamentLogger\FilamentLoggerPlugin::make()

// v4 (jacobtims fork)
\Jacobtims\FilamentLogger\FilamentLoggerPlugin::make()
```

El config `filament-logger.php` es compatible (mismo formato).

#### 3.1.5 filament-shield rewrite completo

**Impacto:** Todas las politicas y permisos deben regenerarse.

**Pasos:**
```bash
composer require bezhansalleh/filament-shield:"^4.0" -W
php artisan shield:setup          # Setup interactivo
php artisan shield:generate --all # Regenerar permisos y politicas
```

**Riesgo:** Los permisos existentes en BD podrian perder sync. BACKUP OBLIGATORIO antes.

### 3.2 Moderados (Manejados por script automatico)

| Cambio | Archivos Afectados | Script Automatico |
|---|---|---|
| `unique()` ahora ignora record por defecto | ChainResource, GiftCardResource, UserResource | ✅ Simplificar a `->unique()` |
| Enum state siempre retorna instancia | GiftCardResource (scope column) | ✅ Ya usamos enum casting |
| `columnSpan()` ahora apunta a `lg` | UserResource (Grid::make(3)) | ⚠️ Revisar manualmente |
| URL params renombrados (`tableFilters` -> `filters`) | Bookmarks/links guardados | ✅ Automatico |
| Sorting por primary key por defecto | Todas las tablas | ⚠️ Puede cambiar orden visible |

### 3.3 Bajo Impacto

| Cambio | Detalle | Accion |
|---|---|---|
| Opcion "All" removida de paginacion | No afecta si no se usa | Ninguna |
| ImageColumn default `private` en discos no-local | Usamos disco local | Ninguna |
| `doctrine/dbal` ya no requerido | Simplifica dependencias | Limpiar si esta instalado |

---

## 4. Plan de Migracion Paso a Paso

### Fase 0: Preparacion (30 min)

| # | Accion | Comando/Detalle | Verificacion |
|---|---|---|---|
| 0.1 | Crear branch de migracion | `git checkout -b upgrade/filament-v4` | Branch creado |
| 0.2 | Backup de base de datos | `cp database/database.sqlite database/database.sqlite.bak` | Archivo existe |
| 0.3 | Documentar estado actual | `vendor/bin/pest` - guardar resultado | Tests base conocidos |
| 0.4 | Exportar roles y permisos | `php artisan tinker` -> exportar roles/permisos a JSON | JSON guardado |

**Script de backup de permisos:**
```php
// En tinker:
$data = [
    'roles' => \Spatie\Permission\Models\Role::with('permissions')->get()->toArray(),
    'users_roles' => \App\Models\User::with('roles')->get()->map(fn($u) => [
        'id' => $u->id, 'email' => $u->email, 'roles' => $u->roles->pluck('name')
    ])->toArray(),
];
file_put_contents(storage_path('app/permissions-backup.json'), json_encode($data, JSON_PRETTY_PRINT));
```

### Fase 1: Reemplazar Plugin Abandonado (15 min)

| # | Accion | Comando | Verificacion |
|---|---|---|---|
| 1.1 | Remover z3d0x/filament-logger | `composer remove z3d0x/filament-logger` | Sin errores |
| 1.2 | Instalar jacobtims/filament-logger | `composer require jacobtims/filament-logger` | Instalado |
| 1.3 | Actualizar AdminPanelProvider | Cambiar namespace del plugin | Sin errores |
| 1.4 | Verificar config compatible | Comparar `config/filament-logger.php` | Config funcional |
| 1.5 | Test rapido del admin | Navegar a `/admin`, revisar logs | Panel carga |

### Fase 2: Ejecutar Upgrade Automatico (30 min)

| # | Accion | Comando | Verificacion |
|---|---|---|---|
| 2.1 | Instalar herramienta de upgrade | `composer require filament/upgrade:"^4.0" -W --dev` | Instalada |
| 2.2 | Ejecutar script automatico | `vendor/bin/filament-v4` | Script completa |
| 2.3 | Revisar cambios del script | `git diff` | Entender cambios |
| 2.4 | Actualizar filament | `composer require filament/filament:"^4.0" -W` | Sin conflictos |
| 2.5 | Limpiar cache | `php artisan optimize:clear` | Cache limpia |
| 2.6 | Remover herramienta | `composer remove filament/upgrade --dev` | Removida |

### Fase 3: Fixes Manuales - Layout (30 min)

| # | Accion | Archivos | Detalle |
|---|---|---|---|
| 3.1 | Fix global Section width | `app/Providers/AppServiceProvider.php` | Agregar `Section::configureUsing(...)` en `boot()` |
| 3.2 | Fix global filter deferral | `app/Providers/AppServiceProvider.php` | Agregar `Table::configureUsing(...)` si se desea filtro instantaneo |
| 3.3 | Revisar Grid::make(3) en UserResource | `app/Filament/Resources/UserResource.php` | Verificar que `columnSpan()` sigue correcto |
| 3.4 | Revisar formulario GiftCardResource | `app/Filament/Resources/GiftCardResource.php` | Verificar `live()`, `visible()`, `required()` callbacks |
| 3.5 | Verificar View components | `filament/qr-codes.blade.php`, `table-qr-codes.blade.php` | Verificar que las vistas Blade siguen renderizando |
| 3.6 | Run Pint | `./vendor/bin/pint` | Codigo formateado |

### Fase 4: Upgrade filament-shield (45 min)

| # | Accion | Comando | Verificacion |
|---|---|---|---|
| 4.1 | Upgrade shield | `composer require bezhansalleh/filament-shield:"^4.0" -W` | Instalado |
| 4.2 | Publicar nuevo config | `php artisan vendor:publish --tag=filament-shield-config --force` | Config publicado |
| 4.3 | Setup interactivo | `php artisan shield:setup` | Completado |
| 4.4 | Regenerar permisos | `php artisan shield:generate --all` | Permisos creados |
| 4.5 | Verificar super_admin | `php artisan tinker` -> verificar rol super_admin existe | Rol presente |
| 4.6 | Restaurar asignaciones | Reasignar roles a usuarios segun backup JSON | Usuarios con roles correctos |
| 4.7 | Verificar acceso admin | Login como super_admin en `/admin` | Panel accesible |

### Fase 5: Verificacion Funcional (60 min)

| # | Area | URL | Verificaciones |
|---|---|---|---|
| 5.1 | Login admin | `/admin/login` | Login custom funciona, validacion de usuario activo |
| 5.2 | Cadenas CRUD | `/admin/chains` | Listar, crear, editar, proteccion de eliminacion |
| 5.3 | Marcas CRUD | `/admin/brands` | Listar con filtro cadena, crear, editar, badge cadena |
| 5.4 | Sucursales CRUD | `/admin/branches` | Listar con filtro marca, crear, editar, badge marca |
| 5.5 | QR Codes CRUD | `/admin/gift-cards` | Scope condicional (cadena/marca/sucursal), filtros, badges |
| 5.6 | QR Scope reactivity | `/admin/gift-cards/create` | Cambiar scope -> campos aparecen/desaparecen |
| 5.7 | Transacciones | `/admin/transactions` | Listar, filtros, export Excel |
| 5.8 | Usuarios CRUD | `/admin/users` | Crear con rol, avatar, toggle activo, asignar sucursal |
| 5.9 | Categorias CRUD | `/admin/gift-card-categories` | CRUD completo, proteccion de eliminacion |
| 5.10 | RelationManagers | `/admin/gift-cards/{id}/edit` | Tab transacciones visible, export funciona |
| 5.11 | RelationManagers | `/admin/branches/{id}/edit` | Tab usuarios visible, CRUD funciona |
| 5.12 | Activity Logs | `/admin/activity-logs` (o ruta equivalente) | Logger registra actividad |
| 5.13 | Roles/Shield | `/admin/shield/roles` | Roles visibles, permisos asignables |
| 5.14 | Navigation | Sidebar | Grupos "Organizacion" y "Administracion de QR" visibles |
| 5.15 | Scanner | `/scanner` | Middleware BranchTerminal sigue funcionando |

### Fase 6: Tests Automatizados (15 min)

```bash
# Ejecutar suite completa de tests
vendor/bin/pest tests/Feature/GiftCardTest.php \
  tests/Feature/TransactionTest.php \
  tests/Feature/GiftCardScopeTest.php \
  tests/Feature/ChainBrandManagementTest.php \
  tests/Feature/ScannerScopeValidationTest.php \
  tests/Feature/BranchTerminalRoleTest.php \
  tests/Feature/BalanceImportTest.php

# Resultado esperado: 103 tests passing
```

### Fase 7: Limpieza (15 min)

| # | Accion | Comando |
|---|---|---|
| 7.1 | Formatear codigo | `./vendor/bin/pint` |
| 7.2 | Limpiar cache | `php artisan optimize:clear` |
| 7.3 | Eliminar backup SQLite | `rm database/database.sqlite.bak` (solo si todo OK) |
| 7.4 | Eliminar backup permisos | `rm storage/app/permissions-backup.json` (solo si todo OK) |
| 7.5 | Commit final | `git add . && git commit -m "Upgrade Filament v3 to v4"` |

---

## 5. Rollback Plan

Si la migracion falla en cualquier fase:

```bash
# Opcion A: Rollback completo via git
git checkout -- .
git clean -fd
composer install
php artisan optimize:clear

# Opcion B: Restaurar DB + revert
cp database/database.sqlite.bak database/database.sqlite
git checkout -- .
composer install
php artisan optimize:clear

# Opcion C: Abandonar branch
git checkout main
git branch -D upgrade/filament-v4
```

**Punto de no retorno:** Fase 4 (upgrade de shield). Una vez que se regeneran permisos en la BD, el rollback requiere restaurar la BD completa.

---

## 6. Riesgos y Mitigaciones

| # | Riesgo | Probabilidad | Impacto | Mitigacion |
|---|---|---|---|---|
| R1 | filament-shield pierde permisos | Alta | Alto | Backup JSON de roles/permisos antes de empezar |
| R2 | Section layout roto en todos los forms | Alta | Medio | Fix global en AppServiceProvider (1 linea) |
| R3 | jacobtims/filament-logger incompatible con config actual | Baja | Bajo | Config es compatible, verificar en Fase 1 |
| R4 | Custom Login page no compatible | Media | Alto | Revisar clase Auth\Login, adaptar si hay cambios en BaseLogin |
| R5 | View components Blade no renderizan | Baja | Medio | Blade es independiente de Filament version, solo verificar |
| R6 | TransactionsRelationManager export falla | Baja | Bajo | Export usa maatwebsite/excel, independiente de Filament |
| R7 | `live()` y `visible()` callbacks no funcionan | Baja | Alto | API se mantiene en v4, pero verificar en Fase 5.6 |
| R8 | Tema custom (colores/logo) no carga | Media | Medio | Puede necesitar rebuild de assets Tailwind |
| R9 | Tests fallan por cambios en HTTP responses | Media | Medio | Tests actuales no tocan admin panel directamente |

---

## 7. Nuevas Funcionalidades Disponibles en v4

Funcionalidades que se pueden aprovechar post-migracion:

| Funcionalidad | Beneficio para el Proyecto | Prioridad |
|---|---|---|
| **SPA Mode** (`->spa()`) | Navegacion mas rapida en admin, sin recargas | Alta |
| **MFA Nativo** | Reemplazar Fortify 2FA en admin panel | Media |
| **Unsaved Changes Alerts** | Evitar perdida de datos en formularios | Alta |
| **DB Transactions** (`->databaseTransactions()`) | Wrap automatico de operaciones en transactions | Media |
| **Reorderable Columns** | Usuarios personalizan sus vistas de tabla | Baja |
| **`visibleJs()`/`hiddenJs()`** | Reactividad sin server roundtrip (scope fields) | Alta |
| **Rate Limiting en Actions** | Proteger acciones criticas (cobros) | Media |
| **Toolbar Actions** | Acciones no-row-specific en tablas | Baja |
| **Performance 2-3x** | Tablas grandes cargan mas rapido | Alta (automatico) |

---

## 8. Estimacion de Tiempo

| Fase | Duracion | Riesgo |
|---|---|---|
| Fase 0: Preparacion | 30 min | Bajo |
| Fase 1: Reemplazar logger | 15 min | Bajo |
| Fase 2: Upgrade automatico | 30 min | Medio |
| Fase 3: Fixes manuales | 30 min | Medio |
| Fase 4: Upgrade shield | 45 min | Alto |
| Fase 5: Verificacion funcional | 60 min | Medio |
| Fase 6: Tests automatizados | 15 min | Bajo |
| Fase 7: Limpieza | 15 min | Bajo |
| **Total** | **~4 horas** | |

---

## 9. Checklist Pre-Migracion

Antes de iniciar la migracion, verificar:

- [ ] Branch `implement-shield` mergeado a `main`
- [ ] Todos los tests pasan (103/103)
- [ ] No hay cambios sin commit en el working directory
- [ ] Base de datos en estado conocido/limpio
- [ ] Acceso al admin panel funciona correctamente
- [ ] Roles y permisos configurados y asignados
- [ ] Branch `upgrade/filament-v4` creado desde `main`
- [ ] Backup de `database.sqlite` creado
- [ ] Backup de roles/permisos en JSON creado

## 10. Checklist Post-Migracion

Despues de completar todas las fases:

- [ ] Admin panel carga sin errores
- [ ] Login custom funciona (validacion usuario activo)
- [ ] Los 7 resources CRUD funcionan
- [ ] Campos condicionales de scope QR funcionan (live reactivity)
- [ ] Filtros en todas las tablas funcionan
- [ ] RelationManagers funcionan (Transacciones, Usuarios)
- [ ] Export Excel de transacciones funciona
- [ ] Activity logger registra eventos
- [ ] Shield roles/permisos accesibles
- [ ] Scanner middleware funciona (BranchTerminal)
- [ ] 103 tests passing
- [ ] Pint sin errores
- [ ] QR codes se generan y muestran correctamente

---

## 11. Archivos que Seran Modificados

### Modificados por Script Automatico
- `app/Filament/Resources/UserResource.php`
- `app/Filament/Resources/GiftCardResource.php`
- `app/Filament/Resources/TransactionResource.php`
- `app/Filament/Resources/BranchResource.php`
- `app/Filament/Resources/BrandResource.php`
- `app/Filament/Resources/ChainResource.php`
- `app/Filament/Resources/GiftCardCategoryResource.php`
- `app/Filament/Resources/*/Pages/*.php` (21 archivos)
- `app/Filament/Resources/*/RelationManagers/*.php` (2 archivos)
- `app/Filament/Pages/Auth/Login.php`
- `app/Providers/Filament/AdminPanelProvider.php`
- `composer.json`
- `composer.lock`

### Modificados Manualmente
- `app/Providers/AppServiceProvider.php` (configuracion global Section + Table)
- `config/filament-shield.php` (nueva version de config)
- `config/filament-logger.php` (verificar compatibilidad con jacobtims)

### Sin Cambios Esperados
- `resources/views/filament/*.blade.php` (Blade independiente)
- `app/Models/*.php` (no dependen de Filament)
- `app/Services/*.php` (no dependen de Filament)
- `app/Http/Controllers/*.php` (no dependen de Filament)
- `app/Http/Middleware/*.php` (no dependen de Filament)
- `tests/Feature/*.php` (no tocan admin panel)
- `database/migrations/*.php` (no dependen de Filament)

---

**Documento generado el 2026-02-08. Basado en Filament v4 stable release.**
**Fuentes:** filamentphp.com/docs/4.x/upgrade-guide, GitHub filament/filament, packagist.org
