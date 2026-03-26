# Plan: Apple Wallet & Google Wallet para Gift Cards QR

## Contexto

El sistema actual genera QR codes (SVG) para cada gift card. Los empleados ven su QR en `/dashboard` y los scanners lo leen para procesar debitos. Se quiere agregar botones "Agregar a Apple Wallet" y "Agregar a Google Wallet" en el dashboard del empleado para que puedan guardar su tarjeta directamente en el wallet de su telefono, con actualizacion automatica de saldo.

---

## Estrategia de Paquetes

| Plataforma | Paquete | Razon |
|------------|---------|-------|
| **Apple Wallet** | `spatie/laravel-mobile-pass` | Push notifications automaticas, registro de dispositivos, trait Eloquent `HasMobilePasses`, builder para StoreCard |
| **Google Wallet** | `chiiya/laravel-passes` | Repositorios nativos GiftCard, firma JWT, actualizacion de saldo via API PATCH |

---

## Fase 1: Configuracion y Credenciales (~3h)

### Requisitos externos (manuales, fuera del codigo)
- **Apple Developer Account** ($99/ano) + crear Pass Type ID (`pass.com.selatravel.giftcard`) + certificado .p12 + descargar WWDR G4
- **Google Cloud Project** (gratis) + habilitar Google Wallet API + Service Account JSON + Issuer ID

### Dependencias
```bash
composer require spatie/laravel-mobile-pass chiiya/laravel-passes
php artisan vendor:publish --tag="mobile-pass-config"
php artisan vendor:publish --tag="mobile-pass-migrations"
php artisan vendor:publish --tag="passes-config"
php artisan migrate
```

### Variables de entorno (`.env.example`)
```env
# Apple Wallet
MOBILE_PASS_APPLE_ORGANISATION_NAME="SELA Travel"
MOBILE_PASS_APPLE_TYPE_IDENTIFIER="pass.com.selatravel.giftcard"
MOBILE_PASS_APPLE_TEAM_IDENTIFIER=""
MOBILE_PASS_APPLE_CERTIFICATE_PATH="${APP_ROOT}/storage/app/credentials/apple-pass.p12"
MOBILE_PASS_APPLE_CERTIFICATE_PASSWORD=""
MOBILE_PASS_APPLE_WEBSERVICE_SECRET=""
MOBILE_PASS_APPLE_WEBSERVICE_HOST="${APP_URL}"

# Google Wallet
GOOGLE_WALLET_CREDENTIALS_PATH="${APP_ROOT}/storage/app/credentials/google-service-account.json"
GOOGLE_WALLET_ISSUER_ID=""
```

### Credenciales (NO en repo)
```
storage/app/credentials/apple-pass.p12
storage/app/credentials/google-service-account.json
```
Agregar `/storage/app/credentials/` a `.gitignore`

### Imagenes de passes
```
storage/app/passes/images/icon.png         (29x29)
storage/app/passes/images/icon@2x.png      (58x58)
storage/app/passes/images/icon@3x.png      (87x87)
storage/app/passes/images/logo.png         (160x50)
storage/app/passes/images/logo@2x.png      (320x100)
public/images/sela-logo-google.png         (660x660)
public/images/add-to-apple-wallet.svg      (badge oficial Apple)
public/images/add-to-google-wallet.svg     (badge oficial Google)
```

---

## Fase 2: Backend (~8h)

### Migracion
**Nuevo:** `database/migrations/XXXX_add_google_wallet_object_id_to_gift_cards_table.php`
- Agrega `google_wallet_object_id` (string, nullable) a `gift_cards`

### Modelo GiftCard
**Modificar:** `app/Models/GiftCard.php`
- Agregar trait `HasMobilePasses` (spatie)
- Agregar `google_wallet_object_id` a `$fillable`
- En `booted()`: despachar `UpdateWalletPassBalance` job cuando cambie el balance

### Servicios nuevos

**`app/Services/AppleWalletService.php`**
- `createPass(GiftCard)` - Genera PKPass tipo **Generic** con GenericPassBuilder (spatie)
  - Colores SELA: bg Navy #191731, fg Cream #EBDFC7, labels Gold #C5A059
  - QR barcode con `legacy_id`
  - Campos: Saldo (header), Empleado (primary), No. Tarjeta + Expira (secondary)
- `updateBalance(GiftCard)` - Actualiza saldo en pass existente + push notification
- `hasPass(GiftCard)` - Verifica si ya tiene pass

**`app/Services/GoogleWalletService.php`**
- `ensureClassExists()` - Crea la clase GiftCard en Google si no existe
- `createPass(GiftCard)` - Crea objeto y retorna save URL
- `generateSaveUrl(GiftCard)` - Genera JWT firmado -> URL de Google
- `updateBalance(GiftCard)` - PATCH al objeto con nuevo saldo
- `hasPass(GiftCard)` - Verifica por `google_wallet_object_id`

**`app/Services/WalletPassService.php`** (orquestador)
- Interfaz unificada para ambas plataformas
- Inyecta `AppleWalletService` y `GoogleWalletService`

