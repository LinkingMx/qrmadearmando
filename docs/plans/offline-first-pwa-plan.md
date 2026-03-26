# Offline-First PWA Implementation Plan
## PWA sin Login Requerido + Sesión Persistente + Funcionalidad Offline

**Status**: 📋 Plan Document
**Priority**: High
**Effort**: 8-10 horas (4 fases)
**Date Created**: 2025-02-09

---

## 🎯 Objetivos

1. **PWA sin login requerido** - Acceso directo a QR codes, scanner, datos básicos sin autenticación
2. **Sesión persistente larga** - Password guardado seguramente en localStorage/IndexedDB (30 días)
3. **Funcionalidad offline-first** - Scanner QR, fotos, datos de gift cards disponibles sin internet
4. **Sincronización inteligente** - Cuando hay conexión, sincroniza datos del usuario
5. **Datos generales siempre disponibles** - Categorías, chains, brands, branches en caché

---

## 📊 Arquitectura de Alto Nivel

```
┌─────────────────────────────────────────────────────────────┐
│                     USER INTERACTION                        │
│  (No login → Guest Mode)  (Login → Authenticated Mode)     │
└─────────────────────────────────────────────────────────────┘
                            ↓
         ┌──────────────────────────────────────┐
         │    SERVICE WORKER (Offline Logic)    │
         │  - Cache QR codes                    │
         │  - Intercept network requests        │
         │  - Queue offline actions             │
         └──────────────────────────────────────┘
                            ↓
    ┌────────────────────────┴────────────────────────┐
    ↓                                                  ↓
┌──────────────────────┐              ┌──────────────────────┐
│   INDEXEDDB          │              │   LOCALSTORAGE       │
│ (Large Data)         │              │ (Session Data)       │
│ - Gift cards         │              │ - Session token      │
│ - Transactions       │              │ - User prefs         │
│ - Images/Cache       │              │ - Encryption key     │
└──────────────────────┘              └──────────────────────┘
    ↓                                                  ↓
┌────────────────────────────────────────────────────────────┐
│              SYNC ENGINE (Online)                          │
│  - Upload offline actions                                  │
│  - Download fresh data                                     │
│  - Conflict resolution                                     │
└────────────────────────────────────────────────────────────┘
    ↓
┌────────────────────────────────────────────────────────────┐
│              LARAVEL BACKEND API                           │
│  - Guest endpoints (QR codes, scanner)                     │
│  - Authenticated endpoints (user data)                     │
│  - Sync endpoints (offline resolution)                     │
└────────────────────────────────────────────────────────────┘
```

---

## 📋 Phase 1: Planning & Architecture (2 horas)

### 1.1 Mode System Design

**Guest Mode (Sin Login):**
- ✅ Ver todas las gift cards disponibles
- ✅ Usar scanner QR
- ✅ Ver detalles de QR codes
- ✅ Ver categorías, chains, branches
- ❌ Ver transacciones personales
- ❌ Debitar/cargar saldos
- ❌ Datos del usuario

**Authenticated Mode (Con Login):**
- ✅ Todas las funciones de Guest Mode
- ✅ Ver transacciones personales
- ✅ Cargar/debitar gift cards
- ✅ Ver perfil y datos
- ✅ Cambiar preferencias

### 1.2 Data Model - IndexedDB Schema

