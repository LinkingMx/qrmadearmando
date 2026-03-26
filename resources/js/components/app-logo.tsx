import { useSidebar } from '@/components/ui/sidebar';
import { useAppearance } from '@/hooks/use-appearance';

export default function AppLogo() {
    const { state } = useSidebar();
    const { appearance, resolvedAppearance } = useAppearance();
    const isCollapsed = state === 'collapsed';
    const isDark = resolvedAppearance === 'dark';

    return (
        <>
            {isCollapsed ? (
                // Collapsed: Show favicon icon
                <div className="flex aspect-square size-8 items-center justify-center">
                    <img src="/favicon.svg?v=2" alt="Logo" className="size-8" />
                </div>
            ) : (
                // Expanded: Show full logo (light logo for dark mode, dark logo for light mode)
                <div className="flex w-full items-center gap-2">
                    <img
                        src={isDark ? '/logo_light.webp' : '/logo_dark.webp'}
                        alt="QR Costeño"
                        className="h-6 w-auto"
                    />
                </div>
            )}
        </>
    );
}
