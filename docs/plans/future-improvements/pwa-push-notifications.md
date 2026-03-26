# Plan: PWA + Push Notifications para Transacciones de Gift Cards

## Contexto

Los empleados acceden al dashboard desde sus telefonos para ver su QR y saldo. Actualmente no reciben notificaciones cuando se realiza una transaccion (debito/credito) en su gift card. Se quiere convertir la app en PWA (Progressive Web App) para que puedan instalarla en su telefono y recibir push notifications en tiempo real cuando su saldo cambie.

---

## Estrategia de Paquetes

| Componente | Paquete | Razon |
|------------|---------|-------|
| **Service Worker + Manifest** | `vite-plugin-pwa` (npm) | Generacion automatica de SW, manifest, hooks React, Workbox caching. 4K+ stars, compatible con Vite 7 |
| **Push Notifications** | `laravel-notification-channels/webpush` (composer) | VAPID keys, suscripciones en DB, canal de notificacion Laravel nativo. 788+ stars, v10.4.0 |

**No se usa** `eramitgupta/laravel-pwa` porque es redundante con `vite-plugin-pwa` en proyectos React/Vite.

---

## Fase 1: Dependencias y Configuracion (~2h)

### Instalar paquetes

```bash
# Backend
composer require laravel-notification-channels/webpush

# Frontend
npm install vite-plugin-pwa -D
```

### Publicar y migrar

```bash
php artisan vendor:publish --provider="NotificationChannels\WebPush\WebPushServiceProvider" --tag="migrations"
php artisan migrate
php artisan webpush:vapid
```

### Variables de entorno (.env.example)

```env
VAPID_PUBLIC_KEY=
VAPID_PRIVATE_KEY=
VAPID_SUBJECT=mailto:admin@selatravel.com
```

### Modelo User

**Modificar:** `app/Models/User.php`
- Agregar trait `HasPushSubscriptions` de webpush

### Iconos PWA

Generar desde `favicon.svg` existente:
```
public/icons/icon-192x192.png   (192x192)
public/icons/icon-512x512.png   (512x512)
public/icons/icon-maskable.png  (512x512, con padding para maskable)
```

---

## Fase 2: Backend (~5h)

### Evento de Transaccion

**Nuevo:** `app/Events/TransactionCreated.php`
- Se dispara despues de crear una transaccion exitosa
- Payload: `Transaction $transaction`

### Listener

**Nuevo:** `app/Listeners/SendTransactionPushNotification.php`
- Escucha `TransactionCreated`
- Obtiene el usuario dueno de la gift card (`$transaction->giftCard->user`)
- Despacha notificacion a la cola

### Notificacion

**Nuevo:** `app/Notifications/TransactionNotification.php`
- Canal: `WebPushChannel`
- Contenido segun tipo de transaccion:
  - **Debit:** "Se realizo un cargo de $XX.XX en tu tarjeta. Saldo: $YY.YY"
  - **Credit:** "Se abono $XX.XX a tu tarjeta. Saldo: $YY.YY"
  - **Adjustment:** "Se realizo un ajuste de $XX.XX en tu tarjeta. Saldo: $YY.YY"
- Icono: `/icons/icon-192x192.png`
- Badge: `/favicon.svg`
- Accion: Abrir `/dashboard`

### Integracion en TransactionService

**Modificar:** `app/Services/TransactionService.php`
- Despues de cada `DB::transaction()` exitoso, disparar `TransactionCreated` event
- El evento se despacha FUERA del DB::transaction para no bloquear

### Controller de suscripciones

**Nuevo:** `app/Http/Controllers/PushSubscriptionController.php`
- `POST /api/push-subscriptions` - Guardar suscripcion del navegador
- `DELETE /api/push-subscriptions` - Eliminar suscripcion
- Seguridad: Solo usuarios autenticados

### Rutas

**Modificar:** `routes/web.php`
```php
Route::middleware(['auth', 'verified'])->group(function () {
    Route::post('api/push-subscriptions', [PushSubscriptionController::class, 'store']);
    Route::delete('api/push-subscriptions', [PushSubscriptionController::class, 'destroy']);
});
```

### Registrar evento

**Modificar:** `app/Providers/EventServiceProvider.php` (o bootstrap/app.php)
```php
TransactionCreated::class => [
    SendTransactionPushNotification::class,
],
```

---

## Fase 3: Frontend (~5h)

### Configurar vite-plugin-pwa

**Modificar:** `vite.config.ts`
```typescript
import { VitePWA } from 'vite-plugin-pwa'

VitePWA({
    registerType: 'autoUpdate',
    manifest: {
        name: 'QR Made - Sistema de Gift Cards',
        short_name: 'QR Made',
        description: 'Sistema de gestion de gift cards con QR',
        theme_color: '#191731',        // Navy
        background_color: '#F8F6F1',   // Soft cream
        display: 'standalone',
        orientation: 'portrait',
        scope: '/',
        start_url: '/dashboard',
        icons: [
            { src: '/icons/icon-192x192.png', sizes: '192x192', type: 'image/png' },
            { src: '/icons/icon-512x512.png', sizes: '512x512', type: 'image/png' },
            { src: '/icons/icon-maskable.png', sizes: '512x512', type: 'image/png', purpose: 'maskable' },
        ],
    },
    workbox: {
        globPatterns: ['**/*.{js,css,html,ico,png,svg,woff2,webp}'],
        navigateFallback: null, // Inertia maneja su propia navegacion
        runtimeCaching: [
            {
                urlPattern: ({ request }) => request.headers.get('x-inertia') === 'true',
                handler: 'NetworkFirst', // Inertia requests siempre network-first
                options: { cacheName: 'inertia-pages' },
            },
            {
                urlPattern: /\.(?:png|jpg|jpeg|svg|gif|webp)$/,
                handler: 'CacheFirst',
                options: { cacheName: 'images', expiration: { maxEntries: 50, maxAgeSeconds: 30 * 24 * 60 * 60 } },
            },
        ],
    },
})
```

