import { render, screen, fireEvent, waitFor } from '@testing-library/react';
import { vi, describe, test, expect, beforeEach, afterEach } from 'vitest';
import { NotificationBell } from './notification-bell';
import * as PushNotificationsHook from '@/hooks/use-push-notifications';

// Mock the hook
vi.mock('@/hooks/use-push-notifications', () => ({
  usePushNotifications: vi.fn(),
}));

// Mock Radix UI components with proper event handling
vi.mock('@/components/ui/button', () => ({
  Button: ({ onClick, disabled, children, ...props }: any) => (
    <button onClick={onClick} disabled={disabled} {...props}>
      {children}
    </button>
  ),
}));

vi.mock('@/components/ui/tooltip', () => ({
  Tooltip: ({ children }: any) => <div>{children}</div>,
  TooltipTrigger: ({ children, asChild }: any) => <div>{children}</div>,
  TooltipContent: ({ children }: any) => <div>{children}</div>,
  TooltipProvider: ({ children }: any) => <div>{children}</div>,
}));

// Mock lucide-react
vi.mock('lucide-react', () => ({
  Bell: () => <div data-testid="bell-icon">Bell</div>,
  Loader2: () => <div data-testid="loader-icon">Loader</div>,
}));

describe('NotificationBell', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    vi.useFakeTimers();
    localStorage.clear();
  });

  afterEach(() => {
    vi.restoreAllMocks();
    vi.useRealTimers();
  });

  describe('rendering', () => {
    test('should render null when push notifications are not supported', () => {
      vi.spyOn(PushNotificationsHook, 'usePushNotifications').mockReturnValue({
        isSupported: false,
        isSubscribed: false,
        isLoading: false,
        error: null,
        subscribe: vi.fn(),
        unsubscribe: vi.fn(),
        permission: 'default',
      });

      const { container } = render(<NotificationBell />);

      expect(container.firstChild).toBe(null);
    });

    test('should render bell icon when supported', () => {
      vi.spyOn(PushNotificationsHook, 'usePushNotifications').mockReturnValue({
        isSupported: true,
        isSubscribed: false,
        isLoading: false,
        error: null,
        subscribe: vi.fn(),
        unsubscribe: vi.fn(),
        permission: 'default',
      });

      render(<NotificationBell />);

      expect(screen.getByTestId('bell-icon')).toBeInTheDocument();
    });

    test('should apply custom className to outer container', () => {
      vi.spyOn(PushNotificationsHook, 'usePushNotifications').mockReturnValue({
        isSupported: true,
        isSubscribed: false,
        isLoading: false,
        error: null,
        subscribe: vi.fn(),
        unsubscribe: vi.fn(),
        permission: 'default',
      });

      const { container } = render(<NotificationBell className="custom-class" />);

      // Find the outer div that contains the Tooltip
      const outerDiv = container.querySelector('.custom-class');
      expect(outerDiv).toBeInTheDocument();
      expect(outerDiv).toHaveClass('relative');
      expect(outerDiv).toHaveClass('custom-class');
    });
  });

  describe('badge color', () => {
    test('should show green badge when subscribed', () => {
      vi.spyOn(PushNotificationsHook, 'usePushNotifications').mockReturnValue({
        isSupported: true,
        isSubscribed: true,
        isLoading: false,
        error: null,
        subscribe: vi.fn(),
        unsubscribe: vi.fn(),
        permission: 'granted',
      });

      const { container } = render(<NotificationBell />);

      const badge = container.querySelector('span');
      expect(badge).toHaveClass('bg-green-500');
    });

    test('should show red badge when not subscribed', () => {
      vi.spyOn(PushNotificationsHook, 'usePushNotifications').mockReturnValue({
        isSupported: true,
        isSubscribed: false,
        isLoading: false,
        error: null,
        subscribe: vi.fn(),
        unsubscribe: vi.fn(),
        permission: 'default',
      });

      const { container } = render(<NotificationBell />);

      const badge = container.querySelector('span');
      expect(badge).toHaveClass('bg-red-500');
    });

    test('should show gray badge when loading', () => {
      vi.spyOn(PushNotificationsHook, 'usePushNotifications').mockReturnValue({
        isSupported: true,
        isSubscribed: false,
        isLoading: true,
        error: null,
        subscribe: vi.fn(),
        unsubscribe: vi.fn(),
        permission: 'default',
      });

      const { container } = render(<NotificationBell />);

      const badge = container.querySelector('span');
      expect(badge).toHaveClass('bg-gray-400');
    });
  });

  describe('tooltip', () => {
    test('should show "Activar notificaciones" when not subscribed', () => {
      vi.spyOn(PushNotificationsHook, 'usePushNotifications').mockReturnValue({
        isSupported: true,
        isSubscribed: false,
        isLoading: false,
        error: null,
        subscribe: vi.fn(),
        unsubscribe: vi.fn(),
        permission: 'default',
      });

      render(<NotificationBell />);

      expect(screen.getByText('Activar notificaciones')).toBeInTheDocument();
    });

    test('should show "Desactivar notificaciones" when subscribed', () => {
      vi.spyOn(PushNotificationsHook, 'usePushNotifications').mockReturnValue({
        isSupported: true,
        isSubscribed: true,
        isLoading: false,
        error: null,
        subscribe: vi.fn(),
        unsubscribe: vi.fn(),
        permission: 'granted',
      });

      render(<NotificationBell />);

      expect(
        screen.getByText('Desactivar notificaciones')
      ).toBeInTheDocument();
    });
  });

  describe('loading state', () => {
    test('should show loader when loading', () => {
      vi.spyOn(PushNotificationsHook, 'usePushNotifications').mockReturnValue({
        isSupported: true,
        isSubscribed: false,
        isLoading: true,
        error: null,
        subscribe: vi.fn(),
        unsubscribe: vi.fn(),
        permission: 'default',
      });

      render(<NotificationBell />);

      expect(screen.getByTestId('loader-icon')).toBeInTheDocument();
    });

    test('should disable button when loading', () => {
      vi.spyOn(PushNotificationsHook, 'usePushNotifications').mockReturnValue({
        isSupported: true,
        isSubscribed: false,
        isLoading: true,
        error: null,
        subscribe: vi.fn(),
        unsubscribe: vi.fn(),
        permission: 'default',
      });

      render(<NotificationBell />);

      const button = screen.getByRole('button');
      expect(button).toBeDisabled();
    });

    test('should enable button when not loading', () => {
      vi.spyOn(PushNotificationsHook, 'usePushNotifications').mockReturnValue({
        isSupported: true,
        isSubscribed: false,
        isLoading: false,
        error: null,
        subscribe: vi.fn(),
        unsubscribe: vi.fn(),
        permission: 'default',
      });

      render(<NotificationBell />);

      const button = screen.getByRole('button');
      expect(button).not.toBeDisabled();
    });
  });

  describe('toggle functionality', () => {
    test('should render subscribe button when not subscribed', () => {
      vi.spyOn(PushNotificationsHook, 'usePushNotifications').mockReturnValue({
        isSupported: true,
        isSubscribed: false,
        isLoading: false,
        error: null,
        subscribe: vi.fn(),
        unsubscribe: vi.fn(),
        permission: 'default',
      });

      render(<NotificationBell />);

      const button = screen.getByRole('button');
      expect(button).toBeInTheDocument();
      expect(button).toHaveAttribute('aria-label', 'Activar notificaciones');
    });

    test('should render unsubscribe button when subscribed', () => {
      vi.spyOn(PushNotificationsHook, 'usePushNotifications').mockReturnValue({
        isSupported: true,
        isSubscribed: true,
        isLoading: false,
        error: null,
        subscribe: vi.fn(),
        unsubscribe: vi.fn(),
        permission: 'granted',
      });

      render(<NotificationBell />);

      const button = screen.getByRole('button');
      expect(button).toBeInTheDocument();
      expect(button).toHaveAttribute('aria-label', 'Desactivar notificaciones');
    });
  });

  describe('error handling', () => {
    test('should render error container when error exists', () => {
      const error = new Error('Test error message');

      vi.spyOn(PushNotificationsHook, 'usePushNotifications').mockReturnValue({
        isSupported: true,
        isSubscribed: false,
        isLoading: false,
        error,
        subscribe: vi.fn(),
        unsubscribe: vi.fn(),
        permission: 'default',
      });

      render(<NotificationBell />);

      // Check that the error toast container exists
      expect(screen.getByText('Test error message')).toBeInTheDocument();
    });

    test('should not display error toast when no error', () => {
      vi.spyOn(PushNotificationsHook, 'usePushNotifications').mockReturnValue({
        isSupported: true,
        isSubscribed: false,
        isLoading: false,
        error: null,
        subscribe: vi.fn(),
        unsubscribe: vi.fn(),
        permission: 'default',
      });

      const { container } = render(<NotificationBell />);

      const errorToast = container.querySelector(
        'div.bg-red-500.text-white'
      );
      expect(errorToast).not.toBeInTheDocument();
    });

    test('should render error with correct styling', () => {
      const error = new Error('Network error');

      vi.spyOn(PushNotificationsHook, 'usePushNotifications').mockReturnValue({
        isSupported: true,
        isSubscribed: false,
        isLoading: false,
        error,
        subscribe: vi.fn(),
        unsubscribe: vi.fn(),
        permission: 'default',
      });

      const { container } = render(<NotificationBell />);

      const errorToast = container.querySelector(
        'div.bg-red-500.text-white'
      );
      expect(errorToast).toBeInTheDocument();
      expect(errorToast).toHaveClass('fixed');
      expect(errorToast).toHaveClass('rounded-lg');
    });
  });

  describe('accessibility', () => {
    test('should have proper aria-label', () => {
      vi.spyOn(PushNotificationsHook, 'usePushNotifications').mockReturnValue({
        isSupported: true,
        isSubscribed: false,
        isLoading: false,
        error: null,
        subscribe: vi.fn(),
        unsubscribe: vi.fn(),
        permission: 'default',
      });

      render(<NotificationBell />);

      const button = screen.getByRole('button');
      expect(button).toHaveAttribute('aria-label', 'Activar notificaciones');
    });

    test('should update aria-label when subscription state changes', () => {
      const mockHook = vi.spyOn(
        PushNotificationsHook,
        'usePushNotifications'
      );

      mockHook.mockReturnValue({
        isSupported: true,
        isSubscribed: false,
        isLoading: false,
        error: null,
        subscribe: vi.fn(),
        unsubscribe: vi.fn(),
        permission: 'default',
      });

      const { rerender } = render(<NotificationBell />);

      let button = screen.getByRole('button');
      expect(button).toHaveAttribute('aria-label', 'Activar notificaciones');

      mockHook.mockReturnValue({
        isSupported: true,
        isSubscribed: true,
        isLoading: false,
        error: null,
        subscribe: vi.fn(),
        unsubscribe: vi.fn(),
        permission: 'granted',
      });

      rerender(<NotificationBell />);

      button = screen.getByRole('button');
      expect(button).toHaveAttribute('aria-label', 'Desactivar notificaciones');
    });
  });
});
