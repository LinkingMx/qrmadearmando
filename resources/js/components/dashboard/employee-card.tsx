import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent } from '@/components/ui/card';
import { useAppearance } from '@/hooks/use-appearance';
import { EmployeeGiftCard } from '@/types/employee-dashboard';
import {
    CalendarIcon,
    CheckCircle2Icon,
    QrCodeIcon,
    XCircleIcon,
} from 'lucide-react';

interface EmployeeCardProps {
    giftCard: EmployeeGiftCard;
}

export function EmployeeCard({ giftCard }: EmployeeCardProps) {
    const { resolvedAppearance } = useAppearance();
    const isDark = resolvedAppearance === 'dark';

    const getInitials = (name: string) => {
        return name
            .split(' ')
            .map((n) => n[0])
            .join('')
            .toUpperCase()
            .slice(0, 2);
    };

    const isActive = giftCard.status;
    const isExpired =
        giftCard.expiry_date &&
        new Date(giftCard.expiry_date.split('/').reverse().join('-')) <
            new Date();

    return (
        <Card className="w-full">
            <CardContent className="space-y-4 px-4 pt-6 md:space-y-6 md:px-6">
                {/* Grid Layout: QR Code + Info */}
                <div className="flex flex-col gap-4 md:grid md:grid-cols-[300px_1fr] md:gap-6">
                    {/* QR Code Section */}
                    <div className="mx-auto flex w-full max-w-[280px] flex-col items-center gap-8 md:max-w-[300px]">
                        {giftCard.qr_image_path ? (
                            <>
                                {/* Logo arriba del QR */}
                                <div className="flex w-full justify-center">
                                    <img
                                        src={
                                            isDark
                                                ? '/logo_light.webp'
                                                : '/logo_dark.webp'
                                        }
                                        alt="Logo"
                                        className="h-auto w-full"
                                    />
                                </div>

                                <div className="relative w-full">
                                    <img
                                        src={giftCard.qr_image_path}
                                        alt="Código QR"
                                        className="w-full rounded-lg border-4 border-border bg-white p-3 shadow-lg md:p-4"
                                    />
                                    {!isActive && (
                                        <div className="absolute inset-0 flex items-center justify-center rounded-lg bg-black/60">
                                            <Badge
                                                variant="default"
                                                className="px-3 py-1.5 text-base md:px-4 md:py-2 md:text-lg"
                                            >
                                                Inactiva
                                            </Badge>
                                        </div>
                                    )}
                                </div>
                            </>
                        ) : (
                            <div className="flex aspect-square w-full flex-col items-center justify-center gap-4 rounded-lg border-4 border-dashed border-muted-foreground/25 bg-muted/20">
                                <QrCodeIcon className="size-16 text-muted-foreground/50 md:size-24" />
                                <p className="text-sm text-muted-foreground">
                                    QR no disponible
                                </p>
                            </div>
                        )}
                    </div>

                    {/* Info Section */}
                    <div className="space-y-4 md:space-y-6">
                        {/* Employee Info */}
                        <div className="flex flex-col items-center gap-4 rounded-lg bg-muted/50 p-4 md:flex-row md:items-start md:p-6">
                            <Avatar className="size-16 md:size-20">
                                <AvatarImage
                                    src={giftCard.user.avatar || undefined}
                                    alt={giftCard.user.name}
                                />
                                <AvatarFallback className="text-lg md:text-xl">
                                    {getInitials(giftCard.user.name)}
                                </AvatarFallback>
                            </Avatar>

                            <div className="flex-1 space-y-2 text-center md:space-y-3 md:text-left">
                                <div className="flex flex-wrap items-center justify-center gap-2 md:justify-start md:gap-3">
                                    <span className="text-2xl font-bold tracking-tight md:text-3xl">
                                        {giftCard.legacy_id}
                                    </span>
                                    <Badge
                                        variant={
                                            isActive ? 'default' : 'destructive'
                                        }
                                        className="gap-1 text-xs md:text-sm"
                                    >
                                        {isActive ? (
                                            <>
                                                <CheckCircle2Icon className="size-3" />
                                                Activa
                                            </>
                                        ) : (
                                            <>
                                                <XCircleIcon className="size-3" />
                                                Inactiva
                                            </>
                                        )}
                                    </Badge>
                                </div>
                                <p className="text-lg font-semibold text-foreground md:text-xl">
                                    {giftCard.user.name}
                                </p>
                                <Badge
                                    variant="default"
                                    className="text-xs break-all md:text-sm"
                                >
                                    {giftCard.user.email}
                                </Badge>
                            </div>
                        </div>

                        {/* Balance Section */}
                        <div className="rounded-lg border-2 border-primary/20 bg-gradient-to-br from-primary/5 to-primary/10 p-6 md:p-8 dark:border-primary/30 dark:from-primary/10 dark:to-primary/20">
                            <div className="space-y-2 md:space-y-3">
                                <p className="text-center text-sm font-medium text-muted-foreground">
                                    Saldo Disponible
                                </p>
                                <p className="text-center text-5xl font-bold break-all text-primary tabular-nums md:text-6xl lg:text-7xl">
                                    ${giftCard.balance.toFixed(2)}
                                </p>
                            </div>
                        </div>

                        {/* Expiry Date */}
                        {giftCard.expiry_date && (
                            <div className="flex flex-wrap items-center justify-center gap-2 rounded-lg bg-muted/50 p-3">
                                <CalendarIcon className="size-4 text-muted-foreground" />
                                <span className="text-sm text-muted-foreground">
                                    Expira: {giftCard.expiry_date}
                                </span>
                                {isExpired && (
                                    <Badge
                                        variant="destructive"
                                        className="ml-2"
                                    >
                                        Expirada
                                    </Badge>
                                )}
                            </div>
                        )}
                    </div>
                </div>
            </CardContent>
        </Card>
    );
}
