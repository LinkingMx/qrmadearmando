/**
 * Hook for managing offline-first gift card data caching
 * Syncs with API when online, serves from IndexedDB when offline
 */

import { Category, GiftCard, initDB } from '@/lib/db';
import { CategoryListResponse, GiftCardListResponse } from '@/types/api';
import { useCallback, useEffect, useRef, useState } from 'react';

export interface UseOfflineDataReturn {
    giftCards: GiftCard[];
    categories: Category[];
    isLoading: boolean;
    isSyncing: boolean;
    error: string | null;
    refresh: () => Promise<void>;
    clearCache: () => Promise<void>;
    lastSyncTime: number | null;
    isOnline: boolean;
}

/**
 * Fetch gift cards from the API
 * Response format: { data: GiftCard[] }
 */
async function fetchGiftCardsFromAPI(): Promise<GiftCard[]> {
    const response = await fetch('/api/v1/gift-cards', {
        headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
        },
    });

    if (!response.ok) {
        throw new Error(`Failed to fetch gift cards: ${response.statusText}`);
    }

    const data: GiftCardListResponse = await response.json();
    return data.data || [];
}

/**
 * Fetch categories from the API
 * Response format: { data: Category[] }
 */
async function fetchCategoriesFromAPI(): Promise<Category[]> {
    const response = await fetch('/api/v1/categories', {
        headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
        },
    });

    if (!response.ok) {
        throw new Error(`Failed to fetch categories: ${response.statusText}`);
    }

    const data: CategoryListResponse = await response.json();
    return data.data || [];
}

/**
 * Hook for managing offline gift card data
 */
export function useOfflineData(autoRefresh = true): UseOfflineDataReturn {
    const [giftCards, setGiftCards] = useState<GiftCard[]>([]);
    const [categories, setCategories] = useState<Category[]>([]);
    const [isLoading, setIsLoading] = useState(true);
    const [isSyncing, setIsSyncing] = useState(false);
    const [error, setError] = useState<string | null>(null);
    const [lastSyncTime, setLastSyncTime] = useState<number | null>(null);
    const [isOnline, setIsOnline] = useState(navigator.onLine);
    const syncTimeoutRef = useRef<NodeJS.Timeout>();

    /**
     * Load cached data from IndexedDB
     */
    const loadFromCache = useCallback(async () => {
        try {
            const db = await initDB();
            const cachedCards = await db.getAll('gift_cards');
            const cachedCategories = await db.getAll('categories');

            setGiftCards(cachedCards);
            setCategories(cachedCategories);
            return { cards: cachedCards, categories: cachedCategories };
        } catch (err) {
            // Error loading from cache, returning empty data
            return { cards: [], categories: [] };
        }
    }, []);

    /**
     * Sync data from API to IndexedDB
     */
    const syncFromAPI = useCallback(async () => {
        if (!isOnline) {
            return false;
        }

        try {
            setIsSyncing(true);
            setError(null);

            const db = await initDB();

            // Fetch gift cards
            const fetchedCards = await fetchGiftCardsFromAPI();
            for (const card of fetchedCards) {
                await db.put('gift_cards', {
                    ...card,
                    cached_at: Date.now(),
                });
            }

            // Fetch categories
            const fetchedCategories = await fetchCategoriesFromAPI();
            for (const cat of fetchedCategories) {
                await db.put('categories', {
                    ...cat,
                    cached_at: Date.now(),
                });
            }

            setGiftCards(fetchedCards);
            setCategories(fetchedCategories);
            setLastSyncTime(Date.now());

            return true;
        } catch (err) {
            const errorMsg = err instanceof Error ? err.message : 'Sync failed';
            setError(errorMsg);
            return false;
        } finally {
            setIsSyncing(false);
        }
    }, [isOnline]);

    /**
     * Initial load and setup
     */
    useEffect(() => {
        const initialize = async () => {
            try {
                setIsLoading(true);

                // Load from cache first
                await loadFromCache();

                // Try to sync if online
                if (isOnline && autoRefresh) {
                    await syncFromAPI();
                }
            } finally {
                setIsLoading(false);
            }
        };

        initialize();
    }, [isOnline, autoRefresh, loadFromCache, syncFromAPI]);

    /**
     * Monitor online/offline status
     */
    useEffect(() => {
        const handleOnline = () => setIsOnline(true);
        const handleOffline = () => setIsOnline(false);

        window.addEventListener('online', handleOnline);
        window.addEventListener('offline', handleOffline);

        return () => {
            window.removeEventListener('online', handleOnline);
            window.removeEventListener('offline', handleOffline);
        };
    }, []);

    /**
     * Auto-refresh on interval when online
     */
    useEffect(() => {
        if (!autoRefresh || !isOnline) {
            return;
        }

        // Sync immediately if last sync was long ago
        const timeSinceLastSync = lastSyncTime
            ? Date.now() - lastSyncTime
            : null;
        if (!timeSinceLastSync || timeSinceLastSync > 5 * 60 * 1000) {
            // 5 minutes
            syncFromAPI();
        }

        // Set up periodic sync every 5 minutes
        syncTimeoutRef.current = setInterval(
            () => {
                syncFromAPI();
            },
            5 * 60 * 1000,
        );

        return () => {
            if (syncTimeoutRef.current) {
                clearInterval(syncTimeoutRef.current);
            }
        };
    }, [autoRefresh, isOnline, lastSyncTime, syncFromAPI]);

    /**
     * Manual refresh
     */
    const refresh = useCallback(async () => {
        if (isOnline) {
            await syncFromAPI();
        } else {
            await loadFromCache();
        }
    }, [isOnline, syncFromAPI, loadFromCache]);

    /**
     * Clear all cached data
     */
    const clearCache = useCallback(async () => {
        try {
            const db = await initDB();

            // Get all gift cards and delete them
            const allCards = await db.getAll('gift_cards');
            for (const card of allCards) {
                await db.delete('gift_cards', card.id);
            }

            // Get all categories and delete them
            const allCategories = await db.getAll('categories');
            for (const cat of allCategories) {
                await db.delete('categories', cat.id);
            }

            setGiftCards([]);
            setCategories([]);
            setLastSyncTime(null);
        } catch (err) {
            const errorMsg =
                err instanceof Error ? err.message : 'Failed to clear cache';
            setError(errorMsg);
            throw err;
        }
    }, []);

    return {
        giftCards,
        categories,
        isLoading,
        isSyncing,
        error,
        refresh,
        clearCache,
        lastSyncTime,
        isOnline,
    };
}