```typescript
// Versión: 2 (Upgrading from v1)

interface GiftCardStore {
  id: string (UUID, primary key)
  legacy_id: string
  category_id: string
  balance: number
  status: 'active' | 'inactive'
  created_at: timestamp
  updated_at: timestamp
  cached_at: timestamp
  is_dirty: boolean // Para sync
}

interface TransactionStore {
  id: string (primary key)
  gift_card_id: string (FK)
  type: 'debit' | 'credit' | 'adjustment'
  amount: number
  balance_before: number
  balance_after: number
  description: string
  created_at: timestamp
  synced: boolean // Offline actions pending
  offline_id?: string // UUID para offline actions
}

interface ImageStore {
  id: string (primary key)
  type: 'qr_code' | 'icon' | 'logo'
  url: string
  data: Blob
  cached_at: timestamp
  expires_at: timestamp
}

interface CategoryStore {
  id: string (primary key)
  prefix: string
  nature: 'payment_method' | 'discount'
  name_es: string
  cached_at: timestamp
}

interface SessionStore {
  id: 'current_session' (only one)
  user_id: string | null
  mode: 'guest' | 'authenticated'
  encrypted_password: string | null // AES-256
  encryption_key: string // Stored separately
  login_timestamp: timestamp
  expires_at: timestamp (30 days)
  token: string | null
}

interface OfflineQueueStore {
  id: string (UUID)
  action_type: 'debit' | 'credit' | 'adjustment'
  payload: object
  created_at: timestamp
  retry_count: number
  last_error: string | null
}
```

### 1.3 Session Security Design

**Password Encryption Strategy:**
- User entra password
- Generate encryption key: `PBKDF2(password, salt, iterations=100000)`
- Store: encrypted password (AES-256), pero NO plain text
- On login: User proporciona password → decrypt → compara con stored hash
- Session expires en 30 días (puede extender con refresh)

**Storage Strategy:**
```typescript
localStorage: {
  'session:user_id': 'uuid',
  'session:mode': 'guest|authenticated',
  'session:token': 'auth_token',
  'session:expires_at': 'timestamp',
  'security:salt': 'random_32_bytes_hex',
  'security:encryption_key_hash': 'hash_of_key'
}

IndexedDB: {
  sessionStore: {
    user_id, mode, encrypted_password, encryption_key, token, expires_at
  }
}
```

### 1.4 API Endpoints Needed

**Guest Endpoints (No auth required):**
```
GET /api/v1/gift-cards (paginated, 100 per page)
GET /api/v1/gift-cards/:id (single card details)
GET /api/v1/categories
GET /api/v1/chains
GET /api/v1/brands
GET /api/v1/branches
GET /api/v1/images/:type/:id (QR codes, logos)
```

**Authenticated Endpoints:**
```
POST /api/v1/transactions (debit/credit/adjustment)
GET /api/v1/me/transactions
GET /api/v1/me/profile
POST /api/v1/me/logout
```

**Sync Endpoints:**
```
POST /api/v1/sync/offline-queue (process offline actions)
GET /api/v1/sync/since/:timestamp (get updates since time)
POST /api/v1/sync/upload-image (upload cached images)
```

### 1.5 Service Worker Caching Strategy

**Network First (Fresh data):**
- Gift cards, transactions, user data
- Fallback to cache if offline

**Cache First (Stable data):**
- Categories, chains, brands (rarely change)
- Images, logos, icons
- App shell (HTML, CSS, JS)

**Stale While Revalidate:**
- QR code images (refresh in background)

---

## 🔧 Phase 2: Frontend Development (4 horas)

### 2.1 IndexedDB Initialization

**File:** `resources/js/lib/db.ts`

```typescript
import { openDB, DBSchema, IDBPDatabase } from 'idb'

interface AppDB extends DBSchema {
  gift_cards: {
    key: string
    value: GiftCard
    indexes: { 'by-legacy-id': string, 'by-category': string }
  }
  transactions: {
    key: string
    value: Transaction
    indexes: { 'by-gift-card': string, 'by-user': string, 'synced': boolean }
  }
  images: {
    key: string
    value: ImageData
    indexes: { 'by-type': string, 'expires-at': number }
  }
  categories: {
    key: string
    value: Category
  }
  session: {
    key: string
    value: SessionData
  }
  offline_queue: {
    key: string
    value: OfflineAction
  }
}

export async function initDB(): Promise<IDBPDatabase<AppDB>> {
  return openDB<AppDB>('qrmade-armando', 2, {
    upgrade(db, oldVersion, newVersion, transaction) {
      if (oldVersion < 1) {
        // Create gift_cards store
        const gcStore = db.createObjectStore('gift_cards', { keyPath: 'id' })
        gcStore.createIndex('by-legacy-id', 'legacy_id', { unique: true })
        gcStore.createIndex('by-category', 'category_id')

        // Create transactions store
        const txStore = db.createObjectStore('transactions', { keyPath: 'id' })
        txStore.createIndex('by-gift-card', 'gift_card_id')
        txStore.createIndex('by-user', 'user_id')
        txStore.createIndex('synced', 'synced')

        // Create other stores...
        db.createObjectStore('images', { keyPath: 'id' })
        db.createObjectStore('categories', { keyPath: 'id' })
        db.createObjectStore('session', { keyPath: 'id' })
        db.createObjectStore('offline_queue', { keyPath: 'id' })
      }

      if (oldVersion < 2) {
        // Migration from v1 to v2 (if needed)
      }
    },
  })
}
```

