import { useEffect, useState } from 'react';

interface UserData {
    id: string;
    name: string;
    email: string;
    avatar: string | null;
}

interface GiftCardData {
    id: string;
    legacy_id: string;
    balance: number;
    status: boolean;
    expiry_date: string | null;
    qr_image_path: string | null;
}

export interface OfflineGiftCardData {
    user: UserData;
    gift_card: GiftCardData;
}

interface UseOfflineGiftCardResult {
    data: OfflineGiftCardData | null;
    isLoading: boolean;
    error: string | null;
    isOffline: boolean;
}

/**
 * Hook for fetching user's gift card data with offline support
 * Implements NetworkFirst pattern: tries API first, falls back to cached data
 *
 * Used in: Dashboard (show user's QR and balance offline)
 */
export function useOfflineGiftCard(): UseOfflineGiftCardResult {
    const [data, setData] = useState<OfflineGiftCardData | null>(null);
    const [isLoading, setIsLoading] = useState(true);
    const [error, setError] = useState<string | null>(null);
    const [isOffline, setIsOffline] = useState(!navigator.onLine);

    useEffect(() => {
        const handleOnline = () => setIsOffline(false);
        const handleOffline = () => setIsOffline(true);

        window.addEventListener('online', handleOnline);
        window.addEventListener('offline', handleOffline);

        return () => {
            window.removeEventListener('online', handleOnline);
            window.removeEventListener('offline', handleOffline);
        };
    }, []);

    useEffect(() => {
        let isMounted = true;

        const fetchGiftCard = async () => {
            try {
                setIsLoading(true);
                setError(null);

                // Try to fetch from API (NetworkFirst)
                if (navigator.onLine) {
                    try {
                        const response = await fetch('/api/v1/me', {
                            method: 'GET',
                            headers: {
                                Accept: 'application/json',
                                'Content-Type': 'application/json',
                            },
                        });

                        if (response.ok) {
                            const json = await response.json();

                            if (isMounted) {
                                setData(json.data);

                                // Cache the data in localStorage for offline use
                                if (json.data) {
                                    localStorage.setItem(
                                        'offline_gift_card',
                                        JSON.stringify({
                                            data: json.data,
                                            cached_at: new Date().toISOString(),
                                        }),
                                    );
                                }
                            }
                            return;
                        }
                    } catch (apiError) {
                        // API call failed, will fall back to cache below
                    }
                }

                // Fallback to cached data
                const cached = localStorage.getItem('offline_gift_card');
                if (cached) {
                    try {
                        const { data: cachedData } = JSON.parse(cached);
                        if (isMounted) {
                            setData(cachedData);
                            if (!navigator.onLine) {
                                setError(
                                    'Viendo datos del último sincronización',
                                );
                            }
                        }
                        return;
                    } catch (parseError) {
                        // Error parsing cached data, continuing with empty state
                    }
                }

                // No data available
                if (isMounted) {
                    setData(null);
                    setError('No tienes una tarjeta QR asignada');
                }
            } finally {
                if (isMounted) {
                    setIsLoading(false);
                }
            }
        };

        fetchGiftCard();

        // Auto-refresh every 5 minutes when online
        const refreshInterval = setInterval(
            () => {
                if (navigator.onLine && isMounted) {
                    fetchGiftCard();
                }
            },
            5 * 60 * 1000,
        );

        return () => {
            clearInterval(refreshInterval);
            isMounted = false;
        };
    }, []);

    return {
        data,
        isLoading,
        error,
        isOffline,
    };
}
