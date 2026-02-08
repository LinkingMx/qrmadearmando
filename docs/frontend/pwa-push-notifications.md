# Frontend PWA + Push Notifications Guide

**Version**: 1.0
**Last Updated**: 2026-02-08
**Status**: Production Ready

## Table of Contents

1. [What is PWA and Push Notifications](#what-is-pwa-and-push-notifications)
2. [React Hooks](#react-hooks)
3. [UI Components](#ui-components)
4. [Service Worker](#service-worker)
5. [Usage Examples](#usage-examples)
6. [Browser Support](#browser-support)
7. [Troubleshooting](#troubleshooting)

---

## What is PWA and Push Notifications

### Progressive Web App (PWA)

A PWA is a web app that works like a native mobile app:

- **Installable**: Add to home screen on mobile or taskbar on desktop
- **Offline-Ready**: Loads cached content when offline
- **Push Notifications**: Receive notifications even when browser is closed
- **App-Like**: Runs in standalone mode (no browser UI)

### Web Push Notifications

- **Real-time**: Sent immediately when transaction occurs
- **Always Connected**: Delivered even if app isn't open
- **Permission-Based**: User must explicitly allow notifications
- **Device-Specific**: Each device/browser has separate subscription

### How It Works

```
1. User clicks bell icon → grants notification permission
2. Browser generates subscription with push service endpoint
3. Subscription sent to backend (stored in database)
4. Transaction occurs on backend
5. Backend sends notification to push service
6. Push service routes to user's device
7. Service Worker receives push event
8. Notification displayed to user
```

---

## React Hooks

### 1. usePushNotifications Hook

**Location**: `resources/js/hooks/use-push-notifications.ts`

**Purpose**: Manages push notification subscription lifecycle.

#### Import

```typescript
import { usePushNotifications } from '@/hooks/use-push-notifications'
```

#### Return Type

```typescript
interface UsePushNotificationsReturn {
  isSupported: boolean           // Browser supports Web Push API
  isSubscribed: boolean          // User has active subscription
  isLoading: boolean             // Request in progress
  error: Error | null            // Last error from subscription
  permission: NotificationPermission  // 'granted' | 'denied' | 'default'
  subscribe: () => Promise<void>      // Request permission and subscribe
  unsubscribe: () => Promise<void>    // Unsubscribe from push
}
```

#### Usage

```typescript
function MyComponent() {
  const {
    isSupported,
    isSubscribed,
    isLoading,
    error,
    subscribe,
    unsubscribe,
  } = usePushNotifications()

  // Check if browser supports push
  if (!isSupported) {
    return <p>Tu navegador no soporta notificaciones</p>
  }

  // Loading state
  if (isLoading) {
    return <p>Conectando...</p>
  }

  // Error state
  if (error) {
    return <p>Error: {error.message}</p>
  }

  // Current subscription state
  return (
    <div>
      <p>{isSubscribed ? 'Notificaciones activas' : 'Notificaciones desactivadas'}</p>
      <button onClick={isSubscribed ? unsubscribe : subscribe}>
        {isSubscribed ? 'Desactivar' : 'Activar'}
      </button>
    </div>
  )
}
```

#### Details

**isSupported**: Checks browser capabilities

```typescript
// Returns true if:
// - navigator.serviceWorker exists
// - PushManager available
// - Notification API available
```

**subscribe()**: Request permission and establish subscription

```typescript
// Steps:
// 1. Request notification permission (browser dialog)
// 2. If denied, update state and stop
// 3. Get service worker registration
// 4. Call pushManager.subscribe() with VAPID public key
// 5. Send subscription to POST /api/push-subscriptions
// 6. Save subscription state in localStorage

// Error handling:
// - Permission denied: don't retry
// - Network error: retry with exponential backoff (1s, 2s, 4s)
// - VAPID error: show error, don't retry
```

**unsubscribe()**: Remove subscription

```typescript
// Steps:
// 1. Get active subscription from pushManager
// 2. Send DELETE to /api/push-subscriptions
// 3. Call subscription.unsubscribe() in browser
// 4. Update local state
```

**permission**: Current notification permission state

```typescript
// 'granted' - User allowed notifications
// 'denied' - User denied notifications
// 'default' - Never asked, or not set
```

#### Local Storage Keys

Hook saves state in localStorage for persistence:

```javascript
localStorage.getItem('pwa:push-permission')      // 'granted' | 'denied' | 'default'
localStorage.getItem('pwa:push-subscription')    // Endpoint string (for fast init)
```

### 2. usePwaInstall Hook

**Location**: `resources/js/hooks/use-pwa-install.ts`

**Purpose**: Manages PWA installation prompts.

#### Import

```typescript
import { usePwaInstall } from '@/hooks/use-pwa-install'
```

#### Return Type

```typescript
interface UsePwaInstallReturn {
  canInstall: boolean        // beforeinstallprompt event captured
  install: () => Promise<void>  // Show native install prompt
  isInstalled: boolean       // App running in standalone mode
  isSupported: boolean       // Browser supports PWA install
  dismiss: () => void        // Dismiss prompt for 14 days
}
```

#### Usage

```typescript
function InstallButton() {
  const { canInstall, isInstalled, isSupported, install, dismiss } = usePwaInstall()

  // Not supported
  if (!isSupported) {
    return null
  }

  // Already installed
  if (isInstalled) {
    return null
  }

  // Can't install
  if (!canInstall) {
    return null
  }

  return (
    <div>
      <button onClick={install}>Instalar la app</button>
      <button onClick={dismiss}>Ahora no (no preguntar por 14 días)</button>
    </div>
  )
}
```

#### Details

**canInstall**: True when browser fires `beforeinstallprompt` event

```typescript
// Hook listens for beforeinstallprompt on window
// Automatically becomes true on mobile when installable
// False on desktop or if already installed
```

**install()**: Shows native install prompt

```typescript
// Only works if canInstall is true
// Shows browser's native install dialog
// Returns promise after user choice
```

**isInstalled**: Checks if running as PWA

```typescript
// Queries window.matchMedia('(display-mode: standalone)')
// True if running from home screen / app mode
// False if running in browser tab
```

**dismiss()**: Hide prompt for 14 days

```typescript
// Sets localStorage timestamp
// Hook won't show install prompt for 14 days
// User can reset by clearing localStorage
```

**localStorage Keys**:

```javascript
localStorage.getItem('pwa:install-dismissed-timestamp') // ISO date string
```

---

## UI Components

### 1. NotificationBell Component

**Location**: `resources/js/components/notification-bell.tsx`

**Purpose**: Header icon to toggle push notifications.

#### Import

```typescript
import { NotificationBell } from '@/components/notification-bell'
```

#### Props

```typescript
interface NotificationBellProps {
  className?: string  // Optional Tailwind classes
}
```

#### Usage

```typescript
// In AppLayout or AppHeader
export function AppHeader() {
  return (
    <header className="flex items-center justify-between p-4">
      <Logo />
      <NotificationBell />
    </header>
  )
}
```

#### Visual States

**Green Badge** (subscribed):
- Icon: Bell with green dot
- Tooltip: "Desactivar notificaciones"
- Means: User will receive notifications

**Red Badge** (not subscribed):
- Icon: Bell with red dot
- Tooltip: "Activar notificaciones"
- Means: User won't receive notifications

**Gray Badge** (loading):
- Icon: Bell with gray dot + spinner
- Tooltip: "Conectando..."
- Means: Subscription request in progress

#### Features

- **Click Behavior**: Toggle subscription on/off
- **Tooltip**: Spanish labels on hover (via Radix Tooltip)
- **Loading State**: Disabled during request
- **Error Handling**: Shows toast on error
- **Accessibility**: ARIA labels for screen readers

#### Code Structure

```typescript
export function NotificationBell({ className = '' }: NotificationBellProps) {
  const { isSubscribed, isLoading, error, subscribe, unsubscribe } = usePushNotifications()

  // Determine badge color
  let badgeColor = 'bg-gray-400'  // Loading
  if (!isLoading) {
    badgeColor = isSubscribed ? 'bg-green-500' : 'bg-red-500'
  }

  return (
    <Tooltip>
      <TooltipTrigger asChild>
        <button
          onClick={isSubscribed ? unsubscribe : subscribe}
          disabled={isLoading}
          className={className}
          aria-label="Activar/Desactivar notificaciones"
        >
          {/* Bell icon with badge */}
          <div className="relative">
            <BellIcon />
            <span className={`absolute top-0 right-0 h-2 w-2 rounded-full ${badgeColor}`} />
          </div>
        </button>
      </TooltipTrigger>
      <TooltipContent>
        {isLoading && 'Conectando...'}
        {!isLoading && (isSubscribed ? 'Desactivar notificaciones' : 'Activar notificaciones')}
      </TooltipContent>
    </Tooltip>
  )
}
```

### 2. PwaInstallPrompt Component

**Location**: `resources/js/components/pwa-install-prompt.tsx`

**Purpose**: Mobile-only banner promoting app installation.

#### Import

```typescript
import { PwaInstallPrompt } from '@/components/pwa-install-prompt'
```

#### Props

```typescript
interface PwaInstallPromptProps {
  className?: string  // Optional Tailwind classes
}
```

#### Usage

```typescript
// In AppLayout at root level
export function AppLayout({ children }: AppLayoutProps) {
  return (
    <div>
      {/* Header, content, etc. */}
      <main>{children}</main>

      {/* PWA Install Prompt at bottom */}
      <PwaInstallPrompt />
    </div>
  )
}
```

#### Visual Design

Mobile-only sticky banner at bottom:

```
┌─────────────────────────────────────┐
│ 📲 Instala QR Made en tu teléfono  │
│ Accede a tu saldo y recibe          │
│ notificaciones en tiempo real.       │
│                                     │
│ [Instalar]      [Ahora no]          │
└─────────────────────────────────────┘
```

#### Features

- **Mobile-Only**: Hidden on desktop (screen width > 640px)
- **Sticky Position**: Appears at bottom of viewport
- **Animation**: Slides in from bottom, slides out on dismiss
- **Smart Display**: Hidden if already installed or dismissed in last 14 days
- **Spanish Copy**: Benefit-focused messaging
- **Buttons**:
  - **Instalar**: Shows native browser install prompt
  - **Ahora no**: Dismisses for 14 days

#### Behavioral Logic

```
┌─ Browser supports PWA?
├─ No → Don't render
│
├─ Yes, check isInstalled
├─ Yes → Don't render (already installed)
│
├─ Check 14-day dismissal
├─ Dismissed → Don't render
│
├─ Check screen size
├─ Desktop → Don't render
│
├─ All checks pass → Render banner
│
└─ User clicks "Instalar"
   └─ Call install()
   └─ Auto-dismiss on success
```

#### Code Structure

```typescript
export function PwaInstallPrompt({ className = '' }: PwaInstallPromptProps) {
  const { canInstall, isInstalled, isSupported, install, dismiss } = usePwaInstall()
  const [isOpen, setIsOpen] = useState(true)
  const isMobile = useMediaQuery('(max-width: 640px)')

  // Don't render if unsupported, installed, or dismissed
  if (!isSupported || isInstalled || !isMobile || !canInstall || !isOpen) {
    return null
  }

  async function handleInstall() {
    await install()
    setIsOpen(false)  // Auto-dismiss after successful install
  }

  function handleDismiss() {
    dismiss()
    setIsOpen(false)
  }

  return (
    <div className={`fixed bottom-0 left-0 right-0 bg-white shadow-lg p-4 ${className}`}>
      <h3 className="font-semibold text-lg mb-2">📲 Instala QR Made en tu teléfono</h3>
      <p className="text-sm text-gray-600 mb-4">
        Accede a tu saldo y recibe notificaciones en tiempo real. ¡Más rápido y sin abrir el navegador!
      </p>
      <div className="flex gap-2">
        <button onClick={handleInstall} className="btn btn-primary">
          Instalar
        </button>
        <button onClick={handleDismiss} className="btn btn-secondary">
          Ahora no
        </button>
      </div>
    </div>
  )
}
```

---

## Service Worker

**Location**: `resources/js/sw-custom.ts`

**Purpose**: Handle push events from backend and notification interactions.

### Push Event Handler

```typescript
self.addEventListener('push', (event: PushEvent) => {
  // Parse notification data from backend
  const data = event.data?.json() ?? {}

  const options: NotificationOptions = {
    body: data.body || 'Tienes una notificación nueva',
    icon: data.icon || '/icons/icon-192x192.png',
    badge: '/favicon.svg',
    tag: 'transaction-notification',  // Prevents duplicate notifications
    requireInteraction: false,          // Auto-dismisses after timeout
    data: {
      url: data.url || '/dashboard',
      timestamp: Date.now(),
    },
    actions: [
      { action: 'open', title: 'Abrir' },
      { action: 'close', title: 'Cerrar' },
    ],
  }

  event.waitUntil(
    self.registration.showNotification(
      data.title || 'QR Made',
      options
    )
  )
})
```

### Notification Click Handler

```typescript
self.addEventListener('notificationclick', (event: NotificationEvent) => {
  event.notification.close()

  // Handle action buttons
  if (event.action === 'close') {
    return
  }

  const url = event.notification.data?.url || '/dashboard'

  event.waitUntil(
    clients.matchAll({ type: 'window' }).then((clientList) => {
      // If dashboard is already open, focus the window
      for (const client of clientList) {
        if (client.url.includes(url) && 'focus' in client) {
          return (client as any).focus()
        }
      }

      // Otherwise open new window
      if (clients.openWindow) {
        return clients.openWindow(url)
      }
    })
  )
})
```

### Notification Close Handler (Optional)

```typescript
self.addEventListener('notificationclose', (event: NotificationEvent) => {
  // Optional: Log analytics on dismissal
  console.log('Notification dismissed:', event.notification.data)
})
```

---

## Usage Examples

### Complete Integration in Layout

**File**: `resources/js/layouts/app-layout.tsx`

```typescript
import { NotificationBell } from '@/components/notification-bell'
import { PwaInstallPrompt } from '@/components/pwa-install-prompt'

export function AppLayout({ children }: AppLayoutProps) {
  return (
    <div className="min-h-screen bg-gray-50">
      {/* Header with notification bell */}
      <header className="bg-white shadow-sm border-b border-gray-200">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-3 flex justify-between items-center">
          <Logo />

          <div className="flex items-center gap-4">
            {/* Notification Bell - Always visible for authenticated users */}
            <NotificationBell />

            {/* User Menu */}
            <UserMenu />
          </div>
        </div>
      </header>

      {/* Main Content */}
      <main className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        {children}
      </main>

      {/* PWA Install Prompt - Conditionally rendered at root level */}
      <PwaInstallPrompt />
    </div>
  )
}
```

### Page-Level Usage

```typescript
import { usePushNotifications } from '@/hooks/use-push-notifications'

export function SettingsPage() {
  const { isSubscribed, subscribe, unsubscribe } = usePushNotifications()

  return (
    <section>
      <h2>Notificaciones</h2>

      <div className="flex items-center justify-between p-4 bg-white rounded-lg border">
        <div>
          <h3 className="font-semibold">Notificaciones Push</h3>
          <p className="text-sm text-gray-600">
            Recibe alertas en tiempo real cuando tu saldo cambia
          </p>
        </div>

        <button
          onClick={isSubscribed ? unsubscribe : subscribe}
          className={isSubscribed ? 'btn-danger' : 'btn-primary'}
        >
          {isSubscribed ? 'Desactivar' : 'Activar'}
        </button>
      </div>

      {isSubscribed && (
        <p className="mt-2 text-sm text-green-600">✓ Notificaciones activas</p>
      )}
    </section>
  )
}
```

### Request Permission on First Visit

```typescript
import { useEffect } from 'react'
import { usePushNotifications } from '@/hooks/use-push-notifications'

export function NotificationPrompt() {
  const { permission, subscribe, isSupported } = usePushNotifications()

  useEffect(() => {
    // On first visit, if not set, request permission
    if (isSupported && permission === 'default') {
      // Show your own prompt first
      const response = confirm('¿Deseas recibir notificaciones de cambios en tu saldo?')

      if (response) {
        subscribe()
      }
    }
  }, [permission, isSupported, subscribe])

  return null
}
```

---

## Browser Support

### Push Notifications Support

| Browser | Min Version | Support |
|---------|------------|---------|
| **Chrome** | 50+ | ✅ Full |
| **Firefox** | 48+ | ✅ Full |
| **Safari** | 16+ | ✅ Full (iOS 16+) |
| **Edge** | 79+ | ✅ Full |
| **Opera** | 37+ | ✅ Full |
| **IE** | — | ❌ Not supported |

### PWA Installation Support

| Browser | Min Version | Support |
|---------|------------|---------|
| **Chrome** | 67+ | ✅ Full |
| **Firefox** | 58+ | ✅ Full |
| **Safari** | 11.3+ | ✅ Full (macOS) |
| **Edge** | 79+ | ✅ Full |
| **Opera** | 54+ | ✅ Full |
| **IE** | — | ❌ Not supported |

### Graceful Degradation

If browser doesn't support PWA or push:

- NotificationBell: Hidden or disabled
- PwaInstallPrompt: Not rendered
- App still functions normally
- Users can use scanner and dashboard as usual

---

## Troubleshooting

### Notifications Not Received

**Checklist**:

1. ✅ User granted permission (green badge on bell)
2. ✅ Service Worker registered (DevTools → Application → Service Workers)
3. ✅ Subscription in database: `SELECT * FROM push_subscriptions WHERE user_id = X`
4. ✅ Queue worker running: `ps aux | grep "queue:work"`
5. ✅ HTTPS enabled (required for production)
6. ✅ Backend received event (check logs)

**Debug Steps**:

```javascript
// In browser console
navigator.serviceWorker.ready.then(reg => {
  reg.pushManager.getSubscription().then(sub => {
    console.log('Subscription:', sub)
    if (sub) {
      console.log('Endpoint:', sub.endpoint)
    }
  })
})
```

### Permission Denied

**To Reset**:

- **Chrome**: Settings → Privacy → Site Settings → Notifications → Find qrmade.com → Remove
- **Firefox**: Preferences → Privacy → Permissions → Notifications → Remove
- **Safari**: Settings → Websites → Notifications → Remove

Then reload page and retry.

### Service Worker Not Registering

```javascript
// Check in DevTools Console
navigator.serviceWorker.getRegistrations().then(regs => {
  console.log('Registrations:', regs.length)
  regs.forEach(reg => console.log('SW URL:', reg.scope))
})
```

**Solutions**:

- Clear cache: DevTools → Application → Clear storage
- Check HTTPS: Service Workers require HTTPS
- Verify `manifest.json` exists: DevTools → Application → Manifest
- Reload page after clearing cache

### "No push service" Error

**Cause**: Push service endpoint invalid or from unknown provider.

**Solution**: Ensure subscription uses known provider:
- `fcm.googleapis.com` (Google Firebase)
- `updates.push.services.mozilla.com` (Mozilla)
- `api.push.apple.com` (Apple)

### Hook Hook Returns `isSupported: false`

**Check**:

```typescript
console.log({
  hasServiceWorker: !!navigator.serviceWorker,
  hasPushManager: !!window.PushManager,
  hasNotification: !!window.Notification,
})
```

If any are false, browser doesn't support PWA push. App still works, just no notifications.

### localStorage Errors

If you see errors about `localStorage.getItem()`:

```typescript
// Use safe wrapper
function getSafeLocalStorage(key) {
  try {
    return localStorage.getItem(key)
  } catch (e) {
    // Private browsing or storage disabled
    return null
  }
}
```

---

**Document Version**: 1.0
**Last Updated**: 2026-02-08
**Author**: Documentation Specialist
**Next**: See [Deployment Guide](../deployment/pwa-push-notifications.md)