### 2.2 Session Management Hook

**File:** `resources/js/hooks/use-offline-session.ts`

```typescript
import { useEffect, useState, useCallback } from 'react'
import { initDB } from '@/lib/db'
import { encrypt, decrypt, generateKey } from '@/lib/crypto'

interface SessionData {
  user_id: string | null
  mode: 'guest' | 'authenticated'
  token: string | null
  expires_at: number
}

export function useOfflineSession() {
  const [session, setSession] = useState<SessionData>({
    user_id: null,
    mode: 'guest',
    token: null,
    expires_at: 0,
  })

  const [isLoading, setIsLoading] = useState(true)

  // Load session from IndexedDB on mount
  useEffect(() => {
    const loadSession = async () => {
      try {
        const db = await initDB()
        const sessionData = await db.get('session', 'current_session')

        if (sessionData && sessionData.expires_at > Date.now()) {
          setSession(sessionData)
        } else if (sessionData) {
          // Session expired - logout
          await db.delete('session', 'current_session')
          localStorage.removeItem('session:token')
        }
      } finally {
        setIsLoading(false)
      }
    }

    loadSession()
  }, [])

  const login = useCallback(
    async (email: string, password: string) => {
      try {
        // 1. Authenticate with backend
        const response = await fetch('/api/v1/auth/login', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ email, password }),
        })

        if (!response.ok) throw new Error('Login failed')

        const data = await response.json()
        const { token, user } = data

        // 2. Generate encryption key from password
        const encryptionKey = await generateKey(password, localStorage.getItem('security:salt')!)

        // 3. Store session in IndexedDB (encrypted password)
        const db = await initDB()
        const encryptedPassword = await encrypt(password, encryptionKey)

        const newSession: SessionData = {
          user_id: user.id,
          mode: 'authenticated',
          token,
          expires_at: Date.now() + 30 * 24 * 60 * 60 * 1000, // 30 days
        }

        await db.put('session', {
          id: 'current_session',
          ...newSession,
          encrypted_password: encryptedPassword,
          encryption_key: encryptionKey,
        })

        // 4. Store token in localStorage for quick access
        localStorage.setItem('session:token', token)
        localStorage.setItem('session:user_id', user.id)
        localStorage.setItem('session:expires_at', newSession.expires_at.toString())

        setSession(newSession)
        return true
      } catch (error) {
        console.error('Login error:', error)
        return false
      }
    },
    []
  )

  const logout = useCallback(async () => {
    try {
      const db = await initDB()
      await db.delete('session', 'current_session')
      localStorage.removeItem('session:token')
      localStorage.removeItem('session:user_id')

      setSession({
        user_id: null,
        mode: 'guest',
        token: null,
        expires_at: 0,
      })
    } catch (error) {
      console.error('Logout error:', error)
    }
  }, [])

  return {
    session,
    isLoading,
    isAuthenticated: session.mode === 'authenticated' && session.expires_at > Date.now(),
    login,
    logout,
  }
}
```

### 2.3 Offline Data Fetch Hook

**File:** `resources/js/hooks/use-offline-data.ts`