### Meta tags PWA

**Modificar:** `resources/views/app.blade.php`
```html
<meta name="theme-color" content="#191731">
<meta name="description" content="Sistema de gestion de gift cards con QR">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta name="apple-mobile-web-app-title" content="QR Made">
```

### Hook de Push Notifications

**Nuevo:** `resources/js/hooks/use-push-notifications.ts`
- `subscribe()` - Solicita permiso y registra suscripcion en backend
- `unsubscribe()` - Elimina suscripcion
- `isSubscribed` - Estado actual
- `isSupported` - Verifica si el navegador soporta push
- Usa VAPID public key desde `import.meta.env.VITE_VAPID_PUBLIC_KEY`

### Hook de PWA Install

**Nuevo:** `resources/js/hooks/use-pwa-install.ts`
- Captura evento `beforeinstallprompt`
- `canInstall` - Si la app se puede instalar
- `install()` - Muestra prompt de instalacion
- `isInstalled` - Si ya esta instalada (display-mode: standalone)

### Componente de notificaciones

**Nuevo:** `resources/js/components/notification-bell.tsx`
- Icono de campana en el header de la app
- Badge con estado (activo/inactivo)
- Click: toggle suscripcion on/off
- Tooltip: "Activar/Desactivar notificaciones"
- Texto en espanol

### Componente de instalacion PWA

**Nuevo:** `resources/js/components/pwa-install-prompt.tsx`
- Banner o dialog invitando a instalar la app
- Botones: "Instalar" / "Ahora no"
- Solo visible en mobile cuando no esta instalada
- Texto en espanol

### Integracion en layout

**Modificar:** `resources/js/layouts/app-layout.tsx` (o app-header.tsx)
- Agregar `<NotificationBell />` en el header junto al menu de usuario
- Agregar `<PwaInstallPrompt />` condicional

### Pasar VAPID key al frontend

**Modificar:** `.env` y `vite.config.ts`
```env
VITE_VAPID_PUBLIC_KEY=${VAPID_PUBLIC_KEY}
```

---

## Fase 4: Service Worker para Push

El service worker generado por vite-plugin-pwa necesita escuchar eventos push:

**Nuevo:** `resources/js/sw-custom.ts` (inyectado via injectManifest)
```typescript
self.addEventListener('push', (event) => {
    const data = event.data?.json();
    event.waitUntil(
        self.registration.showNotification(data.title, {
            body: data.body,
            icon: data.icon || '/icons/icon-192x192.png',
            badge: '/favicon.svg',
            data: { url: data.url || '/dashboard' },
        })
    );
});

self.addEventListener('notificationclick', (event) => {
    event.notification.close();
    event.waitUntil(clients.openWindow(event.notification.data.url));
});
```

---

## Fase 5: Testing (~3h)

### Tests Backend (Pest)

```
tests/Feature/PushNotification/TransactionNotificationTest.php
tests/Feature/PushNotification/PushSubscriptionControllerTest.php
tests/Feature/PushNotification/TransactionCreatedEventTest.php
```

**Verificaciones:**
- Evento `TransactionCreated` se dispara despues de debit/credit/adjustment
- Listener despacha notificacion al usuario correcto (dueno de la gift card)
- Endpoint de suscripcion guarda/elimina correctamente
- Solo usuarios autenticados pueden suscribirse

### Verificacion manual

1. `npm run build` - Verificar manifest.json en public/build/
2. Chrome DevTools > Application > Manifest - Validar PWA
3. Chrome DevTools > Application > Service Workers - Verificar SW activo
4. Lighthouse > PWA audit - Score 100
5. Instalar app en telefono, procesar un debito, verificar push notification

---

## Resumen de Archivos

### Nuevos (10)

| Archivo | Proposito |
|---------|-----------|
| `app/Events/TransactionCreated.php` | Evento post-transaccion |
| `app/Listeners/SendTransactionPushNotification.php` | Despacha push |
| `app/Notifications/TransactionNotification.php` | Contenido del push |
| `app/Http/Controllers/PushSubscriptionController.php` | API suscripciones |
| `resources/js/hooks/use-push-notifications.ts` | Hook React push |
| `resources/js/hooks/use-pwa-install.ts` | Hook React instalacion |
| `resources/js/components/notification-bell.tsx` | UI campana |
| `resources/js/components/pwa-install-prompt.tsx` | UI instalar app |
| `resources/js/sw-custom.ts` | Listeners push en SW |
| `public/icons/` | Iconos PWA (192, 512, maskable) |

### Modificados (6)

| Archivo | Cambio |
|---------|--------|
| `app/Models/User.php` | Agregar trait `HasPushSubscriptions` |
| `app/Services/TransactionService.php` | Disparar `TransactionCreated` event |
| `vite.config.ts` | Agregar `VitePWA` plugin |
| `resources/views/app.blade.php` | Meta tags PWA |
| `routes/web.php` | Rutas push subscription |
| `.env.example` | VAPID keys |

---

## Estimacion Total: ~15h

| Fase | Tiempo |
|------|--------|
| Configuracion | ~2h |
| Backend | ~5h |
| Frontend | ~5h |
| Testing | ~3h |
