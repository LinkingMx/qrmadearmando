import { useEffect, useState } from 'react';

interface UsePushNotificationsReturn {
    isSupported: boolean;
    isSubscribed: boolean;
    isLoading: boolean;
    error: Error | null;
    subscribe: () => Promise<void>;
    unsubscribe: () => Promise<void>;
    permission: NotificationPermission;
}

function urlBase64ToUint8Array(base64String: string): Uint8Array {
    const padding = '='.repeat((4 - (base64String.length % 4)) % 4);
    const base64 = (base64String + padding)
        .replace(/\-/g, '+')
        .replace(/_/g, '/');
    const rawData = window.atob(base64);
    const outputArray = new Uint8Array(rawData.length);

    for (let i = 0; i < rawData.length; ++i) {
        outputArray[i] = rawData.charCodeAt(i);
    }

    return outputArray;
}

export function usePushNotifications(): UsePushNotificationsReturn {
    const [isSubscribed, setIsSubscribed] = useState(false);
    const [isLoading, setIsLoading] = useState(false);
    const [error, setError] = useState<Error | null>(null);
    const [permission, setPermission] =
        useState<NotificationPermission>('default');

    const isSupported =
        typeof window !== 'undefined' &&
        'serviceWorker' in navigator &&
        'PushManager' in window &&
        'Notification' in window;

    // Initialize subscription state on mount
    useEffect(() => {
        if (!isSupported) return;

        const initializeSubscription = async () => {
            try {
                // Check if already subscribed
                const registration = await navigator.serviceWorker.ready;
                const subscription =
                    await registration.pushManager.getSubscription();

                setIsSubscribed(!!subscription);
                setPermission(Notification.permission);

                // Load cached permission state
                const cachedPermission = localStorage.getItem(
                    'pwa:push-permission',
                );
                if (cachedPermission) {
                    setPermission(cachedPermission as NotificationPermission);
                }
            } catch (err) {
                // Error initializing push notifications silently
            }
        };

        initializeSubscription();
    }, [isSupported]);

    const subscribe = async () => {
        if (!isSupported) {
            setError(
                new Error(
                    'Push notifications are not supported in this browser',
                ),
            );
            return;
        }

        setIsLoading(true);
        setError(null);

        try {
            // Request notification permission
            const permission = await Notification.requestPermission();
            setPermission(permission);
            localStorage.setItem('pwa:push-permission', permission);

            if (permission !== 'granted') {
                setError(new Error('Notification permission denied'));
                setIsLoading(false);
                return;
            }

            // Get VAPID public key
            const vapidPublicKey = import.meta.env.VITE_VAPID_PUBLIC_KEY;
            if (!vapidPublicKey) {
                throw new Error('VAPID public key not configured');
            }

            // Get service worker registration
            const registration = await navigator.serviceWorker.ready;

            // Subscribe to push
            const subscription = await registration.pushManager.subscribe({
                userVisibleOnly: true,
                applicationServerKey: urlBase64ToUint8Array(
                    vapidPublicKey,
                ) as BufferSource,
            });

            // Send subscription to backend with retry logic
            let attempts = 0;
            const maxAttempts = 3;
            const delays = [1000, 2000, 4000]; // exponential backoff

            while (attempts < maxAttempts) {
                try {
                    const response = await fetch('/api/push-subscriptions', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        credentials: 'include',
                        body: JSON.stringify({
                            endpoint: subscription.endpoint,
                            publicKey: subscription.getKey?.('p256dh')
                                ? btoa(
                                      String.fromCharCode.apply(
                                          null,
                                          Array.from(
                                              subscription.getKey('p256dh') ||
                                                  new Uint8Array(),
                                          ) as number[],
                                      ),
                                  )
                                : '',
                            authToken: subscription.getKey?.('auth')
                                ? btoa(
                                      String.fromCharCode.apply(
                                          null,
                                          Array.from(
                                              subscription.getKey('auth') ||
                                                  new Uint8Array(),
                                          ) as number[],
                                      ),
                                  )
                                : '',
                        }),
                    });

                    if (!response.ok) {
                        if (response.status === 409) {
                            // Already subscribed, that's fine
                            setIsSubscribed(true);
                            setIsLoading(false);
                            return;
                        }
                        throw new Error(`HTTP ${response.status}`);
                    }

                    setIsSubscribed(true);
                    setIsLoading(false);
                    return;
                } catch (err) {
                    attempts++;
                    if (attempts < maxAttempts) {
                        await new Promise((resolve) =>
                            setTimeout(resolve, delays[attempts - 1]),
                        );
                    } else {
                        throw err;
                    }
                }
            }
        } catch (err) {
            const error =
                err instanceof Error
                    ? err
                    : new Error('Failed to subscribe to push notifications');
            setError(error);
            setIsSubscribed(false);
            setIsLoading(false);
        }
    };

    const unsubscribe = async () => {
        if (!isSupported) {
            setError(
                new Error(
                    'Push notifications are not supported in this browser',
                ),
            );
            return;
        }

        setIsLoading(true);
        setError(null);

        try {
            // Get current subscription
            const registration = await navigator.serviceWorker.ready;
            const subscription =
                await registration.pushManager.getSubscription();

            if (!subscription) {
                setIsSubscribed(false);
                setIsLoading(false);
                return;
            }

            // Delete from backend
            const response = await fetch('/api/push-subscriptions', {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'include',
                body: JSON.stringify({
                    endpoint: subscription.endpoint,
                }),
            });

            if (!response.ok && response.status !== 404) {
                throw new Error(`HTTP ${response.status}`);
            }

            // Unsubscribe from browser
            await subscription.unsubscribe();
            setIsSubscribed(false);
            setIsLoading(false);
        } catch (err) {
            const error =
                err instanceof Error
                    ? err
                    : new Error(
                          'Failed to unsubscribe from push notifications',
                      );
            setError(error);
            setIsLoading(false);
        }
    };

    return {
        isSupported,
        isSubscribed,
        isLoading,
        error,
        subscribe,
        unsubscribe,
        permission,
    };
}