```typescript
import { useEffect, useState } from 'react'
import { initDB } from '@/lib/db'

export function useOfflineGiftCards() {
  const [giftCards, setGiftCards] = useState([])
  const [isLoading, setIsLoading] = useState(true)
  const [lastSync, setLastSync] = useState(0)

  useEffect(() => {
    const fetchGiftCards = async () => {
      try {
        const db = await initDB()

        // Try network first
        if (navigator.onLine) {
          const response = await fetch('/api/v1/gift-cards?limit=100')
          if (response.ok) {
            const data = await response.json()

            // Cache in IndexedDB
            for (const card of data.data) {
              await db.put('gift_cards', {
                ...card,
                cached_at: Date.now(),
              })
            }

            setGiftCards(data.data)
            setLastSync(Date.now())
            return
          }
        }

        // Fallback to IndexedDB cache
        const cached = await db.getAll('gift_cards')
        setGiftCards(cached)
      } finally {
        setIsLoading(false)
      }
    }

    fetchGiftCards()

    // Set up sync listener
    const handleOnline = () => fetchGiftCards()
    window.addEventListener('online', handleOnline)

    return () => window.removeEventListener('online', handleOnline)
  }, [])

  return { giftCards, isLoading, lastSync }
}
```

### 2.4 Scanner with Offline Debit Queue

**File:** `resources/js/hooks/use-scanner-offline.ts`

```typescript
import { useCallback, useState } from 'react'
import { initDB } from '@/lib/db'

interface OfflineDebit {
  gift_card_id: string
  amount: number
  description: string
  offline_id: string
}

export function useScannerOffline() {
  const [pendingDebits, setPendingDebits] = useState<OfflineDebit[]>([])

  const processDebit = useCallback(
    async (giftCardId: string, amount: number, description: string) => {
      const db = await initDB()
      const offline_id = crypto.randomUUID()

      // Create offline action
      const offlineAction = {
        id: offline_id,
        action_type: 'debit' as const,
        payload: { gift_card_id: giftCardId, amount, description },
        created_at: Date.now(),
        retry_count: 0,
        last_error: null,
      }

      // Queue in IndexedDB
      await db.add('offline_queue', offlineAction)

      // Update local gift card balance
      const card = await db.get('gift_cards', giftCardId)
      if (card) {
        await db.put('gift_cards', {
          ...card,
          balance: card.balance - amount,
          is_dirty: true,
        })
      }

      // Add transaction to local store
      await db.add('transactions', {
        id: offline_id,
        gift_card_id: giftCardId,
        type: 'debit',
        amount,
        balance_before: card?.balance || 0,
        balance_after: (card?.balance || 0) - amount,
        description,
        created_at: Date.now(),
        synced: false,
        offline_id,
      })

      setPendingDebits((prev) => [
        ...prev,
        { gift_card_id: giftCardId, amount, description, offline_id },
      ])

      // Try to sync immediately if online
      if (navigator.onLine) {
        await syncOfflineQueue()
      }
    },
    []
  )

  const syncOfflineQueue = useCallback(async () => {
    const db = await initDB()
    const queue = await db.getAll('offline_queue')

    for (const action of queue) {
      try {
        const response = await fetch('/api/v1/sync/offline-queue', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            Authorization: `Bearer ${localStorage.getItem('session:token')}`,
          },
          body: JSON.stringify(action),
        })

        if (response.ok) {
          // Remove from queue
          await db.delete('offline_queue', action.id)

          // Mark transaction as synced
          const tx = await db.get('transactions', action.id)
          if (tx) {
            await db.put('transactions', { ...tx, synced: true })
          }

          setPendingDebits((prev) => prev.filter((d) => d.offline_id !== action.id))
        } else {
          // Update error
          await db.put('offline_queue', {
            ...action,
            retry_count: action.retry_count + 1,
            last_error: await response.text(),
          })
        }
      } catch (error) {
        console.error('Sync error:', error)
      }
    }
  }, [])

  return { processDebit, syncOfflineQueue, pendingDebits }
}
```

### 2.5 Updated Scanner Component

**File:** `resources/js/pages/scanner.tsx`

