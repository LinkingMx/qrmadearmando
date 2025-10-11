import { useSidebar } from '@/components/ui/sidebar';

export default function AppLogo() {
    const { state } = useSidebar();
    const isCollapsed = state === 'collapsed';

    return (
        <>
            {isCollapsed ? (
                // Collapsed: Show favicon icon
                <div className="flex aspect-square size-8 items-center justify-center">
                    <img
                        src="/favicon.svg?v=2"
                        alt="Logo"
                        className="size-8"
                    />
                </div>
            ) : (
                // Expanded: Show full logo
                <div className="flex items-center gap-2 w-full">
                    <img
                        src="/logo.svg?v=2"
                        alt="QR Codemesón"
                        className="h-6 w-auto"
                    />
                </div>
            )}
        </>
    );
}