/**
 * Hook for fetching a single gift card by legacy_id
 */
export function useOfflineGiftCard(legacy_id: string) {
    const [giftCard, setGiftCard] = useState<GiftCard | null>(null);
    const [isLoading, setIsLoading] = useState(true);
    const [error, setError] = useState<string | null>(null);

    useEffect(() => {
        const fetch = async () => {
            try {
                const db = await initDB();

                // Try to find in cache first
                const index = await db.getAllFromIndex(
                    'gift_cards',
                    'by-legacy-id',
                    legacy_id,
                );
                if (index.length > 0) {
                    setGiftCard(index[0]);
                    setError(null);
                    return;
                }

                // If not in cache and online, fetch from API
                if (navigator.onLine) {
                    const response = await fetch(
                        `/api/v1/gift-cards/search?legacy_id=${legacy_id}`,
                        {
                            headers: {
                                'Content-Type': 'application/json',
                                Accept: 'application/json',
                            },
                        },
                    );

                    if (response.ok) {
                        const data = await response.json();
                        const card = data.data;

                        // Cache the result
                        if (card) {
                            await db.put('gift_cards', {
                                ...card,
                                cached_at: Date.now(),
                            });
                            setGiftCard(card);
                            setError(null);
                        } else {
                            setGiftCard(null);
                            setError('Gift card not found');
                        }
                    } else {
                        setGiftCard(null);
                        setError('Failed to fetch gift card');
                    }
                } else {
                    setGiftCard(null);
                    setError('Gift card not found in cache and offline');
                }
            } catch (err) {
                const errorMsg =
                    err instanceof Error
                        ? err.message
                        : 'Failed to fetch gift card';
                setError(errorMsg);
                setGiftCard(null);
            } finally {
                setIsLoading(false);
            }
        };

        fetch();
    }, [legacy_id]);

    return { giftCard, isLoading, error };
}

/**
 * Hook for getting category by ID
 */
export function useOfflineCategory(categoryId: string) {
    const [category, setCategory] = useState<Category | null>(null);
    const [isLoading, setIsLoading] = useState(true);
    const [error, setError] = useState<string | null>(null);

    useEffect(() => {
        const fetch = async () => {
            try {
                const db = await initDB();
                const cat = await db.get('categories', categoryId);

                if (cat) {
                    setCategory(cat);
                    setError(null);
                } else {
                    setCategory(null);
                    setError('Category not found');
                }
            } catch (err) {
                const errorMsg =
                    err instanceof Error
                        ? err.message
                        : 'Failed to fetch category';
                setError(errorMsg);
                setCategory(null);
            } finally {
                setIsLoading(false);
            }
        };

        fetch();
    }, [categoryId]);

    return { category, isLoading, error };
}