```typescript
import { useOfflineSession } from '@/hooks/use-offline-session'
import { useScannerOffline } from '@/hooks/use-scanner-offline'
import { useOfflineGiftCards } from '@/hooks/use-offline-data'

export default function Scanner() {
  const { session, isAuthenticated } = useOfflineSession()
  const { processDebit, pendingDebits } = useScannerOffline()
  const { giftCards } = useOfflineGiftCards()

  const handleQRScan = async (legacyId: string) => {
    const card = giftCards.find((c) => c.legacy_id === legacyId)
    if (!card) return

    // In guest mode: show card details only
    if (!isAuthenticated) {
      showCardDetails(card)
      return
    }

    // In authenticated mode: allow debit
    const amount = prompt('Amount to debit:')
    if (amount) {
      await processDebit(card.id, parseFloat(amount), 'Scanner debit')
    }
  }

  return (
    <div>
      <h1>QR Scanner</h1>

      {/* Scanner UI */}
      <QRCodeReader onScan={handleQRScan} />

      {/* Pending offline debits */}
      {pendingDebits.length > 0 && (
        <div className="bg-yellow-100 p-4">
          <p>{pendingDebits.length} transactions pending sync</p>
        </div>
      )}

      {/* Gift cards list */}
      <GiftCardsList cards={giftCards} />
    </div>
  )
}
```

### 2.6 Service Worker Updates

**File:** `resources/js/sw-custom.ts`

```typescript
declare const self: ServiceWorkerGlobalScope

const CACHE_VERSION = 'v1'
const CACHE_NAMES = {
  app: `qrmade-app-${CACHE_VERSION}`,
  data: `qrmade-data-${CACHE_VERSION}`,
  images: `qrmade-images-${CACHE_VERSION}`,
}

// Install event - cache app shell
self.addEventListener('install', (event) => {
  event.waitUntil(
    (async () => {
      const cache = await caches.open(CACHE_NAMES.app)
      await cache.addAll([
        '/',
        '/index.html',
        '/login',
        '/scanner',
        '/dashboard',
      ])
    })()
  )
})

// Fetch event - implement caching strategy
self.addEventListener('fetch', (event) => {
  const { request } = event

  // Skip non-GET requests
  if (request.method !== 'GET') {
    return
  }

  // Cache First strategy for images
  if (request.url.includes('/api/v1/images/')) {
    event.respondWith(
      caches.match(request).then((response) => {
        if (response) return response
        return fetch(request).then((response) => {
          if (!response.ok) return response
          const cache = caches.open(CACHE_NAMES.images)
          cache.then((c) => c.put(request, response.clone()))
          return response
        })
      })
    )
    return
  }

  // Network First for data
  if (request.url.includes('/api/v1/')) {
    event.respondWith(
      fetch(request)
        .then((response) => {
          if (response.ok) {
            const cache = caches.open(CACHE_NAMES.data)
            cache.then((c) => c.put(request, response.clone()))
          }
          return response
        })
        .catch(() => caches.match(request))
    )
    return
  }

  // App shell strategy
  event.respondWith(
    caches.match(request).then((response) => {
      return response || fetch(request).catch(() => caches.match('/'))
    })
  )
})

// Background sync for offline queue
self.addEventListener('sync', (event) => {
  if (event.tag === 'sync-offline-queue') {
    event.waitUntil(syncOfflineQueue())
  }
})

async function syncOfflineQueue() {
  // Get offline queue from IndexedDB and sync
  const db = await openDB('qrmade-armando')
  const queue = await db.getAll('offline_queue')

  for (const action of queue) {
    try {
      await fetch('/api/v1/sync/offline-queue', {
        method: 'POST',
        body: JSON.stringify(action),
        headers: {
          'Content-Type': 'application/json',
          Authorization: `Bearer ${await getStoredToken()}`,
        },
      })
      await db.delete('offline_queue', action.id)
    } catch (error) {
      console.error('Sync error:', error)
    }
  }
}
```

---

## ⚙️ Phase 3: Backend API Development (3 horas)

### 3.1 Guest API Endpoints

**File:** `app/Http/Controllers/Api/V1/GuestController.php`

