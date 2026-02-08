/**
 * Custom Service Worker handlers for PWA push notifications
 * This file handles push events and notification interactions
 */

/// <reference lib="webworker" />

/* eslint-disable no-restricted-globals */

/**
 * Push event handler - receives push notifications from backend
 * and displays them to the user
 */
addEventListener('push', (event: PushEvent) => {
    try {
        const data = (event.data?.json() ?? {}) as {
            title?: string;
            body?: string;
            icon?: string;
            url?: string;
        };

        const notificationOptions: NotificationOptions = {
            body: data.body || 'Tienes una notificación nueva',
            icon: data.icon || '/icons/icon-192x192.png',
            badge: '/favicon.svg',
            tag: 'transaction-notification',
            requireInteraction: false,
            data: {
                url: data.url || '/dashboard',
                timestamp: Date.now(),
            },
        };

        event.waitUntil(
            self.registration.showNotification(
                data.title || 'QR Made',
                notificationOptions,
            ),
        );
    } catch (error) {
        console.error('Error handling push event:', error);
    }
});

/**
 * Notification click handler - navigates to dashboard when user clicks notification
 */
addEventListener('notificationclick', (event: NotificationEvent) => {
    event.notification.close();

    // If user clicked "close" action, just dismiss
    if (event.action === 'close') {
        return;
    }

    // Get the URL from notification data (always /dashboard)
    const url =
        (event.notification.data as { url: string }).url || '/dashboard';

    // Validate URL to prevent navigation to external sites
    if (!url.startsWith('/')) {
        console.warn('Invalid notification URL:', url);
        return;
    }

    event.waitUntil(
        self.clients
            .matchAll({ type: 'window' })
            .then((clientList: Client[]) => {
                // If dashboard is already open, focus the window
                for (const client of clientList) {
                    if (client.url.includes(url) && 'focus' in client) {
                        return (client as WindowClient).focus();
                    }
                }

                // Otherwise open new window to dashboard
                if (self.clients.openWindow) {
                    return self.clients.openWindow(url);
                }
            }),
    );
});

/**
 * Optional: Notification close handler for analytics
 * Track when users dismiss notifications
 */
addEventListener('notificationclose', (event: NotificationEvent) => {
    // Could send analytics event here if needed
    console.debug('Notification dismissed:', event.notification.data);
});
