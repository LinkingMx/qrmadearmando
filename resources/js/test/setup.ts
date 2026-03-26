import '@testing-library/jest-dom';
import { cleanup } from '@testing-library/react';
import { afterEach, vi } from 'vitest';

// Cleanup after each test
afterEach(() => {
    cleanup();
});

// Mock localStorage
const localStorageMock = (() => {
    let store: Record<string, string> = {};

    return {
        getItem: (key: string) => store[key] || null,
        setItem: (key: string, value: string) => {
            store[key] = value.toString();
        },
        removeItem: (key: string) => {
            delete store[key];
        },
        clear: () => {
            store = {};
        },
    };
})();

Object.defineProperty(window, 'localStorage', {
    value: localStorageMock,
});

// Mock Service Worker registration
const mockServiceWorkerRegistration = {
    active: undefined,
    installing: undefined,
    waiting: undefined,
    controller: undefined,
    pushManager: {
        getSubscription: vi.fn(() => Promise.resolve(null)),
        subscribe: vi.fn(() =>
            Promise.resolve({
                endpoint: 'https://fcm.googleapis.com/fcm/send/test',
                getKey: vi.fn((key: string) => {
                    if (key === 'p256dh') return new Uint8Array(65);
                    if (key === 'auth') return new Uint8Array(16);
                    return null;
                }),
                unsubscribe: vi.fn(() => Promise.resolve(true)),
            }),
        ),
    },
};

Object.defineProperty(navigator, 'serviceWorker', {
    value: {
        register: vi.fn(() => Promise.resolve(mockServiceWorkerRegistration)),
        ready: Promise.resolve(mockServiceWorkerRegistration),
        controller: undefined,
        getRegistrations: vi.fn(() => Promise.resolve([])),
    },
    configurable: true,
});

// Mock Notification API
Object.defineProperty(window, 'Notification', {
    value: {
        permission: 'default' as NotificationPermission,
        requestPermission: vi.fn(
            async () => 'granted' as NotificationPermission,
        ),
    },
    configurable: true,
});

// Mock beforeinstallprompt event
Object.defineProperty(window, 'beforeinstallprompt', {
    value: undefined,
    writable: true,
    configurable: true,
});
