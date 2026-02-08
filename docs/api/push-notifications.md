# Push Notifications API Reference

**Version**: 1.0
**Last Updated**: 2026-02-08
**Status**: Production Ready

## Table of Contents

1. [Overview](#overview)
2. [Authentication](#authentication)
3. [Rate Limiting](#rate-limiting)
4. [Endpoints](#endpoints)
5. [Code Examples](#code-examples)
6. [Error Handling](#error-handling)
7. [Best Practices](#best-practices)

---

## Overview

The Push Notifications API enables employees to manage their real-time push notification subscriptions for balance change alerts. When a transaction (debit, credit, or adjustment) is processed on a gift card, the card owner receives an immediate push notification with transaction details.

### What This API Does

- **Subscribe** to push notifications via browser/device
- **Unsubscribe** from push notifications
- **Manage** multiple subscriptions across devices
- **Receive** real-time balance change notifications

### Base URL

```
https://qrmade.example.com/api/push-subscriptions
```

### Content Type

All requests and responses use `application/json`.

---

## Authentication

All endpoints require:

1. **User Authentication**: Authenticated session or bearer token
2. **Email Verification**: User must have verified email address (2FA requirement)

### Authentication Headers

```http
Authorization: Bearer {token}
```

Or for session-based authentication (Inertia.js requests):

```http
Cookie: XSRF-TOKEN={token}; laravel_session={session}
```

---

## Rate Limiting

**Rate Limit**: 5 requests per minute per authenticated user

**Response Headers** on rate limit:

```http
HTTP/1.1 429 Too Many Requests
RateLimit-Limit: 5
RateLimit-Remaining: 0
RateLimit-Reset: 1707394200
```

**Error Response**:

```json
{
  "message": "Too many requests. Please try again later.",
  "retry_after": 60
}
```

---

## Endpoints

### 1. Subscribe to Push Notifications

Creates or updates a push notification subscription for the authenticated user.

**Request**:

```http
POST /api/push-subscriptions
Content-Type: application/json
Authorization: Bearer {token}

{
  "endpoint": "https://fcm.googleapis.com/fcm/send/example...",
  "publicKey": "BIp4+SJq7ythmV...",
  "authToken": "k7xb8vfg3h..."
}
```

**Parameters**:

| Name | Type | Required | Description |
|------|------|----------|-------------|
| `endpoint` | string | Yes | Push service endpoint URL (HTTPS, max 2048 chars). Must be from FCM, Mozilla, or Apple push services. |
| `publicKey` | string | Yes | Browser-generated encryption key (p256dh), max 500 chars |
| `authToken` | string | Yes | Browser-generated HMAC key (auth), max 500 chars |

**Success Response** (201 Created):

```json
{
  "data": {
    "id": 1,
    "user_id": 5,
    "endpoint": "https://fcm.googleapis.com/fcm/send/...",
    "created_at": "2026-02-08T10:30:00Z"
  },
  "message": "Notificaciones activadas"
}
```

**Error Responses**:

| Status | Code | Message | Cause |
|--------|------|---------|-------|
| 400 | Bad Request | "El endpoint debe ser de un servicio push conocido" | Unknown push service provider |
| 401 | Unauthorized | "Unauthenticado" | Not logged in |
| 403 | Forbidden | "No verificado" | Email not verified |
| 422 | Unprocessable Entity | Validation errors with field details | Missing/invalid data |
| 429 | Too Many Requests | "Too many requests" | Rate limit exceeded |
| 500 | Server Error | "Error al procesar la suscripción" | Server error |

---

### 2. Unsubscribe from Push Notifications

Removes a push notification subscription for the authenticated user.

**Request**:

```http
DELETE /api/push-subscriptions
Content-Type: application/json
Authorization: Bearer {token}

{
  "endpoint": "https://fcm.googleapis.com/fcm/send/example..."
}
```

**Parameters**:

| Name | Type | Required | Description |
|------|------|----------|-------------|
| `endpoint` | string | Yes | Push service endpoint URL to unsubscribe |

**Success Response** (200 OK):

```json
{
  "message": "Notificaciones desactivadas"
}
```

**Error Responses**:

| Status | Code | Message | Cause |
|--------|------|---------|-------|
| 401 | Unauthorized | "Unauthenticado" | Not logged in |
| 403 | Forbidden | "No verificado" | Email not verified |
| 404 | Not Found | "Suscripción no encontrada" | Endpoint not subscribed |
| 422 | Unprocessable Entity | Validation errors | Invalid endpoint format |
| 429 | Too Many Requests | "Too many requests" | Rate limit exceeded |
| 500 | Server Error | "Error al eliminar suscripción" | Server error |

---

## Code Examples

### TypeScript/React (Recommended)

Using the `usePushNotifications` hook (see [Frontend Guide](../frontend/pwa-push-notifications.md)):

```typescript
import { usePushNotifications } from '@/hooks/use-push-notifications'

export function NotificationSettings() {
  const { isSubscribed, subscribe, unsubscribe, isLoading } = usePushNotifications()

  return (
    <button
      onClick={isSubscribed ? unsubscribe : subscribe}
      disabled={isLoading}
    >
      {isSubscribed ? 'Desactivar notificaciones' : 'Activar notificaciones'}
    </button>
  )
}
```

### Direct API Calls (JavaScript)

```javascript
// Subscribe to push notifications
async function subscribeToPush() {
  const registration = await navigator.serviceWorker.ready
  const subscription = await registration.pushManager.subscribe({
    userVisibleOnly: true,
    applicationServerKey: urlBase64ToUint8Array(vapidPublicKey)
  })

  const response = await fetch('/api/push-subscriptions', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
    },
    body: JSON.stringify({
      endpoint: subscription.endpoint,
      publicKey: btoa(String.fromCharCode(...new Uint8Array(subscription.getKey('p256dh')))),
      authToken: btoa(String.fromCharCode(...new Uint8Array(subscription.getKey('auth'))))
    })
  })

  if (!response.ok) throw new Error(`Failed to subscribe: ${response.status}`)
  return response.json()
}

// Unsubscribe from push notifications
async function unsubscribeFromPush(endpoint) {
  const response = await fetch('/api/push-subscriptions', {
    method: 'DELETE',
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
    },
    body: JSON.stringify({ endpoint })
  })

  if (!response.ok) throw new Error(`Failed to unsubscribe: ${response.status}`)
  return response.json()
}
```

### Python

```python
import requests
import json

BASE_URL = "https://qrmade.example.com/api"
HEADERS = {
    "Authorization": f"Bearer {your_token}",
    "Content-Type": "application/json"
}

# Subscribe
def subscribe_to_push(endpoint, public_key, auth_token):
    response = requests.post(
        f"{BASE_URL}/push-subscriptions",
        headers=HEADERS,
        json={
            "endpoint": endpoint,
            "publicKey": public_key,
            "authToken": auth_token
        }
    )
    return response.json()

# Unsubscribe
def unsubscribe_from_push(endpoint):
    response = requests.delete(
        f"{BASE_URL}/push-subscriptions",
        headers=HEADERS,
        json={"endpoint": endpoint}
    )
    return response.json()
```

### cURL

```bash
# Subscribe
curl -X POST https://qrmade.example.com/api/push-subscriptions \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -d '{
    "endpoint": "https://fcm.googleapis.com/fcm/send/...",
    "publicKey": "BIp4+SJq...",
    "authToken": "k7xb8vfg3h..."
  }'

# Unsubscribe
curl -X DELETE https://qrmade.example.com/api/push-subscriptions \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -d '{
    "endpoint": "https://fcm.googleapis.com/fcm/send/..."
  }'
```

---

## Push Notification Payload Format

When a transaction occurs, the server sends a push notification with the following format:

```json
{
  "title": "Tu tarjeta de regalo",
  "body": "Se realizó un cargo de $50.00. Saldo: $250.00",
  "icon": "/icons/icon-192x192.png",
  "badge": "/favicon.svg",
  "url": "/dashboard"
}
```

### Payload Fields

| Field | Type | Description |
|-------|------|-------------|
| `title` | string | "Tu tarjeta de regalo" (gift card title) |
| `body` | string | Transaction details (type, amount, new balance) |
| `icon` | string | Large notification icon (192x192 PNG) |
| `badge` | string | Small badge for Android (SVG) |
| `url` | string | Navigation target when clicked (always `/dashboard`) |

### Transaction Types and Notification Messages

| Type | Message Pattern |
|------|-----------------|
| **Debit** | "Se realizó un cargo de $XX.XX. Saldo: $YY.YY" |
| **Credit** | "Se abonó $XX.XX a tu tarjeta. Saldo: $YY.YY" |
| **Adjustment** | "Se realizó un ajuste de $XX.XX. Saldo: $YY.YY" |

---

## Error Handling

### Validation Errors

```json
{
  "message": "The given data was invalid.",
  "errors": {
    "endpoint": [
      "El campo endpoint es requerido.",
      "El endpoint debe ser una URL válida."
    ],
    "publicKey": [
      "El campo publicKey no debe exceder 500 caracteres."
    ]
  }
}
```

### Retry Strategy

Implement exponential backoff for network errors:

```typescript
async function subscribeWithRetry(
  endpoint: string,
  publicKey: string,
  authToken: string,
  maxRetries = 3
) {
  for (let i = 0; i < maxRetries; i++) {
    try {
      const response = await fetch('/api/push-subscriptions', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ endpoint, publicKey, authToken })
      })

      if (response.status === 429) {
        // Rate limited - wait before retry
        const retryAfter = parseInt(response.headers.get('RateLimit-Reset') || '60')
        await new Promise(resolve => setTimeout(resolve, retryAfter * 1000))
        continue
      }

      if (!response.ok) throw new Error(`HTTP ${response.status}`)

      return response.json()
    } catch (error) {
      const waitTime = Math.pow(2, i) * 1000 // 1s, 2s, 4s
      if (i < maxRetries - 1) {
        await new Promise(resolve => setTimeout(resolve, waitTime))
      } else {
        throw error
      }
    }
  }
}
```

---

## Best Practices

### 1. Always Verify Authentication

Before subscribing, verify the user is authenticated with a verified email:

```typescript
// Frontend
const user = usePage().props.auth.user
if (!user?.email_verified_at) {
  throw new Error('Please verify your email first')
}
```

### 2. Handle Permission Denial Gracefully

Users can deny notification permission. Respect their choice:

```typescript
if (Notification.permission === 'denied') {
  console.log('User denied notification permission')
  // Don't retry - show a message instead
  return
}
```

### 3. Store Subscriptions Across Devices

A user can have multiple subscriptions (phone, tablet, desktop):

```typescript
// This is automatic - just subscribe each device independently
// The API handles duplicates via unique(user_id, endpoint)
```

### 4. Handle Subscription Expiry

Push subscriptions can expire. Implement cleanup:

```typescript
// Check subscription status before use
const subscription = await serviceWorker.pushManager.getSubscription()
if (!subscription) {
  // Subscription expired - prompt to re-subscribe
  await subscribe()
}
```

### 5. Security: Never Expose Private Keys

```typescript
// ❌ DON'T - Private key should never be in frontend code
const privateKey = import.meta.env.VITE_VAPID_PRIVATE_KEY // NOT AVAILABLE

// ✅ DO - Only public key is available
const publicKey = import.meta.env.VITE_VAPID_PUBLIC_KEY
```

### 6. Monitor Rate Limits

Track remaining requests to avoid hitting limits:

```typescript
function handleRateLimit(response) {
  const remaining = parseInt(response.headers.get('RateLimit-Remaining') || '0')
  const reset = parseInt(response.headers.get('RateLimit-Reset') || '0')

  if (remaining === 0) {
    const waitSeconds = reset - Math.floor(Date.now() / 1000)
    console.warn(`Rate limited. Wait ${waitSeconds} seconds`)
  }
}
```

### 7. Log User Actions

Use activity logs to track subscription changes:

```php
// Backend automatically logs via activity()->log()
// Query with:
activity()
    ->where('description', 'Push notification subscribed')
    ->where('causer_id', $user->id)
    ->latest()
    ->get()
```

---

## Troubleshooting

### "Notificaciones activadas" but not receiving notifications

1. Verify Service Worker is registered: Open DevTools → Application → Service Workers
2. Check `push_subscriptions` table: `SELECT * FROM push_subscriptions WHERE user_id = X`
3. Verify queue worker is running: `php artisan queue:work`
4. Check HTTPS is enabled: `https://` required for production

### "El endpoint debe ser de un servicio push conocido"

The push service provider is not recognized. Supported providers:
- Google Firebase Cloud Messaging (FCM)
- Mozilla Push Service
- Apple Push Notification Service (APNs)

### "Too many requests"

Wait for the time specified in `RateLimit-Reset` header, then retry.

### Permission denied in browser

User explicitly denied notification permission. To reset:
- **Chrome**: Settings → Notifications → Find site → Remove
- **Firefox**: Preferences → Privacy → Permissions → Notifications → Remove
- **Safari**: Settings → Notifications → Find site → Allow

---

**Document Version**: 1.0
**Last Updated**: 2026-02-08
**Author**: Documentation Specialist
**Next**: See [Backend Integration Guide](../backend/push-notification-integration.md)
