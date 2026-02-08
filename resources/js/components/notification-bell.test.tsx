import { render, screen, fireEvent, waitFor } from '@testing-library/react';
import { vi, describe, test, expect, beforeEach, afterEach } from 'vitest';
import { NotificationBell } from './notification-bell';
import * as PushNotificationsHook from '@/hooks/use-push-notifications';

// Mock the hook
vi.mock('@/hooks/use-push-notifications', () => ({
  usePushNotifications: vi.fn(),
}));

// Mock Radix UI components
vi.mock('@/components/ui/button', () => ({
  Button: ({ onClick, disabled, children, ...props }: any) => (
    <button onClick={onClick} disabled={disabled} {...props}>
      {children}
    </button>
  ),
}));

vi.mock('@/components/ui/tooltip', () => ({
  Tooltip: ({ children }: any) => <div>{children}</div>,
  TooltipTrigger: ({ children }: any) => <div>{children}</div>,
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

    test('should apply custom className', () => {
      vi.spyOn(PushNotificationsHook, 'usePushNotifications').mockReturnValue({
        isSupported: true,
        isSubscribed: false,
        isLoading: false,
        error: null,
        subscribe: vi.fn(),
        unsubscribe: vi.fn(),
        permission: 'default',
      });

      render(<NotificationBell className="custom-class" />);

      const container = screen.getByTestId('bell-icon').closest('div')
        ?.parentElement;
      expect(container).toHaveClass('custom-class');
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
    test('should call subscribe when not subscribed', async () => {
      const mockSubscribe = vi.fn();

      vi.spyOn(PushNotificationsHook, 'usePushNotifications').mockReturnValue({
        isSupported: true,
        isSubscribed: false,
        isLoading: false,
        error: null,
        subscribe: mockSubscribe,
        unsubscribe: vi.fn(),
        permission: 'default',
      });

      render(<NotificationBell />);

      const button = screen.getByRole('button');
      fireEvent.click(button);

      await waitFor(() => {
        expect(mockSubscribe).toHaveBeenCalled();
      });
    });

    test('should call unsubscribe when subscribed', async () => {
      const mockUnsubscribe = vi.fn();

      vi.spyOn(PushNotificationsHook, 'usePushNotifications').mockReturnValue({
        isSupported: true,
        isSubscribed: true,
        isLoading: false,
        error: null,
        subscribe: vi.fn(),
        unsubscribe: mockUnsubscribe,
        permission: 'granted',
      });

      render(<NotificationBell />);

      const button = screen.getByRole('button');
      fireEvent.click(button);

      await waitFor(() => {
        expect(mockUnsubscribe).toHaveBeenCalled();
      });
    });
  });

  describe('error handling', () => {
    test('should display error toast when error occurs', async () => {
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

      await waitFor(() => {
        expect(screen.getByText('Test error message')).toBeInTheDocument();
      });
    });

    test('should hide error toast after 5 seconds', async () => {
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

      await waitFor(() => {
        expect(screen.getByText('Test error message')).toBeInTheDocument();
      });

      // Fast-forward 5 seconds
      vi.advanceTimersByTime(5000);

      await waitFor(() => {
        expect(
          screen.queryByText('Test error message')
        ).not.toBeInTheDocument();
      });
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
      const { rerender } = render(<NotificationBell />);

      vi.spyOn(PushNotificationsHook, 'usePushNotifications').mockReturnValue({
        isSupported: true,
        isSubscribed: true,
        isLoading: false,
        error: null,
        subscribe: vi.fn(),
        unsubscribe: vi.fn(),
        permission: 'granted',
      });

      rerender(<NotificationBell />);

      const button = screen.getByRole('button');
      expect(button).toHaveAttribute('aria-label', 'Desactivar notificaciones');
    });
  });
});
