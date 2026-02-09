import { Download, X } from 'lucide-react';
import { useEffect, useState } from 'react';

import { Button } from '@/components/ui/button';
import { usePwaInstall } from '@/hooks/use-pwa-install';

interface PwaInstallPromptProps {
    className?: string;
}

export function PwaInstallPrompt({ className = '' }: PwaInstallPromptProps) {
    const { canInstall, install, isSupported, dismiss } = usePwaInstall();
    const [isMobile, setIsMobile] = useState(false);
    const [isVisible, setIsVisible] = useState(false);
    const [isAnimating, setIsAnimating] = useState(false);

    // Check if mobile
    useEffect(() => {
        const checkMobile = () => {
            const isMobileDevice =
                typeof window !== 'undefined' &&
                /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(
                    navigator.userAgent,
                );
            setIsMobile(isMobileDevice);
        };

        checkMobile();
        window.addEventListener('resize', checkMobile);
        return () => window.removeEventListener('resize', checkMobile);
    }, []);

    // Show prompt when conditions are met
    useEffect(() => {
        if (canInstall && isMobile && isSupported) {
            setIsVisible(true);
            // Trigger animation after render
            setTimeout(() => setIsAnimating(true), 50);
        }
    }, [canInstall, isMobile, isSupported]);

    const handleInstall = async () => {
        try {
            await install();
            // Hide after successful install
            setIsAnimating(false);
            setTimeout(() => setIsVisible(false), 300);
        } catch (err) {
            // Error handled silently
        }
    };

    const handleDismiss = () => {
        dismiss();
        setIsAnimating(false);
        setTimeout(() => setIsVisible(false), 300);
    };

    if (!isVisible || !isMobile) {
        return null;
    }

    return (
        <div
            className={`fixed right-0 bottom-0 left-0 z-40 ${className}`}
            style={{
                transform: isAnimating ? 'translateY(0)' : 'translateY(100%)',
                transition: 'transform 300ms ease-out',
            }}
        >
            <div className="border-t border-gray-200 bg-white shadow-lg dark:border-gray-800 dark:bg-gray-900">
                <div className="max-w-full px-4 py-4 sm:px-6">
                    <div className="flex items-start justify-between gap-4">
                        {/* Content */}
                        <div className="flex flex-1 items-start gap-3">
                            <Download className="mt-1 h-5 w-5 flex-shrink-0 text-amber-600 dark:text-amber-500" />

                            <div className="flex-1">
                                <h3 className="text-sm font-semibold text-gray-900 dark:text-white">
                                    Instala QR Made en tu teléfono
                                </h3>
                                <p className="mt-1 text-xs text-gray-600 dark:text-gray-400">
                                    Accede a tu saldo y recibe notificaciones en
                                    tiempo real. ¡Más rápido y sin abrir el
                                    navegador!
                                </p>
                            </div>
                        </div>

                        {/* Close button */}
                        <button
                            onClick={handleDismiss}
                            className="flex-shrink-0 text-gray-400 transition-colors hover:text-gray-500 dark:hover:text-gray-300"
                            aria-label="Cerrar"
                        >
                            <X className="h-5 w-5" />
                        </button>
                    </div>

                    {/* Action buttons */}
                    <div className="mt-4 flex gap-3">
                        <Button
                            onClick={handleInstall}
                            size="sm"
                            className="flex-1 bg-amber-600 text-white hover:bg-amber-700"
                        >
                            Instalar
                        </Button>
                        <Button
                            onClick={handleDismiss}
                            variant="outline"
                            size="sm"
                            className="flex-1"
                        >
                            Ahora no
                        </Button>
                    </div>
                </div>
            </div>
        </div>
    );
}
