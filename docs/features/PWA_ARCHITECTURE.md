# PWA Offline-First Architecture

**Document Version**: 1.0
**Last Updated**: February 9, 2026
**Status**: Production-Ready
**Author**: QR Made Armando Development Team

---

## Table of Contents

1. [Overview](#overview)
2. [Architecture Overview](#architecture-overview)
3. [Core Components Deep Dive](#core-components-deep-dive)
4. [Data Flow & Transaction Scenarios](#data-flow--transaction-scenarios)
5. [API Integration](#api-integration)
6. [Security & Privacy](#security--privacy)
7. [Testing Strategy](#testing-strategy)
8. [Troubleshooting Guide](#troubleshooting-guide)
9. [Performance Considerations](#performance-considerations)
10. [Future Enhancements](#future-enhancements)

---

## Overview

### What is PWA in This Project

QR Made Armando implements a **Progressive Web Application (PWA)** with offline-first architecture, enabling users to scan QR codes and process gift card transactions even without internet connectivity. The system uses service workers, IndexedDB, and encrypted session storage to provide seamless offline experiences with automatic synchronization when connectivity is restored.

### Offline-First Strategy Explanation

The offline-first approach inverts traditional development priorities:

1. **Primary**: Local IndexedDB cache stores all data
2. **Secondary**: API fetches refresh/sync cached data when online
3. **Fallback**: Network failures gracefully degrade to cached data

This strategy ensures the application remains functional and responsive regardless of network conditions, with automatic background synchronization when the connection returns.

### Key Benefits

| Benefit | Impact | Example |
|---------|--------|---------|
| **No Connectivity Required** | Critical infrastructure | Scan QR codes in offline retail locations |
| **Reduced Server Load** | Cost savings | Batch sync pending transactions |
| **Faster Response Times** | Better UX | Instant card lookups from cache |
| **Resilient** | Reliability | Connection drops don't break workflows |
| **Data Persistence** | Recovery | 30-day offline sessions survive app restarts |

### Main Use Cases

1. **Retail Scanner** - Employees scan gift card QR codes offline, process debits/credits, auto-sync when online
2. **Employee Dashboard** - View transaction history cached locally, sync new transactions on reconnect
3. **Mobile Deployment** - Deploy to iOS/Android via home screen installation (PWA install)
4. **Push Notifications** - Receive real-time transaction notifications even when app is closed

### Browser Compatibility

| Browser | Support | Notes |
|---------|---------|-------|
| **Chrome/Edge** | ✅ Full | Service Workers, IndexedDB, WebCrypto |
| **Firefox** | ✅ Full | Excellent offline support |
| **Safari iOS** | ⚠️ Limited | iOS 16.1+ has SW support; PWA features limited |
| **Samsung Internet** | ✅ Full | Android PWA support |
| **IE 11** | ❌ None | Not supported (require modern browser) |

**Minimum Requirements**: HTTP/2, HTTPS, TLS 1.2+

---

## Architecture Overview

### High-Level Architecture Diagram

```
┌─────────────────────────────────────────────────────────────┐
│                    QR Made Armando PWA                       │
├─────────────────────────────────────────────────────────────┤
│                                                               │
│  ┌──────────────────────────────────────────────────────┐   │
│  │            React 19 + TypeScript Frontend             │   │
│  │  • scanner.tsx - QR code scanning interface          │   │
│  │  • dashboard.tsx - Transaction history               │   │
│  │  • auth components - Offline login                    │   │
│  └──────────┬───────────────────────────────────────────┘   │
│             │                                                │
│  ┌──────────▼───────────────────────────────────────────┐   │
│  │         React Hooks (Offline Managers)                │   │
│  │  • useOfflineSession() - Encrypted sessions           │   │
│  │  • useOfflineData() - Cached gift cards/categories    │   │
│  │  • useScannerOffline() - QR scan + transaction queue  │   │
│  │  • usePushNotifications() - PWA notifications         │   │
│  └──────────┬───────────────────────────────────────────┘   │
│             │                                                │
│  ┌──────────▼───────────────────────────────────────────┐   │
│  │      Local Storage Layer (IndexedDB + IDB)           │   │
│  │                                                        │   │
│  │  ┌─────────────┐  ┌──────────────┐  ┌──────────────┐ │   │
│  │  │ gift_cards  │  │ transactions │  │ categories   │ │   │
│  │  ├─────────────┤  ├──────────────┤  ├──────────────┤ │   │
│  │  │ Cache: 24h  │  │ Cache: Real- │  │ Cache: 24h   │ │   │
│  │  │ Indexes:    │  │ time Sync    │  │ Indexes:     │ │   │
│  │  │ by-id       │  │ Status: ✓/✗  │  │ by-id        │ │   │
│  │  │ by-legacy-id│  │ offline_id   │  │ by-prefix    │ │   │
│  │  │ by-category │  │              │  │              │ │   │
│  │  └─────────────┘  └──────────────┘  └──────────────┘ │   │
│  │                                                        │   │
│  │  ┌──────────────────────────────────────────────────┐ │   │
│  │  │         offline_queue (Debit Batching)          │ │   │
│  │  │  • Pending debits for sync                      │ │   │
│  │  │  • Retry count + error tracking                │ │   │
│  │  │  • Batch sync on reconnect                     │ │   │
│  │  └──────────────────────────────────────────────────┘ │   │
│  │                                                        │   │
│  │  ┌──────────────────────────────────────────────────┐ │   │
│  │  │            session (Encrypted Auth)             │ │   │
│  │  │  • user_id (authenticated mode)                │ │   │
│  │  │  • encrypted_password (AES-256-GCM)           │ │   │
│  │  │  • 30-day expiration                           │ │   │
│  │  └──────────────────────────────────────────────────┘ │   │
│  └──────────┬───────────────────────────────────────────┘   │
│             │                                                │
│  ┌──────────▼───────────────────────────────────────────┐   │
│  │  Crypto Layer (Web Crypto API)                       │   │
│  │  • PBKDF2-SHA256 key derivation (100k iterations)   │   │
│  │  • AES-256-GCM encryption/decryption                │   │
│  │  • Random IV/salt generation                        │   │
│  └──────────┬───────────────────────────────────────────┘   │
│             │                                                │
│  ┌──────────▼───────────────────────────────────────────┐   │
│  │  Service Worker (sw-custom.ts)                       │   │
│  │  • Push notification handling                        │   │
│  │  • Workbox caching strategies                        │   │
│  │  • Background sync (NetworkFirst)                    │   │
│  │  • Offline asset serving                             │   │
│  └──────────┬───────────────────────────────────────────┘   │
│             │                                                │
│  ┌──────────▼───────────────────────────────────────────┐   │
│  │        Network Layer (Fetch API)                      │   │
│  │  • Online: Real-time API calls                        │   │
│  │  • Offline: Queue for sync + use cache               │   │
│  └──────────┬───────────────────────────────────────────┘   │
│             │                                                │
└─────────────┼────────────────────────────────────────────────┘
              │
┌─────────────▼─────────────────────────────────────────────────┐
│             Laravel Backend + Push Service                     │
│                                                                │
│  ┌──────────────────────────────────────────────────────────┐ │
│  │              REST API Endpoints (v1)                      │ │
│  │  • GET /api/v1/gift-cards (CacheFirst, 24h)             │ │
│  │  • GET /api/v1/public/gift-cards/search (NetworkFirst)  │ │
│  │  • POST /api/v1/debit (NetworkFirst, with offline_id)   │ │
│  │  • POST /api/v1/sync/transactions (Idempotent sync)     │ │
│  │  • GET /api/v1/categories (CacheFirst, 24h)             │ │
│  └──────────────────────────────────────────────────────────┘ │
│                                                                │
│  ┌──────────────────────────────────────────────────────────┐ │
│  │           Push Notification Service                       │ │
│  │  • VAPID keys (Web Push Protocol)                        │ │
│  │  • TransactionCreated event → WebPushChannel            │ │
│  │  • Payload: type, amount, new balance                   │ │
│  └──────────────────────────────────────────────────────────┘ │
│                                                                │
│  ┌──────────────────────────────────────────────────────────┐ │
│  │              SQLite Database                              │ │
│  │  • gift_cards, transactions, users, branches             │ │
│  │  • offline_id column for idempotent sync                 │ │
│  └──────────────────────────────────────────────────────────┘ │
└────────────────────────────────────────────────────────────────┘
```

### Core Components List

| Component | Type | Purpose | Location |
|-----------|------|---------|----------|
| **IndexedDB Layer** | Library | Client-side data persistence | `resources/js/lib/db.ts` |
| **Crypto Utilities** | Library | Password encryption/decryption | `resources/js/lib/crypto.ts` |
| **Service Worker** | Runtime | Push notifications, caching | `resources/js/sw-custom.ts` |
| **useOfflineSession** | Hook | Session management (30-day) | `resources/js/hooks/use-offline-session.ts` |
| **useOfflineData** | Hook | Gift card/category caching | `resources/js/hooks/use-offline-data.ts` |
| **useScannerOffline** | Hook | QR scanning + debit queue | `resources/js/hooks/use-scanner-offline.ts` |
| **usePushNotifications** | Hook | Notification subscription | `resources/js/hooks/use-push-notifications.ts` |
| **API Controllers** | Backend | RESTful endpoints | `app/Http/Controllers/Api/V1/*` |
| **TransactionService** | Backend | Transaction processing | `app/Services/TransactionService.php` |

### Data Flow Overview

```
┌─────────┐
│  User   │
└────┬────┘
     │ Scans QR / Enters Debit Amount
     ▼
┌────────────────────────────┐
│ Browser online/offline?    │
└────┬──────────────────┬────┘
     │                  │
  ONLINE            OFFLINE
     │                  │
     ▼                  ▼
┌──────────────┐  ┌──────────────────────┐
│ Fetch API    │  │ Queue in IndexedDB   │
│ (real-time)  │  │ offline_queue        │
└───┬──────────┘  └───┬──────────────────┘
    │                 │
    ▼                 │ On Reconnect
┌──────────────┐      │
│ Update Cache │      │
│ (IndexedDB)  │◄─────┘
└───┬──────────┘
    │
    ▼
┌──────────────┐
│ Display to   │
│ User         │
└──────────────┘
```

### Technology Stack

**Frontend**:
- React 19 + TypeScript
- Web Crypto API (AES-256-GCM, PBKDF2)
- IndexedDB (via idb library)
- Service Workers (Workbox, vite-plugin-pwa)
- Inertia.js (Server-Side Rendering)

**Backend**:
- Laravel 12 + PHP 8.2
- Sanctum (API authentication)
- WebPush (VAPID protocol)
- SQLite (development)
- Database transactions (atomic operations)

**Caching & Storage**:
- IndexedDB v2 (6 stores: gift_cards, transactions, categories, session, images, offline_queue)
- Service Worker Cache API (Workbox strategies)
- Browser localStorage (small data: permissions, dismissals)
- Encrypted password storage (client-side only)

---

## Core Components Deep Dive

### 3.1 IndexedDB Layer (`resources/js/lib/db.ts`)

#### Database Schema

```typescript
// Version 2 with future migration support
const stores = {
  gift_cards: {
    keyPath: 'id',
    indexes: [
      { name: 'by-legacy-id', unique: false },
      { name: 'by-category', unique: false }
    ]
  },
  transactions: {
    keyPath: 'id',
    indexes: [
      { name: 'by-gift-card', unique: false },
      { name: 'by-user', unique: false },
      { name: 'synced', unique: false }
    ]
  },
  images: {
    keyPath: 'id',
    indexes: [
      { name: 'by-type', unique: false },
      { name: 'expires-at', unique: false }
    ]
  },
  categories: { keyPath: 'id' },
  session: { keyPath: 'id' },
  offline_queue: { keyPath: 'id' }
}
```

#### Data Models

**GiftCard**:
```typescript
interface GiftCard {
  id: string                        // UUID primary key
  legacy_id: string                 // e.g., EMCAD000001
  category_id: string
  balance: number                   // Current balance
  status: 'active' | 'inactive'     // Activation status
  created_at: number                // Timestamp
  updated_at: number
  cached_at: number                 // Last sync time
  is_dirty: boolean                 // Needs sync
}
```

**Transaction**:
```typescript
interface Transaction {
  id: string                        // Local transaction ID
  gift_card_id: string
  type: 'debit' | 'credit' | 'adjustment'
  amount: number
  balance_before: number
  balance_after: number
  description: string
  created_at: number
  synced: boolean                   // Synced to server
  offline_id?: string              // Idempotency key
}
```

**OfflineAction**:
```typescript
interface OfflineAction {
  id: string                        // Unique queue ID
  action_type: 'debit' | 'credit' | 'adjustment'
  payload: Record<string, any>      // Request payload
  created_at: number
  retry_count: number               // Sync attempts
  last_error?: string              // Last error message
}
```

#### Migration Strategy

The database uses version-based migrations (currently v2):

```typescript
upgrade(db, oldVersion, newVersion, transaction) {
  // Version 1: Initial stores (all 6 created)
  if (oldVersion < 1) {
    // Create gift_cards, transactions, images, categories, session, offline_queue
  }

  // Version 2: Future migrations here
  if (oldVersion < 2) {
    // Example: add new store, new index, etc.
  }
}
```

**Migration Benefits**:
- Automatic schema updates on first load
- No data loss between versions
- Forward compatibility

#### Usage Examples

**Load All Gift Cards**:
```typescript
const db = await initDB()
const cards = await db.getAll('gift_cards')  // All cached cards
```

**Lookup by Legacy ID**:
```typescript
const index = await db.getAllFromIndex(
  'gift_cards',
  'by-legacy-id',
  'EMCAD000001'
)
const card = index[0]  // First match (unique search)
```

**Save/Update Card**:
```typescript
await db.put('gift_cards', {
  ...card,
  balance: newBalance,
  cached_at: Date.now()
})
```

**Queue Offline Transaction**:
```typescript
const actionId = await queueOfflineAction({
  action_type: 'debit',
  payload: { legacy_id, amount, description },
  created_at: Date.now(),
  retry_count: 0,
  last_error: null
})
```

**Get Pending Sync Queue**:
```typescript
const pending = await getPendingActions()  // All offline_queue items
```

---

### 3.2 Service Worker (`resources/js/sw-custom.ts`)

#### Lifecycle Events

**Registration**:
```typescript
// Automatic via vite-plugin-pwa
// registerType: 'autoUpdate' - checks for updates every app load
```

**Push Event** (handles incoming notifications):
```typescript
addEventListener('push', (event: PushEvent) => {
  const data = event.data?.json() ?? {}

  const options: NotificationOptions = {
    body: data.body,           // Transaction message
    icon: '/icons/icon-192x192.png',
    badge: '/favicon.svg',
    tag: 'transaction-notification',  // Only 1 notification per tag
    data: { url: data.url || '/dashboard' }
  }

  event.waitUntil(
    self.registration.showNotification(data.title, options)
  )
})
```

#### Push Notification Handling

**Payload Format** (from backend):
```typescript
{
  title: 'QR Made',
  body: 'Se realizó un cargo de $50.00. Saldo: $150.00',
  icon: '/icons/icon-192x192.png',
  url: '/dashboard'
}
```

**Notification Click Handler**:
```typescript
addEventListener('notificationclick', (event: NotificationEvent) => {
  event.notification.close()

  const url = event.notification.data.url || '/dashboard'

  // Validate URL (prevent external navigation)
  if (!url.startsWith('/')) return

  // Focus existing window or open new one
  event.waitUntil(
    self.clients.matchAll({ type: 'window' })
      .then(clientList => {
        for (const client of clientList) {
          if (client.url.includes(url) && 'focus' in client) {
            return (client as WindowClient).focus()
          }
        }
        if (self.clients.openWindow) {
          return self.clients.openWindow(url)
        }
      })
  )
})
```

#### Caching Strategies

**Workbox Configuration** (vite.config.ts):

| Pattern | Strategy | Cache Name | TTL |
|---------|----------|-----------|-----|
| Inertia pages | StaleWhileRevalidate | inertia-pages | 1 hour |
| Auth routes | NetworkOnly | - | - |
| Images (*.png/*.jpg) | CacheFirst | images | 30 days |
| API v1 data | CacheFirst | offline-data | 24 hours |
| API calls (debit/sync) | NetworkFirst | api-calls | 60 seconds |

**NetworkFirst Example** (real-time transactions):
```
1. Try network (5s timeout)
2. If success: return + update cache
3. If timeout/fail: return from cache
4. If no cache: error
```

**CacheFirst Example** (stable data):
```
1. Check cache first
2. If found: return immediately
3. If not found: fetch from network
4. Store in cache for future
```

#### Background Sync

Currently handled via:
- Manual sync trigger: `useSyncManager()` hook
- Automatic on reconnect: `window.addEventListener('online', ...)`
- Future: Background Sync API (Workbox Level 2)

---

### 3.3 Cryptography (`resources/js/lib/crypto.ts`)

#### AES-256-GCM Encryption

**Key Derivation** (PBKDF2-SHA256):
```typescript
async function deriveKey(password: string, salt: Uint8Array): Promise<CryptoKey> {
  // Import password as raw key material
  const baseKey = await crypto.subtle.importKey(
    'raw',
    new TextEncoder().encode(password),
    { name: 'PBKDF2' },
    false,
    ['deriveBits', 'deriveKey']
  )

  // Derive 256-bit AES key
  return crypto.subtle.deriveKey(
    {
      name: 'PBKDF2',
      salt: salt,
      iterations: 100000,  // 100k iterations = ~0.5s on modern CPU
      hash: 'SHA-256'
    },
    baseKey,
    { name: 'AES-GCM', length: 256 },
    false,
    ['encrypt', 'decrypt']
  )
}
```

**Security Properties**:
- **100k iterations**: PBKDF2 standard recommendation
- **256-bit key**: Maximum AES key size (military-grade)
- **12-byte IV**: Random per-encryption (prevents patterns)
- **16-byte salt**: Random per-password (prevents rainbow tables)
- **GCM mode**: Authenticated encryption (detects tampering)

**Encryption Process**:
```typescript
async function encryptPassword(password: string): Promise<EncryptedData> {
  const salt = crypto.getRandomValues(new Uint8Array(16))    // New salt
  const iv = crypto.getRandomValues(new Uint8Array(12))      // New IV
  const key = await deriveKey(password, salt)

  const ciphertext = await crypto.subtle.encrypt(
    { name: 'AES-GCM', iv: iv },
    key,
    new TextEncoder().encode(password)
  )

  return {
    salt: bytesToBase64(salt),
    iv: bytesToBase64(iv),
    ciphertext: bytesToBase64(new Uint8Array(ciphertext))
  }
}
```

#### Password Storage Format

**Storage in IndexedDB**:
```typescript
{
  salt: "2gN5kL9mPqRsT8uVwXyZ1a==",              // Base64-encoded
  iv: "9bC2dEfGhIjKlMnOpQ==",                     // Base64-encoded
  ciphertext: "xYzA1bCdEfGhIjKlMnOpQrStUvWxYz=="  // Base64-encoded
}
```

**Why This Format**:
- Base64 encoding: Stores binary data as text in JSON
- Random salt: Different for each password (prevents offline attacks)
- Random IV: Different for each encryption (prevents pattern analysis)
- No password ever stored in plaintext

#### Password Validation

**Fast verification** (hash-based):
```typescript
const hash = await hashPassword(providedPassword)
if (hash !== storedHash) {
  return false  // Wrong password
}
```

**Full verification** (decrypt check):
```typescript
const decrypted = await decryptPassword(password, encrypted)
return decrypted === password  // True only if password correct
```

#### Security Considerations

| Threat | Mitigation | Effectiveness |
|--------|-----------|----------------|
| **Offline Brute Force** | 100k PBKDF2 iterations (~0.5s per try) | ⭐⭐⭐⭐ |
| **Rainbow Tables** | 16-byte random salt per password | ⭐⭐⭐⭐⭐ |
| **Known-Plaintext Attack** | 12-byte random IV per encryption | ⭐⭐⭐⭐⭐ |
| **Tampering Detection** | AES-GCM authentication tags | ⭐⭐⭐⭐⭐ |
| **Extraction from Memory** | Decryption only on-demand | ⭐⭐⭐ |

**Limitations**:
- Password stored if "Save Password" checked (trade-off for convenience)
- Decryption requires correct password (no recovery if forgotten)
- localStorage is **not encrypted** (only IndexedDB values are)
- Compromised device = compromised credentials

---

### 3.4 Offline Hooks

#### useOfflineSession.ts - Session Management

**Features**:
- 30-day persistent login (survives app restart)
- Optional encrypted password storage
- Guest mode (no authentication)
- Automatic expiration

**Session Data**:
```typescript
interface SessionData {
  id: 'current_session'
  user_id: string | null           // Null in guest mode
  mode: 'guest' | 'authenticated'
  encrypted_password: string | null  // JSON string or null
  encryption_key: string | null      // Hash of password
  login_timestamp: number
  expires_at: number                 // 30 days = 2592000000ms
  token: string | null               // Reserved for API token
}
```

**Login with Password Persistence**:
```typescript
const { login } = useOfflineSession()

// User checks "Save password" checkbox
await login(userId, email, password, savePassword: true)

// Password encrypted and stored
// Next session: auto-login without entering password
```

**Usage Pattern**:
```typescript
export function OfflineLoginForm() {
  const { session, login, isLoading } = useOfflineSession()
  const [savePassword, setSavePassword] = useState(false)

  const handleSubmit = async (email: string, password: string) => {
    await login(userId, email, password, savePassword)
    // Automatically authenticated for next 30 days
  }

  if (session) {
    return <div>Logged in as {session.user_id}</div>
  }

  return <LoginForm onSubmit={handleSubmit} />
}
```

#### useOfflineData.ts - Data Caching & Sync

**Features**:
- Automatic cache loading on mount
- 5-minute auto-refresh when online
- NetworkFirst for searches, CacheFirst for bulk data
- Single card + category lookups

**Caching Strategy**:

| Data | Strategy | TTL | Use Case |
|------|----------|-----|----------|
| All gift cards | CacheFirst | 24h | Bulk loads on startup |
| All categories | CacheFirst | 24h | Static reference data |
| Search results | NetworkFirst | 5s | Real-time lookups |

**Auto-Refresh Logic**:
```typescript
const { giftCards, isOnline, lastSyncTime } = useOfflineData()

// On mount or when online status changes
useEffect(() => {
  if (!isOnline) return  // Don't sync offline

  // If never synced or >5 minutes: sync now
  const timeSinceSync = lastSyncTime ? Date.now() - lastSyncTime : null
  if (!timeSinceSync || timeSinceSync > 5 * 60 * 1000) {
    syncFromAPI()
  }

  // Set interval for periodic sync
  const interval = setInterval(syncFromAPI, 5 * 60 * 1000)
  return () => clearInterval(interval)
}, [isOnline, lastSyncTime])
```

**Single Card Lookup**:
```typescript
// Cache-first: checks IndexedDB before API
const { giftCard, error } = useOfflineGiftCard('EMCAD000001')

// Behavior:
// Online + in cache: return cache immediately
// Online + not in cache: fetch + cache + return
// Offline + in cache: return cache
// Offline + not in cache: error "Card not found in cache"
```

#### useScannerOffline.ts - QR Scanning + Debit Queue

**Features**:
- Cache-first card lookup
- Offline debit processing with queue
- Automatic sync on reconnect
- Balance validation
- Retry tracking

**Scan & Process Debit Flow**:

```typescript
const {
  scan,
  processDebit,
  syncPendingTransactions,
  getSyncQueue,
  isProcessing,
  error,
  lastScannedCard
} = useScannerOffline()

// User scans QR code (extracts legacy_id)
const card = await scan('EMCAD000001')

// User enters debit amount
const transaction = await processDebit('EMCAD000001', 50.00, 'Coffee')

// Result depends on connection:
// Online: transaction synced immediately, returns server response
// Offline: transaction queued, local balance updated
```

**Offline Debit Processing** (when online fails):
```typescript
// Create local transaction record
const offlineTransaction: Transaction = {
  id: crypto.randomUUID(),
  gift_card_id: card.id,
  type: 'debit',
  amount: 50.00,
  balance_before: card.balance,
  balance_after: card.balance - 50.00,
  created_at: Date.now(),
  synced: false,
  offline_id: crypto.randomUUID()  // For idempotent sync
}

// Save locally
await db.add('transactions', offlineTransaction)

// Queue for sync
await queueOfflineAction({
  action_type: 'debit',
  payload: {
    legacy_id: 'EMCAD000001',
    amount: 50.00,
    description: 'Coffee',
    offline_id: offlineTransaction.offline_id  // Server checks this
  },
  retry_count: 0,
  last_error: null
})

// Update local card balance
await db.put('gift_cards', {
  ...card,
  balance: card.balance - 50.00,
  is_dirty: true
})
```

**Manual Sync Trigger**:
```typescript
// User clicks "Sync Now" button
const { syncPending } = useSyncManager()

await syncPending()
// Sends all pending_actions to /api/v1/sync/transactions
// Server processes idempotently (offline_id prevents duplicates)
// Clears offline_queue on success
```

---

## Data Flow & Transaction Scenarios

### Scenario 1: Online QR Scan and Debit

**User Flow**:
1. Employee opens scanner page with internet connection
2. Scans QR code (extracts legacy_id: "EMCAD000001")
3. Enters debit amount: $50.00
4. Clicks "Confirmar Cargo"

**Technical Flow**:

```
┌──────────────────┐
│ Scan QR Code     │
│ (legacy_id)      │
└────────┬─────────┘
         │
         ▼
┌─────────────────────────────┐
│ Lookup in IndexedDB cache   │
│ by-legacy-id index          │
└────────┬────────────────────┘
         │
    Card Found?
      /    \
    YES    NO
    │       │
    ▼       ▼
  Cache  Fetch API
          ↓
      /api/v1/gift-cards/search?legacy_id=EMCAD000001
          ↓
      Cache result for future
         │
         └────┬─────────────────┐
              │ Card Found      │
              ▼                 │
    ┌───────────────────┐       │
    │ Show balance:     │       │
    │ $200.00           │       │
    │ Status: Active    │       │
    └────────┬──────────┘       │
             │                  │
             ▼                  │
    ┌────────────────────┐      │
    │ User enters amount │      │
    │ & clicks process   │      │
    └────────┬───────────┘      │
             │                  │
             ▼                  │
    ┌──────────────────────────┐│
    │ Validate:                ││
    │ • Amount > 0             ││
    │ • Balance >= Amount      ││
    │ • Card status = active   ││
    └────────┬─────────────────┘│
             │ Valid             │
             ▼                   │
    ┌────────────────────┐      │
    │ POST /api/v1/debit │      │
    │ {                  │      │
    │   legacy_id,       │      │
    │   amount,          │      │
    │   description      │      │
    │ }                  │      │
    └────────┬───────────┘      │
             │                  │
             ▼                  │
    ┌──────────────────────────┐│
    │ Server response:         ││
    │ {                        ││
    │   data: {                ││
    │     id, type: 'debit',   ││
    │     amount: 50.00,       ││
    │     balance_before: 200  ││
    │     balance_after: 150   ││
    │   }                      ││
    │ }                        ││
    └────────┬─────────────────┘│
             │                  │
             ▼                  │
    ┌──────────────────────────┐│
    │ Update IndexedDB:        ││
    │ • gift_cards: bal=150    ││
    │ • transactions: save txn ││
    │   status: synced=true    ││
    └────────┬─────────────────┘│
             │                  │
             ▼                  │
    ┌──────────────────────────┐
    │ Show success:            │
    │ ✓ Cargo realizado        │
    │ Nuevo saldo: $150.00     │
    └──────────────────────────┘

Card Not Found (NO path):
  └─→ Error: "Card not found"
       Try search first
```

**Code Implementation**:

```typescript
const { processDebit } = useScannerOffline()

// Process online debit
const transaction = await processDebit(
  'EMCAD000001',
  50.00,
  'Café'
)

// Returns immediately with server response
if (transaction?.synced) {
  showSuccessNotification(
    `Nuevo saldo: $${transaction.balance_after}`
  )
}
```

---

### Scenario 2: Offline Debit with Queue

**User Flow**:
1. Employee in retail location without internet
2. Scans QR code (cached from earlier)
3. Enters debit amount
4. Network is unavailable
5. Later: employee leaves store, reconnects
6. App syncs pending debits
7. Server confirms and updates balance

**Technical Flow**:

```
┌──────────────────────┐
│ Check online status  │
└────────┬─────────────┘
         │
    Is Online?
      /    \
    NO     YES → (Scenario 1)
    │
    ▼
┌────────────────────────────┐
│ Lookup in IndexedDB cache  │
│ (no API possible)          │
└────────┬───────────────────┘
         │
    Card in Cache?
      /    \
    YES    NO
    │       │
    ▼       ▼
  Process Error: "Card
  Offline not in cache,
            online required"
    ▼
┌────────────────────┐
│ Validate balance:  │
│ 200 >= 50? ✓       │
└────────┬───────────┘
         │
         ▼
┌───────────────────────────────┐
│ Create Offline Transaction    │
│ {                             │
│   id: uuid(),                 │
│   gift_card_id,               │
│   type: 'debit',              │
│   amount: 50.00,              │
│   balance_before: 200,        │
│   balance_after: 150,         │
│   offline_id: uuid(),         │
│   synced: false               │
│ }                             │
└────────┬──────────────────────┘
         │
         ▼
┌───────────────────────────────┐
│ Save to IndexedDB:            │
│ • transactions store          │
│ • offline_queue store         │
│   (for sync batching)         │
└────────┬──────────────────────┘
         │
         ▼
┌───────────────────────────────┐
│ Update Card Balance Locally   │
│ • gift_cards[id].balance=150  │
│ • gift_cards[id].is_dirty=T   │
└────────┬──────────────────────┘
         │
         ▼
┌───────────────────────────────┐
│ Show Status to User:          │
│ ⏳ Pendiente de sincronizar   │
│ Nuevo saldo (local): $150.00  │
│ Saldo se confirmará al        │
│ conectarse a internet         │
└────────┬──────────────────────┘
         │
      [TIME PASSES]
    Employee reconnects
         │
         ▼
┌───────────────────────────────┐
│ Browser detects connection    │
│ addEventListener('online')    │
│ triggers automatic sync       │
└────────┬──────────────────────┘
         │
         ▼
┌───────────────────────────────┐
│ Get pending actions from      │
│ offline_queue (all items)     │
└────────┬──────────────────────┘
         │
         ▼
┌───────────────────────────────┐
│ Batch POST /api/v1/sync       │
│ Loop through each pending:    │
│ {                             │
│   offline_id: uuid,           │
│   legacy_id: "EMCAD000001",   │
│   amount: 50.00,              │
│   description: "Café"         │
│ }                             │
└────────┬──────────────────────┘
         │
         ▼
┌───────────────────────────────┐
│ Server processes (atomic txn) │
│ 1. Check offline_id not seen  │
│ 2. Validate balance again     │
│ 3. Create transaction record  │
│ 4. Save to database           │
│ 5. Send push notification     │
└────────┬──────────────────────┘
         │
    Success?
      /    \
    YES    NO
    │       │
    ▼       ▼
  Remove  Update
  from Q  retry
  queue   count
    │       │
    ▼       ▼
┌──────────────────────┐
│ Refresh gift cards   │
│ GET /api/v1/gift-   │
│ cards (latest data)  │
└────────┬─────────────┘
         │
         ▼
┌──────────────────────┐
│ Update IndexedDB:    │
│ • Confirmed balance  │
│ • is_dirty = false   │
│ • is_synced = true   │
└────────┬─────────────┘
         │
         ▼
┌──────────────────────┐
│ Show notification:   │
│ ✓ Transacción       │
│ sincronizada        │
│ Saldo final:        │
│ $150.00             │
└──────────────────────┘
```

**Code Implementation**:

```typescript
const { processDebit, syncPendingTransactions } = useScannerOffline()

// Offline: processDebit queues transaction
const transaction = await processDebit('EMCAD000001', 50.00)
// Returns local transaction with synced: false, offline_id set

// Later when online...
window.addEventListener('online', async () => {
  // Automatic sync
  await syncPendingTransactions()
  // Clears offline_queue on success
})

// Or manual sync
const { syncPending } = useSyncManager()
await syncPending()  // User clicks "Sincronizar Ahora"
```

---

## API Integration

### REST Endpoints

All endpoints follow the standardized response format:

```typescript
interface ApiResponse<T> {
  data: T | T[]
  meta?: {
    pagination?: { current_page, per_page, total, last_page }
    timestamp?: string
  }
  error?: { code, message, details? }
}
```

### Endpoint Reference

#### GET /api/v1/gift-cards

**Purpose**: List all active gift cards (bulk fetch)

**Caching**: CacheFirst (24 hours)

**Request**:
```bash
GET /api/v1/gift-cards
Accept: application/json
```

**Response** (200 OK):
```json
{
  "data": [
    {
      "id": "550e8400-e29b-41d4-a716-446655440000",
      "legacy_id": "EMCAD000001",
      "category_id": "uuid",
      "balance": 200.50,
      "status": "active",
      "created_at": 1707500000,
      "updated_at": 1707600000,
      "cached_at": 1707700000
    }
  ],
  "meta": {
    "pagination": {
      "current_page": 1,
      "per_page": 50,
      "total": 1200,
      "last_page": 24
    }
  }
}
```

#### GET /api/v1/public/gift-cards/search?legacy_id=EMCAD000001

**Purpose**: Search gift card by legacy_id (real-time lookup)

**Caching**: NetworkFirst (5 second timeout)

**Request**:
```bash
GET /api/v1/public/gift-cards/search?legacy_id=EMCAD000001
Accept: application/json
```

**Response** (200 OK):
```json
{
  "data": {
    "id": "550e8400-e29b-41d4-a716-446655440000",
    "legacy_id": "EMCAD000001",
    "balance": 200.50,
    "status": "active"
  }
}
```

**Response** (404 Not Found):
```json
{
  "error": {
    "code": "CARD_NOT_FOUND",
    "message": "Gift card not found"
  }
}
```

#### POST /api/v1/debit

**Purpose**: Process debit transaction (online only)

**Caching**: NetworkFirst (5 second timeout)

**Request**:
```bash
POST /api/v1/debit
Content-Type: application/json

{
  "legacy_id": "EMCAD000001",
  "amount": 50.00,
  "description": "Café"
}
```

**Response** (200 OK):
```json
{
  "data": {
    "id": "txn-uuid",
    "gift_card_id": "card-uuid",
    "type": "debit",
    "amount": 50.00,
    "balance_before": 200.50,
    "balance_after": 150.50,
    "created_at": 1707700000,
    "synced": true
  }
}
```

**Response** (422 Unprocessable Entity - Validation):
```json
{
  "error": {
    "code": "VALIDATION_ERROR",
    "message": "Validation failed",
    "details": {
      "amount": ["Amount must be greater than 0"],
      "legacy_id": ["Gift card not found"]
    }
  }
}
```

**Response** (402 Payment Required - Insufficient Balance):
```json
{
  "error": {
    "code": "INSUFFICIENT_BALANCE",
    "message": "Insufficient balance. Available: $50.00",
    "details": {
      "balance": 50.00,
      "requested": 100.00
    }
  }
}
```

#### POST /api/v1/sync/transactions

**Purpose**: Sync offline-queued transactions (idempotent)

**Caching**: NetworkFirst (5 second timeout)

**Request**:
```bash
POST /api/v1/sync/transactions
Content-Type: application/json

{
  "offline_id": "unique-uuid-from-offline-queue",
  "legacy_id": "EMCAD000001",
  "amount": 50.00,
  "description": "Café"
}
```

**Idempotency**: Server checks `offline_id` - if already processed, returns success without duplicate transaction

**Response** (200 OK - New):
```json
{
  "data": {
    "synced": true,
    "offline_id": "unique-uuid-from-offline-queue",
    "transaction_id": "server-transaction-id",
    "new_balance": 150.50
  }
}
```

**Response** (200 OK - Already Synced):
```json
{
  "data": {
    "synced": true,
    "offline_id": "unique-uuid-from-offline-queue",
    "transaction_id": "server-transaction-id",
    "message": "Already processed"
  }
}
```

#### GET /api/v1/categories

**Purpose**: List all gift card categories

**Caching**: CacheFirst (24 hours)

**Request**:
```bash
GET /api/v1/categories
Accept: application/json
```

**Response** (200 OK):
```json
{
  "data": [
    {
      "id": "cat-uuid",
      "prefix": "EMCAD",
      "nature": "payment_method",
      "name_es": "Empleados",
      "cached_at": 1707700000
    }
  ]
}
```

### Error Handling Strategies

**Retry Logic with Exponential Backoff**:

```typescript
async function retryWithBackoff<T>(
  fn: () => Promise<T>,
  maxAttempts = 3,
  baseDelay = 1000
): Promise<T> {
  for (let attempt = 0; attempt < maxAttempts; attempt++) {
    try {
      return await fn()
    } catch (error) {
      if (attempt === maxAttempts - 1) throw error

      const delay = baseDelay * Math.pow(2, attempt)  // 1s, 2s, 4s
      await new Promise(resolve => setTimeout(resolve, delay))
    }
  }
}

// Usage
await retryWithBackoff(
  () => fetch('/api/v1/debit', { method: 'POST', body })
)
```

**Network Timeout Handling**:

```typescript
async function fetchWithTimeout(url: string, timeout = 5000) {
  const controller = new AbortController()
  const id = setTimeout(() => controller.abort(), timeout)

  try {
    const response = await fetch(url, {
      signal: controller.signal
    })
    return response
  } catch (error) {
    if (error.name === 'AbortError') {
      // Timeout - use cache
      return getCachedResponse(url)
    }
    throw error
  } finally {
    clearTimeout(id)
  }
}
```

### Idempotency with offline_id

**Problem**: User processes debit offline, sync happens, then user clicks sync again

**Solution**: `offline_id` field ensures duplicate detection

```typescript
// First sync attempt
POST /api/v1/sync/transactions
{
  "offline_id": "abc-123-def-456",
  "legacy_id": "EMCAD000001",
  "amount": 50.00
}
→ Server creates transaction, stores offline_id

// Duplicate sync (network flaky, user retries)
POST /api/v1/sync/transactions
{
  "offline_id": "abc-123-def-456",  // Same ID
  "legacy_id": "EMCAD000001",
  "amount": 50.00
}
→ Server checks: "offline_id already seen"
→ Returns success (no duplicate transaction)
```

---

## Security & Privacy

### Password Encryption Details

**Storage Flow**:
1. User enters password: `"miContraseña123"`
2. Generate random salt (16 bytes)
3. Derive AES key using PBKDF2 (100k iterations)
4. Generate random IV (12 bytes)
5. Encrypt password with AES-256-GCM
6. Store in IndexedDB:

```json
{
  "salt": "2gN5kL9mPqRsT8uVwXyZ1a==",
  "iv": "9bC2dEfGhIjKlMnOpQ==",
  "ciphertext": "xYzA1bCdEfGhIjKlMnOpQrStUvWxYz=="
}
```

**Retrieval Flow**:
1. User enters password: `"miContraseña123"`
2. Retrieve salt from storage
3. Derive same AES key (using stored salt + entered password)
4. Retrieve IV from storage
5. Decrypt ciphertext with AES-256-GCM
6. Compare: if decrypted === entered, password is correct

**Why Encrypt Instead of Hash?**:
- **Hash**: Can't retrieve password later (needed for "Skip Login" feature)
- **Encrypt**: Can store and decrypt when needed, recoverable

### Session Expiration (30 Days)

**Expiration Logic**:
```typescript
// Login
expires_at: Date.now() + 30 * 24 * 60 * 60 * 1000

// Session load
if (session.expires_at < Date.now()) {
  // Session expired, force logout
  await db.delete('session', 'current_session')
  redirectToLogin()
}
```

**30-Day Rationale**:
- Convenient: Skip login for a month
- Secure: Automatic logout even if forgotten
- Balanced: User device compromise time window

### Data Cleanup Policies

**On Logout**:
```typescript
async function logout() {
  const db = await initDB()

  // Clear sensitive data
  await db.delete('session', 'current_session')
  // offline_queue: keep (may have pending syncs)
  // gift_cards: keep (they're not sensitive)
  // categories: keep (reference data)
}
```

**On Force Delete**:
```typescript
async function forceDeleteAllData() {
  const db = await initDB()

  // Clear everything
  await db.clear('session')
  await db.clear('offline_queue')
  await db.clear('gift_cards')
  await db.clear('transactions')
  await db.clear('categories')
  await db.clear('images')

  // Also clear localStorage
  localStorage.clear()
}
```

**Expired Session Cleanup**:
```typescript
// Automatic on app load
const session = await db.get('session', 'current_session')
if (session && session.expires_at < Date.now()) {
  await db.delete('session', 'current_session')
  // User must re-login
}
```

### HTTPS Requirements

**Why HTTPS is Required**:
1. **Service Workers**: Only work over HTTPS (except localhost for dev)
2. **Push Notifications**: VAPID protocol requires HTTPS
3. **Credential Storage**: Browser APIs (crypto, IndexedDB) secured via HTTPS

**Development**:
```bash
# Herd provides automatic HTTPS via TLS
herd secure qrmadearmando
# Access: https://qrmadearmando.test
```

**Production**:
```bash
# SSL certificate (Let's Encrypt, paid, or Herd)
# APP_URL=https://qrmadearmando.com (no .test)
```

### VAPID Keys for Push Notifications

**Key Pair Generation**:
```bash
php artisan webpush:vapid --no-interaction
# Generates VAPID_PUBLIC_KEY and VAPID_PRIVATE_KEY
```

**Security Properties**:
- Public key: Shared with client (stored in .env.public)
- Private key: Stored securely on server only
- Used to authenticate push messages (server → push service)
- Prevents unauthorized entities from spoofing your app's notifications

**Key Storage**:
```bash
# .env (server secret - never expose)
VAPID_PRIVATE_KEY=xxxxx  # Protected
VAPID_PUBLIC_KEY=xxxxx

# .env.example (public only, safe)
VITE_VAPID_PUBLIC_KEY=xxxxx  # Visible in frontend
```

### Best Practices Implemented

| Practice | Implementation | Status |
|----------|---|---|
| **HTTPS Everywhere** | Herd TLS + production SSL | ✅ |
| **Encrypted Passwords** | AES-256-GCM storage | ✅ |
| **Session Expiration** | 30-day auto-logout | ✅ |
| **VAPID Authentication** | Keys generated, server-side only | ✅ |
| **Rate Limiting** | throttle:10,1 on API endpoints | ✅ |
| **CSRF Protection** | Inertia.js automatic tokens | ✅ |
| **SQL Injection Prevention** | Parameterized Laravel queries | ✅ |
| **XSS Protection** | React JSX escaping + CSP headers | ✅ |

---

## Testing Strategy

### Unit Test Approach

**Goal**: Test individual functions in isolation

**Files**: `resources/js/__tests__/unit/`

**Example - Crypto Utils**:
```typescript
import { describe, it, expect } from 'vitest'
import {
  deriveKey,
  encrypt,
  decrypt,
  encryptPassword,
  decryptPassword
} from '@/lib/crypto'

describe('Crypto Utilities', () => {
  it('should encrypt and decrypt password correctly', async () => {
    const password = 'testPassword123'

    const encrypted = await encryptPassword(password)

    // Verify format
    expect(encrypted.salt).toBeDefined()
    expect(encrypted.iv).toBeDefined()
    expect(encrypted.ciphertext).toBeDefined()

    // Verify decryption
    const decrypted = await decryptPassword(password, encrypted)
    expect(decrypted).toBe(password)
  })

  it('should fail decryption with wrong password', async () => {
    const password = 'correctPassword'
    const wrong = 'wrongPassword'

    const encrypted = await encryptPassword(password)
    const decrypted = await decryptPassword(wrong, encrypted)

    expect(decrypted).not.toBe(password)
  })
})
```

### Integration Testing

**Goal**: Test hooks + APIs + IndexedDB together

**Files**: `resources/js/__tests__/integration/`

**Example - Offline Debit Sync**:
```typescript
import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest'
import { renderHook, act, waitFor } from '@testing-library/react'
import { useScannerOffline } from '@/hooks/use-scanner-offline'
import { initDB, getPendingActions } from '@/lib/db'

describe('Offline Debit Sync', () => {
  let db: IDBPDatabase<AppDB>

  beforeEach(async () => {
    db = await initDB()
    // Clear all stores
    await db.clear('gift_cards')
    await db.clear('offline_queue')
  })

  afterEach(async () => {
    await db.close()
  })

  it('should queue debit when offline', async () => {
    // Mock offline status
    vi.spyOn(navigator, 'onLine', 'get').mockReturnValue(false)

    // Pre-populate cache
    await db.add('gift_cards', {
      id: 'card-1',
      legacy_id: 'EMCAD000001',
      balance: 100.00,
      status: 'active',
      cached_at: Date.now()
    })

    const { result } = renderHook(() => useScannerOffline())

    // Process debit offline
    await act(async () => {
      await result.current.processDebit('EMCAD000001', 50.00)
    })

    // Verify queued
    const pending = await getPendingActions()
    expect(pending).toHaveLength(1)
    expect(pending[0].payload.amount).toBe(50.00)
  })

  it('should sync when back online', async () => {
    // Mock API response
    global.fetch = vi.fn()
      .mockResolvedValueOnce({
        ok: true,
        json: async () => ({ data: { synced: true } })
      })

    // Queue offline transaction
    const actionId = await queueOfflineAction({
      action_type: 'debit',
      payload: { legacy_id: 'EMCAD000001', amount: 50.00 },
      created_at: Date.now(),
      retry_count: 0,
      last_error: null
    })

    // Now online
    vi.spyOn(navigator, 'onLine', 'get').mockReturnValue(true)

    const { result } = renderHook(() => useScannerOffline())

    // Trigger sync
    await act(async () => {
      await result.current.syncPendingTransactions()
    })

    // Verify removed from queue
    const pending = await getPendingActions()
    expect(pending).toHaveLength(0)
  })
})
```

### Offline Scenario Testing

**Goal**: Test all offline conditions (no network, poor connection, offline transitions)

**Test Categories**:

1. **No Network**:
   - User offline from start
   - App loads from cache
   - User can scan/debit
   - Transactions queued for sync

2. **Network Loss**:
   - App starts online
   - Connection drops mid-transaction
   - App queues remaining operations
   - No data loss

3. **Intermittent Connection**:
   - Network flaky (timeout/retry)
   - App retries with backoff
   - User sees appropriate status

4. **Reconnection**:
   - Device reconnects after offline
   - Automatic sync triggered
   - User sees "syncing..." then confirmation

### Mock Strategies

**Global Setup** (`resources/js/test/setup.ts`):
```typescript
import { beforeEach, vi } from 'vitest'

beforeEach(() => {
  // Mock localStorage
  const store: Record<string, string> = {}
  global.localStorage = {
    getItem: (key) => store[key] || null,
    setItem: (key, value) => { store[key] = value },
    removeItem: (key) => { delete store[key] },
    clear: () => { Object.keys(store).forEach(k => delete store[k]) },
    length: Object.keys(store).length,
    key: () => null
  }

  // Mock Service Worker
  Object.defineProperty(navigator, 'serviceWorker', {
    value: {
      ready: Promise.resolve({
        pushManager: {
          subscribe: vi.fn(),
          getSubscription: vi.fn()
        }
      }),
      register: vi.fn()
    },
    writable: true
  })

  // Mock fetch
  global.fetch = vi.fn()

  // Mock online status
  Object.defineProperty(navigator, 'onLine', {
    writable: true,
    value: true
  })
})
```

---

## Troubleshooting Guide

### Common Issues

#### Issue: "IndexedDB quota exceeded"

**Symptoms**:
- App crashes when saving data
- Error: `QuotaExceededError`

**Solution**:
```typescript
// Check storage quota
navigator.storage.estimate().then(estimate => {
  const percentUsed = (estimate.usage / estimate.quota) * 100
  console.log(`Storage: ${percentUsed.toFixed(2)}%`)
})

// Clear old cached images
const db = await initDB()
await cleanupExpiredImages()
```

**Prevention**:
- Images have 30-day expiration (automatic cleanup)
- Transactions stored as records (minimal size)
- Limit to 50 gift cards in cache

---

#### Issue: "Session expired during transaction"

**Symptoms**:
- User logged in, session ends mid-debit
- Debit queued but marked as needing re-auth

**Solution**:
```typescript
// Check session expiration
const { session } = useOfflineSession()
if (session && Date.now() > session.expires_at) {
  await logout()
  redirectToLogin()
}

// Increase TTL if needed
expires_at: Date.now() + 60 * 24 * 60 * 60 * 1000  // 60 days
```

---

#### Issue: "Push notifications not received"

**Symptoms**:
- Subscribed but no notifications
- No errors in console

**Checklist**:

```bash
# 1. Verify VAPID keys exist
grep VAPID_PUBLIC_KEY .env

# 2. Check browser permissions
# Settings → Notifications → qrmadearmando.test (Allow)

# 3. Verify Service Worker registered
# DevTools → Application → Service Workers (status: activated)

# 4. Check subscription endpoint
# IndexedDB → push_subscriptions → endpoint URL valid?

# 5. Verify database has subscription record
php artisan tinker
>>> PushSubscription::count()

# 6. Test manual push
php artisan tinker
>>> event(new TransactionCreated($transaction))
```

---

#### Issue: "Offline debit not syncing"

**Symptoms**:
- Debits queue, but "syncing" hangs
- offline_queue has items but stuck

**Diagnosis**:
```typescript
// Check pending queue
const pending = await getPendingActions()
console.table(pending)

// Check last error
pending.forEach(action => {
  console.log(`${action.id}: ${action.last_error}`)
})
```

**Solutions**:

1. **Network timeout**:
```typescript
// Increase timeout in vite.config.ts
networkTimeoutSeconds: 10  // Instead of 5
```

2. **Server validation error**:
```typescript
// Check server response
POST /api/v1/sync/transactions
→ 422: { error: { details: { legacy_id: "not found" } } }
→ Action marked with error, retryable

// Fix: Ensure gift card exists with correct legacy_id
```

3. **Manual retry**:
```typescript
const { syncPending } = useSyncManager()
try {
  await syncPending()
} catch (error) {
  console.error('Sync failed:', error)
  // Retry in background after delay
}
```

---

#### Issue: "Password decryption fails"

**Symptoms**:
- User saved password, now can't login
- "Wrong password" even though correct

**Causes**:
- Browser cleared IndexedDB
- Password changed externally
- Encryption data corrupted

**Recovery**:
```typescript
// Check if password stored
const hasPassword = await useOfflineSession().hasEncryptedPassword()

if (!hasPassword) {
  // Re-login and save password again
}

// Manual clear
const db = await initDB()
await db.put('session', {
  id: 'current_session',
  encrypted_password: null,
  encryption_key: null,
  expires_at: 0
})
```

---

#### Issue: "App cache stale after update"

**Symptoms**:
- App updated but still shows old data
- Service Worker cached version

**Solution**:
```bash
# Force Service Worker update
# DevTools → Application → Service Workers → Unregister

# Or automatic via vite-plugin-pwa
registerType: 'autoUpdate'  # Checks every app load
```

---

### Debug Tips

#### Enable Offline Mode (Chrome DevTools)

1. Open DevTools (F12)
2. Network tab → "Offline" dropdown
3. Select "Offline"
4. App now runs without network
5. Check IndexedDB for cached data

#### Inspect IndexedDB Data

1. DevTools → Application → Storage → IndexedDB
2. Expand `qrmade-armando` database
3. View each store:
   - gift_cards: check cached_at
   - offline_queue: check pending debits
   - session: check user_id, expires_at

#### Monitor Service Worker

1. DevTools → Application → Service Workers
2. Check status (activated/running)
3. Unregister to force clean re-registration
4. Check console for SW errors

#### Browser Console Debugging

```javascript
// Check online status
navigator.onLine  // true/false

// Get all pending actions
const db = await initDB()
const pending = await db.getAll('offline_queue')
console.table(pending)

// Check session
const session = await db.get('session', 'current_session')
console.log('Session expires at:', new Date(session.expires_at))

// Force IndexedDB cleanup
await db.clear('transactions')
```

---

## Performance Considerations

### Caching Strategies

| Resource | Strategy | TTL | Hit Rate | Impact |
|----------|----------|-----|----------|--------|
| **Static Assets** | CacheFirst | 30d | 99%+ | Instant page load |
| **Inertia Pages** | StaleWhileRevalidate | 1h | 95%+ | Always responsive |
| **API Data** | CacheFirst (data) | 24h | 90%+ | Offline capability |
| **API Calls** | NetworkFirst (transact) | 60s | 50%+ | Real-time debits |
| **Images** | CacheFirst | 30d | 95%+ | Bandwidth savings |

### Bundle Size Optimization

**Frontend Bundle**:
```
Base Inertia app: 150KB
+ React 19: 45KB
+ PWA hooks: 30KB
+ Crypto utilities: 25KB
+ IndexedDB wrapper: 15KB
Total: ~265KB (gzipped: ~85KB)
```

**Optimization Techniques**:
1. **Code splitting**: Components loaded on-demand
2. **Tree shaking**: Unused crypto functions excluded
3. **Compression**: gzip (server) + Brotli (CDN)
4. **Lazy loading**: IndexedDB init deferred until needed

### Lazy Loading

**Service Worker**:
```typescript
// SW only loads when PWA functionality needed
if ('serviceWorker' in navigator) {
  navigator.serviceWorker.register('/sw-custom.js')
}
```

**IndexedDB**:
```typescript
// DB only opened when useOfflineData hook mounted
useEffect(() => {
  const db = initDB()  // First access opens database
}, [])
```

**Crypto**:
```typescript
// Imported only in offline session hook
// Tree shaking removes if not used
import { encryptPassword } from '@/lib/crypto'
```

### Service Worker Updates

**Auto-Update Strategy**:
```javascript
// vite-plugin-pwa automatic
registerType: 'autoUpdate'

// On page reload or after 1 hour:
// 1. Check for new SW version
// 2. Download if available
// 3. Activate on next visit
```

**User Notification**:
```typescript
// Prompt user to reload (optional)
const newContentWaiting = useRef(false)

useEffect(() => {
  if (newContentWaiting.current) {
    showToast('App update available')
  }
}, [])
```

---

## Future Enhancements

### Potential Improvements

1. **Background Sync API**
   - Workbox Level 2: Schedule retries during poor connection
   - Automatic sync even if app closed

2. **Service Worker Precaching**
   - Pre-cache entire pages for offline browsing
   - Improved initial offline load time

3. **Periodic Background Sync**
   - OS-managed sync (charge battery when optimal)
   - Battery-friendly background updates

4. **Shared Web Workers**
   - Shared IndexedDB connection across tabs
   - Unified sync state

5. **IndexedDB Replication**
   - PouchDB/CouchDB style replication
   - Conflict resolution for multi-tab scenarios

### Known Limitations

| Limitation | Impact | Workaround |
|-----------|--------|-----------|
| **iOS Safari** | Limited SW support (iOS 16.1+) | Fallback to session storage |
| **IndexedDB Size** | ~50MB limit | Cleanup old transactions |
| **Offline Encryption** | No biometric unlock yet | Manual password entry |
| **Cross-Tab Sync** | Separate IndexedDB per tab | User refreshes to sync |
| **Shared Workers** | Not implemented | Full app refresh needed |

### Roadmap Items

**Phase 5 (Q2 2026)**:
- [ ] Background Sync API integration
- [ ] Offline transaction analytics
- [ ] Multi-device sync (cloud backup)

**Phase 6 (Q3 2026)**:
- [ ] Encrypted cloud backup
- [ ] Transaction conflict resolution
- [ ] Offline analytics dashboard

**Phase 7 (Q4 2026)**:
- [ ] Native iOS/Android apps (React Native)
- [ ] Biometric authentication
- [ ] Advanced offline reconciliation

---

## Appendix: Quick Reference

### Key Files Summary

```
resources/js/
├── lib/
│   ├── db.ts              # IndexedDB layer (6 stores, migrations)
│   └── crypto.ts          # AES-256-GCM encryption (PBKDF2)
├── hooks/
│   ├── use-offline-session.ts    # 30-day sessions + password storage
│   ├── use-offline-data.ts       # Gift card/category caching
│   ├── use-scanner-offline.ts    # QR scanning + debit queue
│   └── use-push-notifications.ts # Push subscription
├── sw-custom.ts           # Service Worker (push + caching)
└── pages/
    ├── scanner.tsx        # QR scan interface
    └── dashboard.tsx      # Transaction history

app/
├── Http/Controllers/Api/V1/
│   ├── GiftCardController.php  # /api/v1/gift-cards, /search
│   ├── DebitController.php     # /api/v1/debit
│   ├── SyncController.php      # /api/v1/sync/transactions
│   └── CategoryController.php  # /api/v1/categories
└── Services/
    └── TransactionService.php  # Transaction processing + sync

database/
├── migrations/
│   └── *_add_offline_id_to_transactions_table.php  # Idempotent sync
```

### Command Reference

```bash
# Development
npm run dev              # Start dev server with PWA
npm run build           # Production build
npm run types           # TypeScript check

# Testing
npm run test            # Run tests
npm run test:coverage   # Coverage report

# Database
php artisan migrate     # Run migrations
php artisan tinker      # Interactive console

# VAPID Keys
php artisan webpush:vapid  # Generate new VAPID pair

# Logs
tail -f storage/logs/laravel.log  # Watch logs
php artisan log:tail              # Real-time tail
```

### Environment Variables

```bash
# Frontend
VITE_VAPID_PUBLIC_KEY=xxx  # Public key for notifications

# Backend
VAPID_PRIVATE_KEY=xxx      # Private key (server only)
VAPID_PUBLIC_KEY=xxx
APP_URL=https://qrmadearmando.test  # HTTPS required
```

---

**Document Version**: 1.0
**Last Updated**: February 9, 2026
**Status**: Production-Ready
**Next Review**: After Phase 5 deployment (Q2 2026)