```php
namespace App\Http\Controllers\Api\V1;

use App\Models\GiftCard;
use App\Models\GiftCardCategory;
use App\Http\Resources\GiftCardResource;
use Illuminate\Http\Request;

class GuestController extends Controller
{
    public function giftCards(Request $request)
    {
        $query = GiftCard::query();

        if ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->has('search')) {
            $query->where('legacy_id', 'like', '%' . $request->search . '%');
        }

        return GiftCardResource::collection(
            $query->paginate($request->input('limit', 100))
        );
    }

    public function giftCard($id)
    {
        $card = GiftCard::findOrFail($id);
        return new GiftCardResource($card);
    }

    public function categories()
    {
        return GiftCardCategory::all()->map(fn($c) => [
            'id' => $c->id,
            'prefix' => $c->prefix,
            'nature' => $c->nature,
        ]);
    }

    public function images($type, $id)
    {
        if ($type === 'qr_code') {
            $card = GiftCard::findOrFail($id);
            return response()->file(storage_path("app/public/qr_codes/{$card->uuid}.png"));
        }

        abort(404);
    }
}
```

### 3.2 Sync Endpoints

**File:** `app/Http/Controllers/Api/V1/SyncController.php`

```php
namespace App\Http\Controllers\Api\V1;

use App\Models\Transaction;
use App\Models\GiftCard;
use Illuminate\Http\Request;

class SyncController extends Controller
{
    public function offlineQueue(Request $request)
    {
        $this->middleware('auth:sanctum')->handle($request);

        $action = $request->validate([
            'action_type' => 'required|in:debit,credit,adjustment',
            'payload' => 'required|array',
            'created_at' => 'required|numeric',
        ]);

        $user = auth()->user();
        $payload = $action['payload'];

        try {
            if ($action['action_type'] === 'debit') {
                $card = GiftCard::findOrFail($payload['gift_card_id']);

                if ($card->balance < $payload['amount']) {
                    return response()->json(['error' => 'Insufficient balance'], 400);
                }

                Transaction::create([
                    'gift_card_id' => $card->id,
                    'type' => 'debit',
                    'amount' => $payload['amount'],
                    'balance_before' => $card->balance,
                    'balance_after' => $card->balance - $payload['amount'],
                    'description' => $payload['description'],
                    'admin_user_id' => $user->id,
                    'branch_id' => $user->branch_id,
                ]);

                $card->update(['balance' => $card->balance - $payload['amount']]);
            }

            return response()->json(['status' => 'synced'], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function since(Request $request, $timestamp)
    {
        // Return data changed since timestamp
        $since = \Carbon\Carbon::createFromTimestamp($timestamp / 1000);

        return [
            'gift_cards' => GiftCard::where('updated_at', '>', $since)->get(),
            'categories' => GiftCardCategory::where('updated_at', '>', $since)->get(),
        ];
    }
}
```

### 3.3 Auth Endpoints

**File:** `app/Http/Controllers/Api/V1/AuthController.php`

```php
namespace App\Http\Controllers\Api\V1;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $credentials['email'])->first();

        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            return response()->json(['error' => 'Invalid credentials'], 401);
        }

        if (!$user->is_active) {
            return response()->json(['error' => 'Account disabled'], 403);
        }

        $token = $user->createToken('mobile-session')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->tokens()->delete();
        return response()->json(['status' => 'logged out']);
    }
}
```

### 3.4 Routes

**File:** `routes/api.php`

```php
Route::prefix('v1')->group(function () {
    // Guest endpoints (no auth)
    Route::get('/gift-cards', 'Api\V1\GuestController@giftCards');
    Route::get('/gift-cards/{id}', 'Api\V1\GuestController@giftCard');
    Route::get('/categories', 'Api\V1\GuestController@categories');
    Route::get('/images/{type}/{id}', 'Api\V1\GuestController@images');

    // Auth endpoints
    Route::post('/auth/login', 'Api\V1\AuthController@login');

    // Protected endpoints
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/auth/logout', 'Api\V1\AuthController@logout');
        Route::post('/sync/offline-queue', 'Api\V1\SyncController@offlineQueue');
        Route::get('/sync/since/{timestamp}', 'Api\V1\SyncController@since');
    });
});
```

---

