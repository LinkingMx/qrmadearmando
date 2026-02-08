import { useEffect, useState } from 'react';

interface UsePwaInstallReturn {
    canInstall: boolean;
    install: () => Promise<void>;
    isInstalled: boolean;
    isSupported: boolean;
    dismiss: () => void;
}

interface BeforeInstallPromptEvent extends Event {
    prompt: () => Promise<void>;
    userChoice: Promise<{ outcome: 'accepted' | 'dismissed' }>;
}

const DISMISSAL_KEY = 'pwa:install-dismissed-timestamp';
const DISMISSAL_DURATION = 14 * 24 * 60 * 60 * 1000; // 14 days in milliseconds

export function usePwaInstall(): UsePwaInstallReturn {
    const [canInstall, setCanInstall] = useState(false);
    const [isInstalled, setIsInstalled] = useState(false);
    const [deferredPrompt, setDeferredPrompt] =
        useState<BeforeInstallPromptEvent | null>(null);

    const isSupported =
        typeof window !== 'undefined' && 'beforeinstallprompt' in window;

    // Check if dismissed within the last 14 days
    const isDismissed = (): boolean => {
        const dismissedTimestamp = localStorage.getItem(DISMISSAL_KEY);
        if (!dismissedTimestamp) return false;

        const now = Date.now();
        const dismissedTime = parseInt(dismissedTimestamp, 10);

        return now - dismissedTime < DISMISSAL_DURATION;
    };

    // Check if already installed
    const checkInstalled = (): boolean => {
        if (typeof window === 'undefined') return false;

        return (
            window.matchMedia('(display-mode: standalone)').matches ||
            (window.navigator as any).standalone === true
        );
    };

    // Initialize on mount
    useEffect(() => {
        if (!isSupported) return;

        setIsInstalled(checkInstalled());

        // Listen for beforeinstallprompt event
        const handleBeforeInstallPrompt = (event: Event) => {
            event.preventDefault();
            setDeferredPrompt(event as BeforeInstallPromptEvent);

            // Only show if not already dismissed
            if (!isDismissed() && !checkInstalled()) {
                setCanInstall(true);
            }
        };

        // Listen for app installed
        const handleAppInstalled = () => {
            setIsInstalled(true);
            setCanInstall(false);
        };

        window.addEventListener(
            'beforeinstallprompt',
            handleBeforeInstallPrompt,
        );
        window.addEventListener('appinstalled', handleAppInstalled);

        return () => {
            window.removeEventListener(
                'beforeinstallprompt',
                handleBeforeInstallPrompt,
            );
            window.removeEventListener('appinstalled', handleAppInstalled);
        };
    }, [isSupported]);

    const install = async () => {
        if (!deferredPrompt) {
            throw new Error('Install prompt not available');
        }

        try {
            // Show the install prompt
            await deferredPrompt.prompt();

            // Wait for user choice
            const { outcome } = await deferredPrompt.userChoice;

            if (outcome === 'accepted') {
                setIsInstalled(true);
                setCanInstall(false);
            } else {
                // User dismissed, set dismissal timestamp
                localStorage.setItem(DISMISSAL_KEY, Date.now().toString());
                setCanInstall(false);
            }

            // Clear the deferred prompt
            setDeferredPrompt(null);
        } catch (err) {
            console.error('Failed to show install prompt:', err);
            throw err;
        }
    };

    const dismiss = () => {
        localStorage.setItem(DISMISSAL_KEY, Date.now().toString());
        setCanInstall(false);
    };

    return {
        canInstall: canInstall && !isDismissed() && !isInstalled,
        install,
        isInstalled,
        isSupported,
        dismiss,
    };
}
