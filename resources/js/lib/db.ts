import { DBSchema, IDBPDatabase, openDB } from 'idb';

export interface GiftCard {
    id: string;
    legacy_id: string;
    category_id: string;
    balance: number;
    status: 'active' | 'inactive';
    created_at: number;
    updated_at: number;
    cached_at: number;
    is_dirty: boolean;
}

export interface Transaction {
    id: string;
    gift_card_id: string;
    user_id?: string;
    type: 'debit' | 'credit' | 'adjustment';
    amount: number;
    balance_before: number;
    balance_after: number;
    description: string;
    created_at: number;
    synced: boolean;
    offline_id?: string;
}

export interface ImageData {
    id: string;
    type: 'qr_code' | 'icon' | 'logo';
    url: string;
    data: Blob;
    cached_at: number;
    expires_at: number;
}

export interface Category {
    id: string;
    prefix: string;
    nature: 'payment_method' | 'discount';
    name_es: string;
    cached_at: number;
}

export interface SessionData {
    id: 'current_session';
    user_id: string | null;
    mode: 'guest' | 'authenticated';
    encrypted_password: string | null;
    encryption_key: string | null;
    login_timestamp: number;
    expires_at: number;
    token: string | null;
}

export interface OfflineAction {
    id: string;
    action_type: 'debit' | 'credit' | 'adjustment';
    payload: Record<string, any>;
    created_at: number;
    retry_count: number;
    last_error: string | null;
}

interface AppDB extends DBSchema {
    gift_cards: {
        key: string;
        value: GiftCard;
        indexes: { 'by-legacy-id': string; 'by-category': string };
    };
    transactions: {
        key: string;
        value: Transaction;
        indexes: { 'by-gift-card': string; 'by-user': string; synced: boolean };
    };
    images: {
        key: string;
        value: ImageData;
        indexes: { 'by-type': string; 'expires-at': number };
    };
    categories: {
        key: string;
        value: Category;
    };
    session: {
        key: string;
        value: SessionData;
    };
    offline_queue: {
        key: string;
        value: OfflineAction;
    };
}

let dbInstance: IDBPDatabase<AppDB> | null = null;

export async function initDB(): Promise<IDBPDatabase<AppDB>> {
    if (dbInstance) {
        return dbInstance;
    }

    dbInstance = await openDB<AppDB>('qrmade-armando', 2, {
        upgrade(db, oldVersion, newVersion, transaction) {
            // Version 1: Initial stores
            if (oldVersion < 1) {
                // Gift cards store
                if (!db.objectStoreNames.contains('gift_cards')) {
                    const gcStore = db.createObjectStore('gift_cards', {
                        keyPath: 'id',
                    });
                    gcStore.createIndex('by-legacy-id', 'legacy_id', {
                        unique: false,
                    });
                    gcStore.createIndex('by-category', 'category_id');
                }

                // Transactions store
                if (!db.objectStoreNames.contains('transactions')) {
                    const txStore = db.createObjectStore('transactions', {
                        keyPath: 'id',
                    });
                    txStore.createIndex('by-gift-card', 'gift_card_id');
                    txStore.createIndex('by-user', 'user_id');
                    txStore.createIndex('synced', 'synced');
                }

                // Images store
                if (!db.objectStoreNames.contains('images')) {
                    const imgStore = db.createObjectStore('images', {
                        keyPath: 'id',
                    });
                    imgStore.createIndex('by-type', 'type');
                    imgStore.createIndex('expires-at', 'expires_at');
                }

                // Categories store
                if (!db.objectStoreNames.contains('categories')) {
                    db.createObjectStore('categories', { keyPath: 'id' });
                }

                // Session store
                if (!db.objectStoreNames.contains('session')) {
                    db.createObjectStore('session', { keyPath: 'id' });
                }

                // Offline queue store
                if (!db.objectStoreNames.contains('offline_queue')) {
                    db.createObjectStore('offline_queue', { keyPath: 'id' });
                }
            }

            // Version 2: Future migrations (if needed)
            if (oldVersion < 2) {
                // Migration logic here
            }
        },
    });

    return dbInstance;
}

export async function closeDB(): Promise<void> {
    if (dbInstance) {
        dbInstance.close();
        dbInstance = null;
    }
}

// Clean up expired images
export async function cleanupExpiredImages(): Promise<void> {
    const db = await initDB();
    const now = Date.now();

    const allImages = await db.getAll('images');
    for (const image of allImages) {
        if (image.expires_at < now) {
            await db.delete('images', image.id);
        }
    }
}

// Get all pending offline actions
export async function getPendingActions(): Promise<OfflineAction[]> {
    const db = await initDB();
    return db.getAll('offline_queue');
}

// Add offline action to queue
export async function queueOfflineAction(
    action: Omit<OfflineAction, 'id'>,
): Promise<string> {
    const db = await initDB();
    const id = crypto.randomUUID();

    await db.add('offline_queue', {
        id,
        ...action,
    });

    return id;
}

// Remove offline action from queue
export async function removeOfflineAction(id: string): Promise<void> {
    const db = await initDB();
    await db.delete('offline_queue', id);
}

// Update offline action retry count
export async function updateOfflineActionError(
    id: string,
    error: string,
): Promise<void> {
    const db = await initDB();
    const action = await db.get('offline_queue', id);

    if (action) {
        await db.put('offline_queue', {
            ...action,
            retry_count: action.retry_count + 1,
            last_error: error,
        });
    }
}