## ✅ Phase 4: Testing & Deployment (1-2 horas)

### 4.1 Test Cases

**Offline Scenarios:**
- ✅ User entra sin internet → ve gift cards en caché
- ✅ User escanea QR sin internet → debita offline
- ✅ Vuelve internet → sincroniza automáticamente
- ✅ Conflicto de balance → resuelve correctamente
- ✅ Session de 30 días funciona

**Guest vs Auth:**
- ✅ Guest mode: puede ver cards, scanner, pero no debitar
- ✅ Auth mode: todas las funciones disponibles
- ✅ Login/logout cambia modo correctamente

**Data Sync:**
- ✅ Descarga categorías al iniciar
- ✅ Sincroniza gift cards actualizadas
- ✅ Cachea imágenes correctamente
- ✅ Limpia caché expirado

### 4.2 Deployment Checklist

- [ ] Service Worker actualizado con estrategia de caché
- [ ] IndexedDB migrations en el código
- [ ] Encryption/decryption funcionando
- [ ] API endpoints probados con Postman
- [ ] Tests pasando (unit + integration)
- [ ] PWA manifest actualizado
- [ ] HTTPS habilitado (ya está)
- [ ] Offline queue sincroniza en background

---

## 📊 Success Criteria

- ✅ PWA funciona sin internet (QR codes, scanner, datos)
- ✅ Password guardado 30 días con encryption
- ✅ Autenticación opcional (guest mode funciona)
- ✅ Sync automático cuando hay conexión
- ✅ Offline debits quedan en cola y sincronizan
- ✅ Caché de images y categorías funciona
- ✅ Tests: 80%+ coverage
- ✅ Documentación completa

---

## 🔐 Security Considerations

1. **Password Storage:**
   - Nunca guardar plain text
   - Usar PBKDF2 + AES-256
   - Encryption key = derived del password
   - Session expira en 30 días

2. **Data Sync:**
   - Validar token en cada sync
   - Verificar user_id matches
   - Rate limit sync endpoints
   - Log todas las transacciones offline

3. **Offline Queue:**
   - Sign offline actions con user_id
   - Verificar balance antes de aplicar
   - Idempotent operations (retry-safe)
   - Timeout de 7 días para viejos datos

4. **Service Worker:**
   - No cachear datos sensitivos
   - Limpiar cache en logout
   - HTTPS only (PWA requirement)

---

## 📝 Files to Create/Modify

| Archivo | Acción | Descripción |
|---------|--------|-------------|
| `resources/js/lib/db.ts` | Create | IndexedDB setup |
| `resources/js/lib/crypto.ts` | Create | Encryption/decryption |
| `resources/js/hooks/use-offline-session.ts` | Create | Session management |
| `resources/js/hooks/use-offline-data.ts` | Create | Data fetching |
| `resources/js/hooks/use-scanner-offline.ts` | Create | Scanner + offline queue |
| `resources/js/pages/scanner.tsx` | Modify | Add offline support |
| `resources/js/sw-custom.ts` | Modify | Update caching strategy |
| `app/Http/Controllers/Api/V1/GuestController.php` | Create | Guest API |
| `app/Http/Controllers/Api/V1/SyncController.php` | Create | Sync endpoints |
| `app/Http/Controllers/Api/V1/AuthController.php` | Create | Auth endpoints |
| `routes/api.php` | Modify | Add API routes |
| `resources/views/app.blade.php` | Verify | PWA ready |

---

## 📅 Timeline

**Day 1:** Planning + IndexedDB setup (2 hrs)
**Day 2:** Frontend hooks + scanner (4 hrs)
**Day 3:** Backend API + routes (3 hrs)
**Day 4:** Testing + deployment (2 hrs)

**Total: ~8-10 horas**

---

## 🚀 Next Steps

1. ✅ Aprobar este plan
2. ⏳ Implementar Phase 2 (Frontend)
3. ⏳ Implementar Phase 3 (Backend)
4. ⏳ Testing & QA
5. ⏳ Deploy a producción

---

**¿Aprobado? Podemos empezar cuando quieras.**
