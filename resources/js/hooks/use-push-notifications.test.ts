import { renderHook, act, waitFor } from '@testing-library/react';
import { vi, describe, test, expect, beforeEach, afterEach } from 'vitest';
import { usePushNotifications } from './use-push-notifications';

describe('usePushNotifications', () => {
  beforeEach(() => {
    localStorage.clear();
    vi.clearAllMocks();
  });

  afterEach(() => {
    vi.restoreAllMocks();
  });

  describe('initialization', () => {
    test('should detect push notification support', () => {
      const { result } = renderHook(() => usePushNotifications());

      expect(result.current.isSupported).toBe(true);
      expect(result.current.permission).toBe('default');
      expect(result.current.isSubscribed).toBe(false);
    });

    test('should return isSupported false when browser lacks support', () => {
      // Mock missing PushManager
      const originalPushManager = (window as any).PushManager;
      delete (window as any).PushManager;

      const { result } = renderHook(() => usePushNotifications());

      expect(result.current.isSupported).toBe(false);

      (window as any).PushManager = originalPushManager;
    });

    test('should load cached permission from localStorage', async () => {
      localStorage.setItem('pwa:push-permission', 'granted');

      const { result } = renderHook(() => usePushNotifications());

      await waitFor(() => {
        expect(result.current.permission).toBe('granted');
      });
    });
  });

  describe('subscribe', () => {
    test('should successfully subscribe to push notifications', async () => {
      const mockFetch = vi.fn(async () => ({
        ok: true,
        status: 201,
        json: async () => ({ data: { id: 1 } }),
      }));

      global.fetch = mockFetch;

      const { result } = renderHook(() => usePushNotifications());

      await act(async () => {
        await result.current.subscribe();
      });

      await waitFor(() => {
        expect(result.current.isLoading).toBe(false);
      });

      expect(result.current.isSubscribed).toBe(true);
      expect(result.current.error).toBe(null);
      expect(mockFetch).toHaveBeenCalledWith(
        '/api/push-subscriptions',
        expect.objectContaining({
          method: 'POST',
          credentials: 'include',
        })
      );
    });

    test('should handle permission denied', async () => {
      vi.spyOn(window.Notification, 'requestPermission').mockResolvedValueOnce(
        'denied'
      );

      const { result } = renderHook(() => usePushNotifications());

      await act(async () => {
        await result.current.subscribe();
      });

      await waitFor(() => {
        expect(result.current.isLoading).toBe(false);
      });

      expect(result.current.isSubscribed).toBe(false);
      expect(result.current.error?.message).toContain('permission denied');
    });

    test('should handle missing VAPID key', async () => {
      vi.spyOn(window.Notification, 'requestPermission').mockResolvedValueOnce(
        'granted'
      );

      const originalEnv = import.meta.env.VITE_VAPID_PUBLIC_KEY;
      (import.meta.env as any).VITE_VAPID_PUBLIC_KEY = undefined;

      const { result } = renderHook(() => usePushNotifications());

      await act(async () => {
        await result.current.subscribe();
      });

      await waitFor(() => {
        expect(result.current.isLoading).toBe(false);
      });

      expect(result.current.error?.message).toContain('VAPID');

      (import.meta.env as any).VITE_VAPID_PUBLIC_KEY = originalEnv;
    });

    test('should retry on network error with exponential backoff', async () => {
      vi.spyOn(window.Notification, 'requestPermission').mockResolvedValueOnce(
        'granted'
      );

      let callCount = 0;
      const mockFetch = vi.fn(async () => {
        callCount++;
        if (callCount < 3) {
          throw new Error('Network error');
        }
        return {
          ok: true,
          status: 201,
          json: async () => ({ data: { id: 1 } }),
        };
      });

      global.fetch = mockFetch;

      const { result } = renderHook(() => usePushNotifications());

      await act(async () => {
        await result.current.subscribe();
      });

      await waitFor(() => {
        expect(result.current.isLoading).toBe(false);
      });

      // Should have retried twice before succeeding on the 3rd attempt
      expect(mockFetch).toHaveBeenCalledTimes(3);
      expect(result.current.isSubscribed).toBe(true);
    });

    test('should handle duplicate subscription (409 conflict)', async () => {
      vi.spyOn(window.Notification, 'requestPermission').mockResolvedValueOnce(
        'granted'
      );

      const mockFetch = vi.fn(async () => ({
        ok: false,
        status: 409,
      }));

      global.fetch = mockFetch;

      const { result } = renderHook(() => usePushNotifications());

      await act(async () => {
        await result.current.subscribe();
      });

      await waitFor(() => {
        expect(result.current.isLoading).toBe(false);
      });

      expect(result.current.isSubscribed).toBe(true);
      expect(result.current.error).toBe(null);
    });

    test('should not be supported error if browser lacks support', async () => {
      const originalPushManager = (window as any).PushManager;
      delete (window as any).PushManager;

      const { result } = renderHook(() => usePushNotifications());

      await act(async () => {
        await result.current.subscribe();
      });

      expect(result.current.error?.message).toContain('not supported');

      (window as any).PushManager = originalPushManager;
    });

    test('should save permission to localStorage', async () => {
      vi.spyOn(window.Notification, 'requestPermission').mockResolvedValueOnce(
        'granted'
      );

      const mockFetch = vi.fn(async () => ({
        ok: true,
        status: 201,
      }));

      global.fetch = mockFetch;

      const { result } = renderHook(() => usePushNotifications());

      await act(async () => {
        await result.current.subscribe();
      });

      await waitFor(() => {
        expect(result.current.isLoading).toBe(false);
      });

      expect(localStorage.getItem('pwa:push-permission')).toBe('granted');
    });
  });

  describe('unsubscribe', () => {
    test('should successfully unsubscribe from push notifications', async () => {
      const mockFetch = vi.fn(async () => ({
        ok: true,
        status: 200,
      }));

      global.fetch = mockFetch;

      const { result } = renderHook(() => usePushNotifications());

      await act(async () => {
        await result.current.unsubscribe();
      });

      await waitFor(() => {
        expect(result.current.isLoading).toBe(false);
      });

      expect(result.current.isSubscribed).toBe(false);
      expect(mockFetch).toHaveBeenCalledWith(
        '/api/push-subscriptions',
        expect.objectContaining({
          method: 'DELETE',
        })
      );
    });

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

      await waitFor(() => {
        expect(result.current.isLoading).toBe(false);
      });

      expect(result.current.error).toBe(null);
    });

    test('should not be supported error if browser lacks support', async () => {
      const originalPushManager = (window as any).PushManager;
      delete (window as any).PushManager;

      const { result } = renderHook(() => usePushNotifications());

      await act(async () => {
        await result.current.unsubscribe();
      });

      expect(result.current.error?.message).toContain('not supported');

      (window as any).PushManager = originalPushManager;
    });
  });

  describe('request headers', () => {
    test('should include CSRF token in request headers', async () => {
      vi.spyOn(window.Notification, 'requestPermission').mockResolvedValueOnce(
        'granted'
      );

      const mockFetch = vi.fn(async () => ({
        ok: true,
        status: 201,
        json: async () => ({ data: { id: 1 } }),
      }));

      global.fetch = mockFetch;

      const { result } = renderHook(() => usePushNotifications());

      await act(async () => {
        await result.current.subscribe();
      });

      await waitFor(() => {
        expect(result.current.isLoading).toBe(false);
      });

      expect(mockFetch).toHaveBeenCalledWith(
        '/api/push-subscriptions',
        expect.objectContaining({
          headers: expect.objectContaining({
            'X-Requested-With': 'XMLHttpRequest',
          }),
        })
      );
    });

    test('should include credentials in request', async () => {
      vi.spyOn(window.Notification, 'requestPermission').mockResolvedValueOnce(
        'granted'
      );

      const mockFetch = vi.fn(async () => ({
        ok: true,
        status: 201,
        json: async () => ({ data: { id: 1 } }),
      }));

      global.fetch = mockFetch;

      const { result } = renderHook(() => usePushNotifications());

      await act(async () => {
        await result.current.subscribe();
      });

      await waitFor(() => {
        expect(result.current.isLoading).toBe(false);
      });

      expect(mockFetch).toHaveBeenCalledWith(
        '/api/push-subscriptions',
        expect.objectContaining({
          credentials: 'include',
        })
      );
    });
  });
});
