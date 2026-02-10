# Scanner Flow Documentation

This document provides comprehensive technical documentation for the QR Gift Card Scanner feature, including architecture, data flow, API endpoints, and offline-first strategy.

## Table of Contents

- [Architecture Overview](#architecture-overview)
- [Flow Diagrams](#flow-diagrams)
- [API Endpoints](#api-endpoints)
- [Data Models](#data-models)
- [Offline-First Strategy](#offline-first-strategy)
- [Error Codes](#error-codes)
- [QR Code System](#qr-code-system)
- [Scope Validation](#scope-validation)
- [Security Considerations](#security-considerations)

---

## Architecture Overview

The scanner system uses an **offline-first architecture** with progressive web app (PWA) capabilities:

```
┌─────────────────────────────────────────────────────────────┐
│                     CLIENT (Browser/PWA)                     │
├─────────────────────────────────────────────────────────────┤
│  React Components                                            │
│  ├── Scanner.tsx (QR scanning UI)                           │
│  ├── DebitForm.tsx (Transaction form)                       │
│  └── OfflineStatusIndicator.tsx (Sync status)               │
├─────────────────────────────────────────────────────────────┤
│  React Hooks (Business Logic)                               │
│  ├── useScannerOffline() - Scanning & debit processing     │
│  ├── useOfflineData() - Data caching & sync                │
│  └── useOfflineSession() - Auth & session management        │
├─────────────────────────────────────────────────────────────┤
│  IndexedDB (Local Storage)                                  │
│  ├── gift_cards (cached cards)                              │
│  ├── transactions (pending sync)                            │
│  ├── categories (metadata)                                  │
│  └── offline_queue (deferred actions)                       │
├─────────────────────────────────────────────────────────────┤
│  Service Worker (sw-custom.ts)                              │
│  ├── Cache-first strategy for cards/categories             │
│  ├── Network-first strategy for transactions               │
│  └── Background sync for offline transactions              │
└─────────────────────────────────────────────────────────────┘
                            ↕ HTTP/HTTPS
┌─────────────────────────────────────────────────────────────┐
│                     SERVER (Laravel)                         │
├─────────────────────────────────────────────────────────────┤
│  API Routes (routes/api.php)                                │
│  ├── Public: /api/v1/public/* (no auth)                    │
│  └── Authenticated: /api/v1/* (Sanctum)                    │
├─────────────────────────────────────────────────────────────┤
│  Controllers                                                 │
│  ├── GiftCardController - Card lookup & listing            │
│  ├── DebitController - Transaction processing              │
│  └── SyncController - Offline sync handler                 │
├─────────────────────────────────────────────────────────────┤
│  Services                                                    │
│  ├── TransactionService - Business logic                   │
│  └── QrCodeService - QR generation                         │
├─────────────────────────────────────────────────────────────┤
│  Models                                                      │
│  ├── GiftCard (UUID, legacy_id, balance, scope)            │
│  ├── Transaction (type, amount, balances)                  │
│  ├── Branch (organizational hierarchy)                     │
│  └── User (employees, authentication)                      │
├─────────────────────────────────────────────────────────────┤
│  Database (SQLite/PostgreSQL)                               │
│  └── Relational schema with foreign keys                   │
└─────────────────────────────────────────────────────────────┘
```

### Key Components

1. **Frontend (React + TypeScript)**
   - Inertia.js for SPA navigation
   - shadcn/ui components for UI
   - Tailwind CSS for styling
   - html5-qrcode for camera scanning

2. **Backend (Laravel 12)**
   - RESTful API with Sanctum authentication
   - Transaction service for business logic
   - Event-driven architecture for notifications
   - Queue system for async jobs

3. **Offline Layer**
   - IndexedDB for client-side data persistence
   - Service Worker for asset caching
   - Background sync for transaction queue
   - AES-256-GCM encryption for sensitive data

---

## Flow Diagrams

### 1. Scanner Access Flow

```
┌──────────────┐
│  User Opens  │
│  /scanner    │
└──────┬───────┘
       │
       ▼
┌──────────────────┐      No      ┌─────────────────┐
│ Authenticated?   ├─────────────▶│ Redirect to     │
│                  │               │ /login          │
└──────┬───────────┘               └─────────────────┘
       │ Yes
       ▼
┌──────────────────┐      No      ┌─────────────────┐
│ Has Branch       ├─────────────▶│ Show Error:     │
│ Assignment?      │               │ "No branch      │
└──────┬───────────┘               │  assigned"      │
       │ Yes                       └─────────────────┘
       ▼
┌──────────────────┐      No      ┌─────────────────┐
│ User Active?     ├─────────────▶│ Logout & Show   │
│                  │               │ "Account        │
└──────┬───────────┘               │  inactive"      │
       │ Yes                       └─────────────────┘
       ▼
┌──────────────────┐
│ Show Scanner     │
│ Interface        │
└──────────────────┘
```

### 2. QR Code Lookup Flow (Offline-First)

```
┌──────────────┐
│ User Scans   │
│ QR Code      │
└──────┬───────┘
       │
       ▼
┌─────────────────────────────────────────────────┐
│ Extract identifier (legacy_id or UUID)          │
└──────┬──────────────────────────────────────────┘
       │
       ▼
┌──────────────────┐      Found     ┌─────────────┐
│ Search IndexedDB ├───────────────▶│ Return Card │
│ (Cache-first)    │                │ from Cache  │
└──────┬───────────┘                └──────┬──────┘
       │ Not Found                          │
       ▼                                    │
┌──────────────────┐                        │
│ Check Online     │                        │
│ Status           │                        │
└──────┬───────────┘                        │
       │                                    │
       ├─ Offline ──────────────┐           │
       │                        ▼           │
       │                 ┌─────────────┐    │
       │                 │ Return      │    │
       │                 │ "Not Found" │    │
       │                 └─────────────┘    │
       │                                    │
       ├─ Online ───────────────┐           │
       │                        ▼           │
       │                 ┌────────────────┐ │
       │                 │ Call API:      │ │
       │                 │ GET /api/v1/   │ │
       │                 │ public/gift-   │ │
       │                 │ cards/search   │ │
       │                 └────────┬───────┘ │
       │                          │         │
       │                          ▼         │
       │                 ┌────────────────┐ │
       │                 │ Store in       │ │
       │                 │ IndexedDB      │ │
       │                 └────────┬───────┘ │
       │                          │         │
       └──────────────────────────┴─────────┘
                                  │
                                  ▼
                         ┌─────────────────┐
                         │ Display Card    │
                         │ Details:        │
                         │ - Name          │
                         │ - Balance       │
                         │ - Status        │
                         │ - QR Image      │
                         └─────────────────┘
```

### 3. Debit Processing Flow (Online + Offline)

```
┌──────────────┐
│ User Enters  │
│ Debit Amount │
└──────┬───────┘
       │
       ▼
┌──────────────────┐      No      ┌─────────────────┐
│ Validate Amount  ├─────────────▶│ Show Validation │
│ - Positive       │               │ Error           │
│ - <= Balance     │               └─────────────────┘
└──────┬───────────┘
       │ Yes
       ▼
┌──────────────────┐
│ Check Scope      │
│ Validation       │
└──────┬───────────┘
       │
       ├─ Invalid Scope ────┐
       │                    ▼
       │             ┌─────────────┐
       │             │ Show Error: │
       │             │ "Scope      │
       │             │  invalid"   │
       │             └─────────────┘
       │
       ├─ Valid Scope ──────┐
       │                    ▼
       │             ┌─────────────────┐
       │             │ Check Online    │
       │             │ Status          │
       │             └─────┬───────────┘
       │                   │
       │                   ├─ Online ────────────────┐
       │                   │                         ▼
       │                   │                  ┌────────────────┐
       │                   │                  │ POST /api/v1/  │
       │                   │                  │ debit          │
       │                   │                  └────────┬───────┘
       │                   │                           │
       │                   │                           ▼
       │                   │                  ┌────────────────┐
       │                   │                  │ Response:      │
       │                   │                  │ - transaction  │
       │                   │                  │ - new balance  │
       │                   │                  │ - folio        │
       │                   │                  └────────┬───────┘
       │                   │                           │
       │                   ├─ Offline ───────┐         │
       │                   │                 ▼         │
       │                   │          ┌────────────┐   │
       │                   │          │ Queue in   │   │
       │                   │          │ IndexedDB: │   │
       │                   │          │ offline_   │   │
       │                   │          │ queue      │   │
       │                   │          └──────┬─────┘   │
       │                   │                 │         │
       │                   │                 ▼         │
       │                   │          ┌────────────┐   │
       │                   │          │ Update     │   │
       │                   │          │ Local      │   │
       │                   │          │ Balance    │   │
       │                   │          └──────┬─────┘   │
       │                   │                 │         │
       └───────────────────┴─────────────────┴─────────┘
                                             │
                                             ▼
                                    ┌─────────────────┐
                                    │ Show Success    │
                                    │ - New Balance   │
                                    │ - Folio         │
                                    │ - Sync Status   │
                                    └─────────────────┘
```

### 4. Offline Sync Flow

```
┌──────────────┐
│ Network      │
│ Reconnected  │
└──────┬───────┘
       │
       ▼
┌──────────────────┐
│ Check offline_   │
│ queue in         │
│ IndexedDB        │
└──────┬───────────┘
       │
       ▼
┌──────────────────┐      No       ┌─────────────┐
│ Has Pending      ├──────────────▶│ Do Nothing  │
│ Transactions?    │                └─────────────┘
└──────┬───────────┘
       │ Yes
       ▼
┌──────────────────────────────────────┐
│ For each pending transaction:        │
│ ┌────────────────────────────────┐   │
│ │ 1. Generate offline_id (UUID)  │   │
│ │ 2. POST /api/v1/sync/          │   │
│ │    transactions                │   │
│ │ 3. Include offline_id for      │   │
│ │    idempotency                 │   │
│ └────────────────┬───────────────┘   │
│                  │                   │
│                  ▼                   │
│         ┌────────────────┐           │
│         │ Success?       │           │
│         └────────┬───────┘           │
│                  │                   │
│         ├─ Yes ──┼────────┐          │
│         │        │        ▼          │
│         │        │ ┌────────────┐    │
│         │        │ │ Remove from│    │
│         │        │ │ queue      │    │
│         │        │ └────────────┘    │
│         │        │                   │
│         ├─ No ───┼────────┐          │
│         │        │        ▼          │
│         │        │ ┌────────────┐    │
│         │        │ │ Increment  │    │
│         │        │ │ retry count│    │
│         │        │ └────────┬───┘    │
│         │        │          │        │
│         │        │          ▼        │
│         │        │ ┌────────────┐    │
│         │        │ │ Max retries│    │
│         │        │ │ reached?   │    │
│         │        │ └──────┬─────┘    │
│         │        │        │          │
│         │        │   Yes ─┼────┐     │
│         │        │        │    ▼     │
│         │        │        │ ┌──────┐ │
│         │        │        │ │ Flag │ │
│         │        │        │ │ Error│ │
│         │        │        │ └──────┘ │
│         │        │        │          │
│         │        └────────┴──────────┘
└─────────┴───────────────────────────────┘
       │
       ▼
┌──────────────────┐
│ Show Sync        │
│ Complete         │
│ Notification     │
└──────────────────┘
```

---

## API Endpoints

### Public Endpoints (No Authentication)

#### 1. Search Gift Card

**Endpoint**: `GET /api/v1/public/gift-cards/search`

**Purpose**: Lookup gift card by legacy_id or UUID (used by guest mode scanner)

**Query Parameters**:
- `legacy_id` (string, optional): Legacy ID format (e.g., EMCAD000001)
- `id` (string, optional): UUID format

**Response** (200 OK):
```json
{
  "success": true,
  "data": {
    "id": "550e8400-e29b-41d4-a716-446655440000",
    "legacy_id": "EMCAD000001",
    "status": true,
    "balance": 2500.00,
    "expiry_date": "2025-12-31",
    "qr_image_path": "/storage/qr-codes/550e8400-e29b-41d4-a716-446655440000",
    "category": {
      "id": 1,
      "name": "Empleados",
      "prefix": "EMCAD",
      "nature": "payment_method"
    }
  }
}
```

**Errors**:
- `400`: Missing parameter (legacy_id or id required)
- `403`: Inactive card
- `404`: Card not found

**Cache Headers**:
- `Cache-Control: public, max-age=3600` (1 hour)
- `ETag: <hash>` (for conditional requests)

---

#### 2. List Categories

**Endpoint**: `GET /api/v1/public/categories`

**Purpose**: Get all gift card categories for filtering

**Response** (200 OK):
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Empleados",
      "prefix": "EMCAD",
      "nature": "payment_method",
      "description": "Gift cards para empleados"
    },
    {
      "id": 2,
      "name": "Promociones",
      "prefix": "PROMO",
      "nature": "discount",
      "description": "Descuentos promocionales"
    }
  ]
}
```

**Cache Headers**:
- `Cache-Control: public, max-age=86400` (24 hours)
- `ETag: <hash>`

---

### Authenticated Endpoints (Requires Sanctum Token)

#### 3. List Gift Cards

**Endpoint**: `GET /api/v1/gift-cards`

**Purpose**: Get paginated list of active gift cards for caching

**Query Parameters**:
- `category_id` (integer, optional): Filter by category
- `page` (integer, default: 1): Page number
- `per_page` (integer, default: 50): Items per page

**Response** (200 OK):
```json
{
  "success": true,
  "data": [
    {
      "id": "550e8400-e29b-41d4-a716-446655440000",
      "legacy_id": "EMCAD000001",
      "status": true,
      "balance": 2500.00,
      "expiry_date": "2025-12-31",
      "qr_image_path": "/storage/qr-codes/550e8400-e29b-41d4-a716-446655440000",
      "category": {
        "id": 1,
        "name": "Empleados",
        "prefix": "EMCAD",
        "nature": "payment_method"
      }
    }
  ],
  "meta": {
    "current_page": 1,
    "last_page": 5,
    "per_page": 50,
    "total": 234,
    "from": 1,
    "to": 50
  }
}
```

**Cache Headers**:
- `Cache-Control: public, max-age=86400` (24 hours)
- `ETag: <hash>`

---

#### 4. Process Debit

**Endpoint**: `POST /api/v1/debit`

**Purpose**: Process debit transaction on gift card

**Request Body**:
```json
{
  "legacy_id": "EMCAD000001",
  "amount": 250.50,
  "description": "Compra en tienda"
}
```

**Validation**:
- `legacy_id`: required, string
- `amount`: required, numeric, min:0.01
- `description`: optional, string, max:255

**Response** (201 Created):
```json
{
  "success": true,
  "data": {
    "id": 1234,
    "gift_card_id": "550e8400-e29b-41d4-a716-446655440000",
    "type": "debit",
    "amount": 250.50,
    "balance_before": 2500.00,
    "balance_after": 2249.50,
    "description": "Compra en tienda",
    "created_at": 1707523200,
    "synced": true
  }
}
```

**Errors**:
- `403`: Inactive card
- `404`: Card not found
- `422`: Insufficient balance, validation error
- `500`: Processing error

**Cache Headers**:
- `Cache-Control: no-cache, no-store, must-revalidate`

---

#### 5. Sync Offline Transactions

**Endpoint**: `POST /api/v1/sync/transactions`

**Purpose**: Sync pending offline transactions (idempotent)

**Request Body**:
```json
{
  "transactions": [
    {
      "offline_id": "a1b2c3d4-e5f6-7890-abcd-ef1234567890",
      "legacy_id": "EMCAD000001",
      "amount": 100.00,
      "description": "Offline debit",
      "timestamp": 1707520000
    }
  ]
}
```

**Response** (200 OK):
```json
{
  "success": true,
  "data": {
    "synced": 1,
    "failed": 0,
    "results": [
      {
        "offline_id": "a1b2c3d4-e5f6-7890-abcd-ef1234567890",
        "transaction_id": 1235,
        "status": "synced"
      }
    ]
  }
}
```

**Idempotency**: Uses `offline_id` to prevent duplicate processing. If `offline_id` already exists, returns existing transaction.

---

#### 6. Get Sync Status

**Endpoint**: `GET /api/v1/sync/status`

**Purpose**: Get pending offline transaction count

**Response** (200 OK):
```json
{
  "success": true,
  "data": {
    "pending_count": 3,
    "last_sync": 1707523200
  }
}
```

---

### Legacy Endpoints (Web Auth, for backward compatibility)

#### 7. Lookup Gift Card (Legacy)

**Endpoint**: `POST /api/scanner/lookup`

**Middleware**: `auth`, `verified`, `has.branch`

**Request Body**:
```json
{
  "identifier": "EMCAD000001"
}
```

**Response**: Same as public search endpoint

---

#### 8. Process Debit (Legacy)

**Endpoint**: `POST /api/scanner/process-debit`

**Middleware**: `auth`, `verified`, `has.branch`

**Request Body**:
```json
{
  "gift_card_id": "550e8400-e29b-41d4-a716-446655440000",
  "amount": 250.50,
  "description": "Compra en tienda",
  "reference": "REF-001234"
}
```

**Validation**: Same as API debit endpoint + scope validation

---

## Data Models

### TypeScript Interfaces

#### GiftCard
```typescript
interface GiftCard {
  id: string; // UUID
  legacy_id: string; // EMCAD000001
  status: boolean;
  balance: number; // Float
  expiry_date: string | null; // ISO date
  qr_image_path: string | null;
  scope: 'chain' | 'brand' | 'branch';
  chain_id: number | null;
  brand_id: number | null;
  branch_id: number | null;
  category: GiftCardCategory;
  user?: {
    name: string;
    avatar: string | null;
  };
}
```

#### GiftCardCategory
```typescript
interface GiftCardCategory {
  id: number;
  name: string;
  prefix: string; // EMCAD
  nature: 'payment_method' | 'discount';
  description?: string;
}
```

#### Transaction
```typescript
interface Transaction {
  id: number;
  gift_card_id: string; // UUID
  type: 'credit' | 'debit' | 'adjustment';
  amount: number; // Float
  balance_before: number; // Float
  balance_after: number; // Float
  description: string;
  reference?: string;
  offline_id?: string; // UUID for offline sync
  branch_id: number | null;
  admin_id: number;
  created_at: number; // Unix timestamp
  synced: boolean;
}
```

#### OfflineQueueItem
```typescript
interface OfflineQueueItem {
  id: string; // Auto-increment
  offline_id: string; // UUID
  type: 'debit';
  legacy_id: string;
  amount: number;
  description: string;
  timestamp: number; // Unix timestamp
  retry_count: number;
  error?: string;
}
```

---

## Offline-First Strategy

### 1. Data Caching Strategy

**Cache-First Resources** (Long-lived, rarely change):
- **Gift Cards**: Cache for 24 hours
- **Categories**: Cache for 24 hours
- **Static Assets**: Cache indefinitely with version hash

**Network-First Resources** (Transactional, require freshness):
- **Debit Transactions**: Always hit server if online
- **Sync Status**: No caching
- **User Profile**: Short cache (5 minutes)

### 2. Offline Queue Management

**Queue Priority**:
1. Critical: Debit transactions
2. Normal: Balance adjustments
3. Low: Analytics events

**Retry Logic**:
- Max retries: 3 attempts
- Backoff: Exponential (1s, 2s, 4s)
- Failure handling: Mark as error, notify user

**Conflict Resolution**:
- Server state is always source of truth
- Client reconciles on sync
- Show diff to user if mismatch detected

### 3. Session Management

**Authenticated Sessions**:
- 30-day expiration
- Optional password persistence (encrypted AES-256-GCM)
- Auto-logout on inactive status

**Guest Sessions**:
- Scan-only mode (no debit processing)
- No data persistence beyond current session
- Public API access only

### 4. Service Worker Caching

**Workbox Strategies**:

```typescript
// Gift cards & categories
registerRoute(
  /\/api\/v1\/(public\/)?gift-cards/,
  new CacheFirst({
    cacheName: 'gift-cards-cache',
    plugins: [
      new ExpirationPlugin({
        maxAgeSeconds: 86400, // 24 hours
      }),
    ],
  })
);

// Transactions (debit, sync)
registerRoute(
  /\/api\/v1\/(debit|sync)/,
  new NetworkFirst({
    cacheName: 'transactions-cache',
    networkTimeoutSeconds: 5,
    plugins: [
      new ExpirationPlugin({
        maxAgeSeconds: 60, // 1 minute
      }),
    ],
  })
);
```

---

## Error Codes

| Code | HTTP Status | Message | User Action |
|------|-------------|---------|-------------|
| `QR_NOT_FOUND` | 404 | QR no encontrado | Verify QR code and retry |
| `INACTIVE_CARD` | 422 | QR está inactivo | Contact administrator |
| `INSUFFICIENT_BALANCE` | 422 | Saldo insuficiente | Enter lower amount |
| `INVALID_SCOPE` | 422 | QR no válido para esta sucursal | Use different card |
| `VALIDATION_ERROR` | 422 | Datos inválidos | Check form fields |
| `PROCESSING_ERROR` | 500 | Error al procesar transacción | Retry or contact support |
| `MISSING_PARAMETER` | 400 | Parámetro requerido faltante | Check request format |
| `NETWORK_ERROR` | 0 | Sin conexión a internet | Transaction queued for sync |
| `SYNC_ERROR` | 500 | Error al sincronizar | Retry sync manually |

---

## QR Code System

### Generation

Each gift card generates **2 QR codes**:

1. **UUID QR Code** (`{id}_uuid.svg`)
   - Contains: Full UUID
   - Format: `550e8400-e29b-41d4-a716-446655440000`
   - Use case: Internal admin reference

2. **Legacy ID QR Code** (`{id}_legacy.svg`)
   - Contains: Category prefix + 6-digit number
   - Format: `EMCAD000001`
   - Use case: User-facing scanner (shorter, readable)

### Storage

- **Path**: `storage/app/public/qr-codes/`
- **Format**: SVG (vector, scalable)
- **Size**: ~2KB per QR code
- **Public URL**: `/storage/qr-codes/{filename}`

### Lifecycle

1. **Created**: Auto-generated on gift card creation
2. **Updated**: Re-generated when `legacy_id` changes
3. **Deleted**: Removed on force delete (soft delete preserves)

---

## Scope Validation

Gift cards have three scope types that determine where they can be used:

### 1. Chain Scope

**Definition**: Can be used at **any branch** within the entire chain

**Validation**:
```php
$giftCard->scope === GiftCardScope::CHAIN
&& $giftCard->chain_id === $branch->brand->chain_id
```

**Use Case**: Corporate-wide gift cards, employee benefits

**Error Message**: "Este QR es tipo Cadena y no puede usarse en esta sucursal."

---

### 2. Brand Scope

**Definition**: Can only be used at branches of the **assigned brand**

**Validation**:
```php
$giftCard->scope === GiftCardScope::BRAND
&& $giftCard->brand_id === $branch->brand_id
```

**Use Case**: Brand-specific promotions, loyalty programs

**Error Message**: "Este QR es tipo Marca y solo funciona en sucursales de la marca asignada."

---

### 3. Branch Scope

**Definition**: Can only be used at the **specific assigned branch**

**Validation**:
```php
$giftCard->scope === GiftCardScope::BRANCH
&& $giftCard->branch_id === $branch->id
```

**Use Case**: Location-specific vouchers, store credits

**Error Message**: "Este QR es tipo Sucursal y no está asignado a esta ubicación."

---

### Organizational Hierarchy

```
Chain (chain_id)
├── Brand 1 (brand_id)
│   ├── Branch A (branch_id)
│   └── Branch B (branch_id)
└── Brand 2 (brand_id)
    ├── Branch C (branch_id)
    └── Branch D (branch_id)
```

**Scope Behavior**:
- **Chain scope** with chain_id = 1 → Valid at Branches A, B, C, D
- **Brand scope** with brand_id = 1 → Valid at Branches A, B only
- **Branch scope** with branch_id = A → Valid at Branch A only

---

## Security Considerations

### 1. Authentication & Authorization

- **Public endpoints**: Rate-limited to 60 requests/minute per IP
- **Authenticated endpoints**: Sanctum token required, 100 req/min per user
- **Branch validation**: User must be assigned to a branch for scanner access
- **Scope validation**: Server-side validation on every debit transaction

### 2. Data Protection

- **Passwords**: Encrypted with AES-256-GCM before storing in localStorage
- **Tokens**: Sanctum tokens stored in HTTP-only cookies
- **Sensitive data**: Balance, PII never logged or cached insecurely
- **QR codes**: Public but non-guessable (UUID-based)

### 3. Transaction Integrity

- **Idempotency**: `offline_id` prevents duplicate transactions
- **Balance validation**: Server-side check before processing
- **Atomic operations**: DB transactions ensure consistency
- **Audit trail**: All transactions logged with user, branch, timestamp

### 4. Offline Security

- **IndexedDB encryption**: Sensitive fields encrypted at rest
- **Sync authentication**: All sync requests require valid token
- **Queue expiration**: Offline transactions expire after 7 days
- **Conflict detection**: Server rejects stale/conflicting updates

---

## Performance Optimization

### 1. Caching Strategy

- **ETag support**: Conditional requests reduce bandwidth
- **Pagination**: 50 items per page for gift card listing
- **Lazy loading**: Categories loaded only when needed
- **Service Worker**: Pre-cache critical assets on install

### 2. Database Optimization

- **Indexes**: Created on `legacy_id`, `status`, `scope`, `offline_id`
- **Eager loading**: Use `with()` to prevent N+1 queries
- **Query scopes**: Reusable query filters for active cards
- **Connection pooling**: Reuse DB connections for API requests

### 3. Frontend Optimization

- **Code splitting**: Lazy load scanner page
- **Image optimization**: SVG QR codes (small, scalable)
- **Debouncing**: QR scan input debounced to 300ms
- **Virtual scrolling**: For long transaction lists

---

## Monitoring & Debugging

### Key Metrics

1. **Sync Queue Length**: Alert if >100 pending transactions
2. **API Response Time**: Target <200ms for lookup, <500ms for debit
3. **Error Rate**: Alert if >5% of transactions fail
4. **Offline Usage**: Track % of debits processed offline
5. **Cache Hit Rate**: Target >80% for gift card lookups

### Debugging Tools

- **Browser DevTools**: Check IndexedDB, Service Worker, Network
- **Laravel Telescope**: Monitor API requests, queue jobs
- **Log aggregation**: Centralized logging for error tracking
- **Performance profiling**: Use Chrome DevTools Performance tab

---

**Last Updated**: 2026-02-09
**Version**: 1.0.0