### Controller
**Nuevo:** `app/Http/Controllers/WalletPassController.php`
- `GET /wallet/apple/{giftCard}/download` - Descarga .pkpass
- `GET /wallet/google/{giftCard}/save-url` - Retorna URL de Google Wallet
- Seguridad: verifica `auth()->id() === $giftCard->user_id`

### Job asincrono
**Nuevo:** `app/Jobs/UpdateWalletPassBalance.php`
- Se despacha cuando cambia el balance de una gift card
- Ejecuta `WalletPassService::updateBalance()` en cola

### Rutas
**Modificar:** `routes/web.php`
```php
// Dentro del grupo auth + verified
Route::prefix('wallet')->group(function () {
    Route::get('apple/{giftCard}/download', [WalletPassController::class, 'downloadApplePass']);
    Route::get('google/{giftCard}/save-url', [WalletPassController::class, 'googleSaveUrl']);
});
Route::mobilePass(); // Rutas de spatie para comunicacion con dispositivos Apple
```

---

## Fase 3: Frontend (~4h)

### Tipos TypeScript
**Modificar:** `resources/js/types/employee-dashboard.ts`
- Agregar `wallet_urls: { apple_download: string | null; google_save: string | null } | null`

### Componente nuevo
**Nuevo:** `resources/js/components/dashboard/wallet-buttons.tsx`
- Renderiza botones oficiales "Add to Apple Wallet" / "Add to Google Wallet"
- Deteccion de plataforma (iOS muestra Apple prominente, Android muestra Google)
- Deshabilitado si tarjeta inactiva o expirada

### Integracion en dashboard
**Modificar:** `resources/js/components/dashboard/employee-card.tsx`
- Agregar `<WalletButtons>` debajo del QR code
- Solo visible si `giftCard.wallet_urls` existe y tarjeta activa

### Controller data
**Modificar:** `app/Http/Controllers/EmployeeDashboardController.php`
- Inyectar `WalletPassService`
- Pasar `wallet_urls` con URLs de Apple/Google al response de Inertia

---

## Fase 4: Actualizacion de Saldo en Wallet (~3h)

### Apple (automatico via spatie)
- Al ejecutar `updateBalance()`, spatie envia push notification via APNS
- Dispositivo pide pass actualizado a las rutas `Route::mobilePass()`
- Requisito: app accesible via HTTPS

### Google (API directa)
- PATCH al objeto con nuevo saldo en micros + currency
- Google propaga automaticamente al dispositivo

### Sincronizacion de estado
- Cuando gift card se desactiva: Apple muestra "Inactiva", Google cambia state a `INACTIVE`

---

## Fase 5: Testing (~5h)

### Tests nuevos (Pest)
```
tests/Feature/WalletPass/AppleWalletServiceTest.php
tests/Feature/WalletPass/GoogleWalletServiceTest.php
tests/Feature/WalletPass/WalletPassControllerTest.php
tests/Feature/WalletPass/UpdateWalletPassBalanceJobTest.php
```

### Verificacion end-to-end
1. `composer test` - Todos los tests pasan
2. En dashboard, verificar botones visibles
3. En iOS: descargar .pkpass, agregar a Wallet, verificar QR y saldo
4. En Android: abrir URL Google Wallet, agregar, verificar QR y saldo
5. Procesar un debito -> verificar saldo actualizado en ambos wallets

---

## Resumen de archivos

### Nuevos (11)
| Archivo | Proposito |
|---------|-----------|
| `app/Services/WalletPassService.php` | Orquestacion |
| `app/Services/AppleWalletService.php` | Generacion PKPass |
| `app/Services/GoogleWalletService.php` | JWT y API Google |
| `app/Http/Controllers/WalletPassController.php` | Endpoints descarga |
| `app/Jobs/UpdateWalletPassBalance.php` | Job de actualizacion |
| `database/migrations/XXXX_...google_wallet.php` | Migracion |
| `resources/js/components/dashboard/wallet-buttons.tsx` | Botones React |
| `tests/Feature/WalletPass/AppleWalletServiceTest.php` | Tests Apple |
| `tests/Feature/WalletPass/GoogleWalletServiceTest.php` | Tests Google |
| `tests/Feature/WalletPass/WalletPassControllerTest.php` | Tests controller |
| `tests/Feature/WalletPass/UpdateWalletPassBalanceJobTest.php` | Tests job |

### Modificados (7)
| Archivo | Cambio |
|---------|--------|
| `app/Models/GiftCard.php` | Trait HasMobilePasses, fillable, evento balance |
| `app/Http/Controllers/EmployeeDashboardController.php` | Inyectar WalletPassService, pasar URLs |
| `resources/js/components/dashboard/employee-card.tsx` | Agregar WalletButtons |
| `resources/js/types/employee-dashboard.ts` | Tipo wallet_urls |
| `routes/web.php` | Rutas wallet + mobilePass |
| `.env.example` | Variables wallet |
| `.gitignore` | Excluir credentials |
