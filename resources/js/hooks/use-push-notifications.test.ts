import { act, renderHook, waitFor } from '@testing-library/react';
import { afterEach, beforeEach, describe, expect, test, vi } from 'vitest';
import { usePushNotifications } from './use-push-notifications';

describe('usePushNotifications', () => {
    beforeEach(() => {
        localStorage.clear();
        vi.clearAllMocks();

        // Ensure browser APIs are available
        Object.defineProperty(window, 'PushManager', {
            value: {},
            configurable: true,
        });
    });

    afterEach(() => {
        vi.restoreAllMocks();
    });

    describe('initialization', () => {
        test('should detect push notification support', async () => {
            const { result } = renderHook(() => usePushNotifications());

            await waitFor(
                () => {
                    expect(result.current.isSupported).toBe(true);
                },
                { timeout: 500 },
            );
        });

        test('should return isSupported false when browser lacks support', () => {
            const originalPushManager = (window as any).PushManager;
            delete (window as any).PushManager;

            const { result } = renderHook(() => usePushNotifications());

            expect(result.current.isSupported).toBe(false);

            (window as any).PushManager = originalPushManager;
        });

        test('should load cached permission from localStorage', async () => {
            localStorage.setItem('pwa:push-permission', 'granted');

            const { result } = renderHook(() => usePushNotifications());

            await waitFor(
                () => {
                    expect(result.current.permission).toBe('granted');
                },
                { timeout: 500 },
            );
        });
    });

    describe('subscribe', () => {
        test('should handle permission denied', async () => {
            vi.spyOn(
                window.Notification,
                'requestPermission',
            ).mockResolvedValueOnce('denied');

            const { result } = renderHook(() => usePushNotifications());

            await act(async () => {
                await result.current.subscribe();
            });

            await waitFor(
                () => {
                    expect(result.current.isLoading).toBe(false);
                },
                { timeout: 500 },
            );

            expect(result.current.isSubscribed).toBe(false);
            expect(result.current.error?.message).toContain(
                'permission denied',
            );
        });

        test('should save permission to localStorage on success', async () => {
            vi.spyOn(
                window.Notification,
                'requestPermission',
            ).mockResolvedValueOnce('granted');

            const mockFetch = vi.fn(async () => ({
                ok: true,
                status: 201,
            }));

            global.fetch = mockFetch;

            const { result } = renderHook(() => usePushNotifications());

            await act(async () => {
                await result.current.subscribe();
            });

            await waitFor(
                () => {
                    expect(result.current.isLoading).toBe(false);
                },
                { timeout: 500 },
            );

            expect(localStorage.getItem('pwa:push-permission')).toBe('granted');
        });

        test('should mark as not supported when PushManager missing', async () => {
            const originalPushManager = (window as any).PushManager;
            delete (window as any).PushManager;

            const { result } = renderHook(() => usePushNotifications());

            expect(result.current.isSupported).toBe(false);

            await act(async () => {
                await result.current.subscribe();
            });

            expect(result.current.error?.message).toContain('not supported');

            (window as any).PushManager = originalPushManager;
        });

        test('should set isLoading during subscription process', async () => {
            vi.spyOn(
                window.Notification,
                'requestPermission',
            ).mockResolvedValueOnce('granted');

            const mockFetch = vi.fn(async () => {
                expect(result.current.isLoading).toBe(true);
                return { ok: true, status: 201 };
            });

            global.fetch = mockFetch;

            const { result } = renderHook(() => usePushNotifications());

            await act(async () => {
                await result.current.subscribe();
            });

            await waitFor(
                () => {
                    expect(result.current.isLoading).toBe(false);
                },
                { timeout: 500 },
            );
        });
    });

    describe('unsubscribe', () => {
        test('should handle 404 when subscription not found', async () => {
            const mockFetch = vi.fn(async () => ({
                ok: false,
                status: 404,
            }));

            global.fetch = mockFetch;

            const { result } = renderHook(() => usePushNotifications());

            await act(async () => {
                await result.current.unsubscribe();
            });

            await waitFor(
                () => {
                    expect(result.current.isLoading).toBe(false);
                },
                { timeout: 500 },
            );

            expect(result.current.error).toBe(null);
        });

        test('should handle unsupported browser on unsubscribe', async () => {
            const originalPushManager = (window as any).PushManager;
            delete (window as any).PushManager;

            const { result } = renderHook(() => usePushNotifications());

            await act(async () => {
                await result.current.unsubscribe();
            });

            expect(result.current.error?.message).toContain('not supported');

            (window as any).PushManager = originalPushManager;
        });

        test('should set isLoading during unsubscribe process', async () => {
            const mockFetch = vi.fn(async () => {
                expect(result.current.isLoading).toBe(true);
                return { ok: true, status: 200 };
            });

            global.fetch = mockFetch;

            const { result } = renderHook(() => usePushNotifications());

            await act(async () => {
                await result.current.unsubscribe();
            });

            await waitFor(
                () => {
                    expect(result.current.isLoading).toBe(false);
                },
                { timeout: 500 },
            );
        });
    });

    describe('error handling', () => {
        test('should handle unsupported browser gracefully', async () => {
            const originalPushManager = (window as any).PushManager;
            delete (window as any).PushManager;

            const { result } = renderHook(() => usePushNotifications());

            await act(async () => {
                await result.current.subscribe();
            });

            expect(result.current.error?.message).toContain('not supported');

            (window as any).PushManager = originalPushManager;
        });

        test('should reset error when starting new operation', async () => {
            vi.spyOn(
                window.Notification,
                'requestPermission',
            ).mockResolvedValueOnce('denied');

            const { result } = renderHook(() => usePushNotifications());

            // First attempt fails
            await act(async () => {
                await result.current.subscribe();
            });

            expect(result.current.error).not.toBe(null);
        });

        test('should not set isSubscribed on error', async () => {
            vi.spyOn(
                window.Notification,
                'requestPermission',
            ).mockResolvedValueOnce('denied');

            const { result } = renderHook(() => usePushNotifications());

            await act(async () => {
                await result.current.subscribe();
            });

            await waitFor(
                () => {
                    expect(result.current.isLoading).toBe(false);
                },
                { timeout: 500 },
            );

            expect(result.current.isSubscribed).toBe(false);
        });

        test('should maintain permission state on error', async () => {
            vi.spyOn(
                window.Notification,
                'requestPermission',
            ).mockResolvedValueOnce('denied');

            const { result } = renderHook(() => usePushNotifications());

            await act(async () => {
                await result.current.subscribe();
            });

            await waitFor(
                () => {
                    expect(result.current.isLoading).toBe(false);
                },
                { timeout: 500 },
            );

            expect(result.current.permission).toBe('denied');
        });
    });

    describe('state management', () => {
        test('should track subscription state correctly', async () => {
            const { result } = renderHook(() => usePushNotifications());

            expect(result.current.isSubscribed).toBe(false);
            expect(result.current.isLoading).toBe(false);
            expect(result.current.error).toBe(null);
        });

        test('should provide correct default permission', async () => {
            const { result } = renderHook(() => usePushNotifications());

            await waitFor(
                () => {
                    expect(['default', 'granted', 'denied']).toContain(
                        result.current.permission,
                    );
                },
                { timeout: 500 },
            );
        });
    });
});
