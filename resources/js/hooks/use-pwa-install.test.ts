import { renderHook, act, waitFor } from '@testing-library/react';
import { vi, describe, test, expect, beforeEach, afterEach } from 'vitest';
import { usePwaInstall } from './use-pwa-install';

describe('usePwaInstall', () => {
  beforeEach(() => {
    localStorage.clear();
    vi.clearAllMocks();
  });

  afterEach(() => {
    vi.restoreAllMocks();
  });

  describe('initialization', () => {
    test('should detect PWA install support', () => {
      // Ensure beforeinstallprompt is available
      (window as any).beforeinstallprompt = undefined;

      const { result } = renderHook(() => usePwaInstall());

      expect(result.current.isSupported).toBe(true);
      expect(result.current.canInstall).toBe(false);
      expect(result.current.isInstalled).toBe(false);
    });

    test('should return isSupported false when browser lacks support', () => {
      const originalBeforeInstallPrompt = (window as any).beforeinstallprompt;
      delete (window as any).beforeinstallprompt;

      const { result } = renderHook(() => usePwaInstall());

      expect(result.current.isSupported).toBe(false);

      (window as any).beforeinstallprompt = originalBeforeInstallPrompt;
    });

    test('should detect if app is already installed (standalone mode)', () => {
      const originalMatchMedia = window.matchMedia;
      window.matchMedia = vi.fn(() => ({
        matches: true,
        media: '(display-mode: standalone)',
        addListener: vi.fn(),
        removeListener: vi.fn(),
        addEventListener: vi.fn(),
        removeEventListener: vi.fn(),
        dispatchEvent: vi.fn(),
      })) as any;

      const { result } = renderHook(() => usePwaInstall());

      expect(result.current.isInstalled).toBe(true);
      expect(result.current.canInstall).toBe(false);

      window.matchMedia = originalMatchMedia;
    });

    test('should detect if app is already installed (iOS)', () => {
      Object.defineProperty(navigator, 'standalone', {
        value: true,
        configurable: true,
      });

      const { result } = renderHook(() => usePwaInstall());

      expect(result.current.isInstalled).toBe(true);

      Object.defineProperty(navigator, 'standalone', {
        value: undefined,
        configurable: true,
      });
    });
  });

  describe('install prompt', () => {
    test('should show install prompt when beforeinstallprompt is triggered', async () => {
      const { result } = renderHook(() => usePwaInstall());

      expect(result.current.canInstall).toBe(false);

      act(() => {
        const event = new Event('beforeinstallprompt');
        Object.defineProperty(event, 'preventDefault', {
          value: vi.fn(),
          configurable: true,
        });
        window.dispatchEvent(event);
      });

      await waitFor(
        () => {
          expect(result.current.canInstall).toBe(true);
        },
        { timeout: 500 }
      );
    });

    test('should not show prompt if already dismissed within 14 days', async () => {
      localStorage.setItem(
        'pwa:install-dismissed-timestamp',
        Date.now().toString()
      );

      const { result } = renderHook(() => usePwaInstall());

      act(() => {
        const event = new Event('beforeinstallprompt');
        Object.defineProperty(event, 'preventDefault', {
          value: vi.fn(),
          configurable: true,
        });
        window.dispatchEvent(event);
      });

      expect(result.current.canInstall).toBe(false);
    });

    test('should show prompt if dismissal period has expired', async () => {
      // Set dismissal to 15 days ago
      const fifteenDaysAgo = Date.now() - 15 * 24 * 60 * 60 * 1000;
      localStorage.setItem(
        'pwa:install-dismissed-timestamp',
        fifteenDaysAgo.toString()
      );

      const { result } = renderHook(() => usePwaInstall());

      act(() => {
        const event = new Event('beforeinstallprompt');
        Object.defineProperty(event, 'preventDefault', {
          value: vi.fn(),
          configurable: true,
        });
        window.dispatchEvent(event);
      });

      await waitFor(
        () => {
          expect(result.current.canInstall).toBe(true);
        },
        { timeout: 500 }
      );
    });

    test('should not show prompt if app is already installed', async () => {
      const originalMatchMedia = window.matchMedia;
      window.matchMedia = vi.fn(() => ({
        matches: true,
        media: '(display-mode: standalone)',
        addListener: vi.fn(),
        removeListener: vi.fn(),
        addEventListener: vi.fn(),
        removeEventListener: vi.fn(),
        dispatchEvent: vi.fn(),
      })) as any;

      const { result } = renderHook(() => usePwaInstall());

      act(() => {
        const event = new Event('beforeinstallprompt');
        Object.defineProperty(event, 'preventDefault', {
          value: vi.fn(),
          configurable: true,
        });
        window.dispatchEvent(event);
      });

      expect(result.current.canInstall).toBe(false);

      window.matchMedia = originalMatchMedia;
    });
  });

  describe('install method', () => {
    test('should trigger install prompt', async () => {
      const mockPrompt = vi.fn();
      const mockUserChoice = Promise.resolve({ outcome: 'accepted' as const });

      const { result } = renderHook(() => usePwaInstall());

      act(() => {
        const event = new Event('beforeinstallprompt');
        Object.defineProperty(event, 'preventDefault', {
          value: vi.fn(),
          configurable: true,
        });
        Object.defineProperty(event, 'prompt', {
          value: mockPrompt,
          configurable: true,
        });
        Object.defineProperty(event, 'userChoice', {
          value: mockUserChoice,
          configurable: true,
        });
        window.dispatchEvent(event);
      });

      await waitFor(
        () => {
          expect(result.current.canInstall).toBe(true);
        },
        { timeout: 500 }
      );

      await act(async () => {
        await result.current.install();
      });

      expect(mockPrompt).toHaveBeenCalled();
      expect(result.current.isInstalled).toBe(true);
      expect(result.current.canInstall).toBe(false);
    });

    test('should handle user accepting install', async () => {
      const mockPrompt = vi.fn();
      const mockUserChoice = Promise.resolve({ outcome: 'accepted' as const });

      const { result } = renderHook(() => usePwaInstall());

      act(() => {
        const event = new Event('beforeinstallprompt');
        Object.defineProperty(event, 'preventDefault', {
          value: vi.fn(),
          configurable: true,
        });
        Object.defineProperty(event, 'prompt', {
          value: mockPrompt,
          configurable: true,
        });
        Object.defineProperty(event, 'userChoice', {
          value: mockUserChoice,
          configurable: true,
        });
        window.dispatchEvent(event);
      });

      await waitFor(
        () => {
          expect(result.current.canInstall).toBe(true);
        },
        { timeout: 500 }
      );

      await act(async () => {
        await result.current.install();
      });

      expect(result.current.isInstalled).toBe(true);
    });

    test('should handle user dismissing install', async () => {
      const mockPrompt = vi.fn();
      const mockUserChoice = Promise.resolve({ outcome: 'dismissed' as const });

      const { result } = renderHook(() => usePwaInstall());

      act(() => {
        const event = new Event('beforeinstallprompt');
        Object.defineProperty(event, 'preventDefault', {
          value: vi.fn(),
          configurable: true,
        });
        Object.defineProperty(event, 'prompt', {
          value: mockPrompt,
          configurable: true,
        });
        Object.defineProperty(event, 'userChoice', {
          value: mockUserChoice,
          configurable: true,
        });
        window.dispatchEvent(event);
      });

      await waitFor(
        () => {
          expect(result.current.canInstall).toBe(true);
        },
        { timeout: 500 }
      );

      await act(async () => {
        await result.current.install();
      });

      expect(result.current.isInstalled).toBe(false);
      expect(result.current.canInstall).toBe(false);
      expect(localStorage.getItem('pwa:install-dismissed-timestamp')).not.toBe(
        null
      );
    });

    test('should throw error if prompt is not available', async () => {
      const { result } = renderHook(() => usePwaInstall());

      await expect(result.current.install()).rejects.toThrow(
        'Install prompt not available'
      );
    });
  });

  describe('dismiss method', () => {
    test('should save dismissal timestamp to localStorage', () => {
      const { result } = renderHook(() => usePwaInstall());

      act(() => {
        result.current.dismiss();
      });

      const dismissalTimestamp = localStorage.getItem(
        'pwa:install-dismissed-timestamp'
      );
      expect(dismissalTimestamp).not.toBe(null);
      expect(parseInt(dismissalTimestamp!, 10)).toBeGreaterThan(0);
    });

    test('should hide install prompt after dismissal', async () => {
      const { result } = renderHook(() => usePwaInstall());

      act(() => {
        const event = new Event('beforeinstallprompt');
        Object.defineProperty(event, 'preventDefault', {
          value: vi.fn(),
          configurable: true,
        });
        window.dispatchEvent(event);
      });

      await waitFor(
        () => {
          expect(result.current.canInstall).toBe(true);
        },
        { timeout: 500 }
      );

      act(() => {
        result.current.dismiss();
      });

      expect(result.current.canInstall).toBe(false);
    });

    test('should prevent showing prompt for 14 days after dismissal', async () => {
      const { result } = renderHook(() => usePwaInstall());

      act(() => {
        result.current.dismiss();
      });

      const dismissalTimestamp = localStorage.getItem(
        'pwa:install-dismissed-timestamp'
      );
      expect(dismissalTimestamp).not.toBe(null);

      act(() => {
        const event = new Event('beforeinstallprompt');
        Object.defineProperty(event, 'preventDefault', {
          value: vi.fn(),
          configurable: true,
        });
        window.dispatchEvent(event);
      });

      expect(result.current.canInstall).toBe(false);
    });
  });

  describe('app installed event', () => {
    test('should mark app as installed when appinstalled event fires', async () => {
      const { result } = renderHook(() => usePwaInstall());

      expect(result.current.isInstalled).toBe(false);

      act(() => {
        window.dispatchEvent(new Event('appinstalled'));
      });

      await waitFor(
        () => {
          expect(result.current.isInstalled).toBe(true);
          expect(result.current.canInstall).toBe(false);
        },
        { timeout: 500 }
      );
    });
  });
});
