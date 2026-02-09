import { vi } from 'vitest';

/**
 * Setup all global mocks for testing
 * Call this in beforeEach() to ensure clean state before each test
 *
 * @example
 * describe('MyComponent', () => {
 *   beforeEach(() => {
 *     setupGlobalMocks();
 *   });
 *
 *   test('should work', () => {
 *     // test code
 *   });
 * });
 */
export function setupGlobalMocks(): void {
  // Clear localStorage
  localStorage.clear();
  vi.clearAllMocks();

  // Mock Service Worker
  setupServiceWorkerMock();

  // Mock Notification API
  setupNotificationMock();

  // Mock fetch
  setupFetchMock();

  // Mock beforeinstallprompt event
  setupBeforeInstallPromptMock();
}

/**
 * Create a mock Response object for fetch
 *
 * @template T - The type of the response data
 * @param data - The data to return in the response
 * @param status - HTTP status code (default: 200)
 * @returns A mock Response object with json() method
 *
 * @example
 * const response = createFetchResponse({ id: 1, name: 'Test' }, 200);
 * expect(await response.json()).toEqual({ id: 1, name: 'Test' });
 */
export function createFetchResponse<T>(
  data: T,
  status: number = 200
): Response {
  return new Response(JSON.stringify(data), {
    status,
    headers: {
      'Content-Type': 'application/json',
    },
  });
}

/**
 * Mock Service Worker registration with push manager
 * Includes subscribe() and getSubscription() methods
 *
 * @returns Mock ServiceWorkerRegistration object
 *
 * @example
 * const registration = mockServiceWorkerRegistration();
 * expect(registration.pushManager.subscribe).toBeDefined();
 */
export function mockServiceWorkerRegistration(): ServiceWorkerRegistration {
  return {
    active: undefined,
    installing: undefined,
    waiting: undefined,
    controller: undefined,
    scope: 'https://qrmadearmando.test/',
    updateViaCache: 'imports',
    navigationPreload: {
      enable: vi.fn(),
      disable: vi.fn(),
      getState: vi.fn(() => Promise.resolve({ enabled: false })),
    } as any,
    pushManager: {
      getSubscription: vi.fn(() =>
        Promise.resolve(null as any as PushSubscription)
      ),
      subscribe: vi.fn(() =>
        Promise.resolve({
          endpoint: 'https://fcm.googleapis.com/fcm/send/test-subscription-id',
          expirationTime: null,
          getKey: vi.fn((key: string) => {
            if (key === 'p256dh') return new Uint8Array(65);
            if (key === 'auth') return new Uint8Array(16);
            return null;
          }),
          unsubscribe: vi.fn(() => Promise.resolve(true)),
          toJSON: vi.fn(() => ({
            endpoint: 'https://fcm.googleapis.com/fcm/send/test-subscription-id',
            expirationTime: null,
            keys: {
              p256dh: 'test-p256dh',
              auth: 'test-auth',
            },
          })),
        } as any as PushSubscription)
      ),
    } as any,
    unregister: vi.fn(() => Promise.resolve(true)),
    update: vi.fn(() => Promise.resolve()),
    getNotifications: vi.fn(() => Promise.resolve([])),
    showNotification: vi.fn(() => Promise.resolve()),
  } as any as ServiceWorkerRegistration;
}

/**
 * Mock notification permission
 * Sets window.Notification.permission and requestPermission method
 *
 * @param permission - The permission state to set ('default', 'granted', 'denied')
 *
 * @example
 * mockNotificationPermission('granted');
 * expect(window.Notification.permission).toBe('granted');
 */
export function mockNotificationPermission(
  permission: NotificationPermission
): void {
  Object.defineProperty(window, 'Notification', {
    value: {
      permission,
      requestPermission: vi.fn(async () => permission),
    },
    writable: true,
    configurable: true,
  });
}

/**
 * Reset all mocks and clear state
 * Call this in afterEach() to clean up after tests
 *
 * @example
 * afterEach(() => {
 *   resetAllMocks();
 * });
 */
export function resetAllMocks(): void {
  vi.clearAllMocks();
  vi.restoreAllMocks();
  localStorage.clear();
}

/**
 * Mock matchMedia for responsive design testing
 *
 * @param query - Media query string
 * @param matches - Whether the media query matches
 *
 * @example
 * mockMatchMedia('(display-mode: standalone)', true);
 * expect(window.matchMedia('(display-mode: standalone)').matches).toBe(true);
 */
export function mockMatchMedia(
  query: string,
  matches: boolean = true
): MediaQueryList {
  return {
    media: query,
    matches,
    addListener: vi.fn(),
    removeListener: vi.fn(),
    addEventListener: vi.fn(),
    removeEventListener: vi.fn(),
    dispatchEvent: vi.fn(),
    onchange: null,
  } as any as MediaQueryList;
}

/**
 * Mock beforeinstallprompt event
 * Allows tests to trigger the install prompt
 *
 * @example
 * const event = new Event('beforeinstallprompt');
 * // Set prompt and userChoice methods
 * window.dispatchEvent(event);
 */
export function createBeforeInstallPromptEvent(options?: {
  prompt?: () => Promise<void>;
  userChoice?: Promise<{ outcome: 'accepted' | 'dismissed' }>;
}): Event {
  const event = new Event('beforeinstallprompt', {
    bubbles: true,
    cancelable: true,
  });

  Object.defineProperty(event, 'preventDefault', {
    value: vi.fn(),
    writable: true,
    configurable: true,
  });

  Object.defineProperty(event, 'prompt', {
    value: options?.prompt || vi.fn(() => Promise.resolve()),
    writable: true,
    configurable: true,
  });

  Object.defineProperty(event, 'userChoice', {
    value: options?.userChoice || Promise.resolve({ outcome: 'accepted' as const }),
    writable: true,
    configurable: true,
  });

  return event;
}

/**
 * Mock appinstalled event
 * Fired when PWA is successfully installed
 */
export function createAppInstalledEvent(): Event {
  return new Event('appinstalled', {
    bubbles: true,
    cancelable: false,
  });
}

// ============================================================================
// Private setup functions
// ============================================================================

function setupServiceWorkerMock(): void {
  const registration = mockServiceWorkerRegistration();

  Object.defineProperty(navigator, 'serviceWorker', {
    value: {
      register: vi.fn(() => Promise.resolve(registration)),
      ready: Promise.resolve(registration),
      controller: undefined,
      getRegistrations: vi.fn(() => Promise.resolve([registration])),
      oncontrollerchange: null,
      onmessage: null,
      onerror: null,
    },
    writable: true,
    configurable: true,
  });
}

function setupNotificationMock(): void {
  mockNotificationPermission('default');
}

function setupFetchMock(): void {
  global.fetch = vi.fn();
}

function setupBeforeInstallPromptMock(): void {
  Object.defineProperty(window, 'beforeinstallprompt', {
    value: undefined,
    writable: true,
    configurable: true,
  });
}
