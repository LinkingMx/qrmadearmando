import { Bell, Loader2 } from 'lucide-react';
import { useEffect, useState } from 'react';

import { Button } from '@/components/ui/button';
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import { usePushNotifications } from '@/hooks/use-push-notifications';

interface NotificationBellProps {
    className?: string;
}

export function NotificationBell({ className = '' }: NotificationBellProps) {
    const {
        isSupported,
        isSubscribed,
        isLoading,
        error,
        subscribe,
        unsubscribe,
    } = usePushNotifications();
    const [showError, setShowError] = useState(false);

    // Show error toast for 5 seconds
    useEffect(() => {
        if (error) {
            setShowError(true);
            const timer = setTimeout(() => setShowError(false), 5000);
            return () => clearTimeout(timer);
        }
    }, [error]);

    if (!isSupported) {
        return null;
    }

    const handleToggle = async () => {
        try {
            if (isSubscribed) {
                await unsubscribe();
            } else {
                await subscribe();
            }
        } catch (err) {
            // Error handled silently
        }
    };

    // Determine badge color based on state
    let badgeColor = 'bg-gray-400'; // loading state
    if (!isLoading) {
        badgeColor = isSubscribed ? 'bg-green-500' : 'bg-red-500';
    }

    const tooltipText = isSubscribed
        ? 'Desactivar notificaciones'
        : 'Activar notificaciones';

    return (
        <div className={`relative ${className}`}>
            <TooltipProvider>
                <Tooltip>
                    <TooltipTrigger asChild>
                        <Button
                            variant="ghost"
                            size="icon"
                            onClick={handleToggle}
                            disabled={isLoading}
                            aria-label={tooltipText}
                            className="relative"
                        >
                            <Bell className="h-5 w-5" />

                            {/* Badge indicator */}
                            <span
                                className={`absolute top-1 right-1 h-2 w-2 rounded-full ${badgeColor} transition-colors duration-200`}
                            />

                            {/* Loading spinner overlay */}
                            {isLoading && (
                                <Loader2 className="absolute h-5 w-5 animate-spin opacity-50" />
                            )}
                        </Button>
                    </TooltipTrigger>
                    <TooltipContent className="bg-gray-900 text-xs text-white">
                        {tooltipText}
                    </TooltipContent>
                </Tooltip>
            </TooltipProvider>

            {/* Error toast */}
            {showError && error && (
                <div className="fixed right-4 bottom-4 z-50 max-w-xs rounded-lg bg-red-500 px-4 py-2 text-sm text-white shadow-lg">
                    {error.message}
                </div>
            )}
        </div>
    );
}
