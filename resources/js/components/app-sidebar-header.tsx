import { Breadcrumbs } from '@/components/breadcrumbs';
import { NotificationBell } from '@/components/notification-bell';
import { Button } from '@/components/ui/button';
import { SidebarTrigger } from '@/components/ui/sidebar';
import { type BreadcrumbItem as BreadcrumbItemType } from '@/types';
import { router } from '@inertiajs/react';
import { RefreshCwIcon } from 'lucide-react';
import { useState } from 'react';

export function AppSidebarHeader({
    breadcrumbs = [],
}: {
    breadcrumbs?: BreadcrumbItemType[];
}) {
    const [isRefreshing, setIsRefreshing] = useState(false);

    const handleRefresh = () => {
        setIsRefreshing(true);
        router.reload({
            onFinish: () => {
                setIsRefreshing(false);
            },
        });
    };

    return (
        <header className="sticky top-safe z-10 flex h-16 shrink-0 items-center justify-between gap-2 border-b border-sidebar-border/50 bg-background px-6 backdrop-blur-sm transition-[width,height] ease-linear group-has-data-[collapsible=icon]/sidebar-wrapper:h-12 md:px-4">
            <div className="flex items-center gap-2">
                <SidebarTrigger className="-ml-1" />
                <Breadcrumbs breadcrumbs={breadcrumbs} />
            </div>
            <div className="flex items-center gap-2">
                <Button
                    variant="ghost"
                    size="icon"
                    onClick={handleRefresh}
                    disabled={isRefreshing}
                    className="size-8"
                    title="Actualizar datos"
                >
                    <RefreshCwIcon
                        className={`size-4 ${isRefreshing ? 'animate-spin' : ''}`}
                    />
                </Button>
                <NotificationBell />
            </div>
        </header>
    );
}
