import { EmployeeGiftCard } from '@/types/employee-dashboard';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import {
    CreditCardIcon,
    CalendarIcon,
    CheckCircle2Icon,
    XCircleIcon,
    QrCodeIcon,
} from 'lucide-react';

interface EmployeeCardProps {
    giftCard: EmployeeGiftCard;
}

export function EmployeeCard({ giftCard }: EmployeeCardProps) {
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
        new Date(giftCard.expiry_date.split('/').reverse().join('-')) < new Date();

    return (
        <Card className="w-full">
            <CardHeader className="px-4 md:px-6">
                <CardTitle className="flex items-center gap-2 text-lg md:text-xl">
                    <CreditCardIcon className="size-5" />
                    Mi Tarjeta QR Empleado
                </CardTitle>
            </CardHeader>
            <CardContent className="space-y-4 md:space-y-6 px-4 md:px-6">
                {/* Grid Layout: QR Code + Info */}
                <div className="flex flex-col gap-4 md:gap-6 md:grid md:grid-cols-[300px_1fr]">
                    {/* QR Code Section */}
                    <div className="flex flex-col items-center gap-4 mx-auto w-full max-w-[280px] md:max-w-[300px]">
                        {giftCard.qr_image_path ? (
                            <div className="relative w-full">
                                <img
                                    src={giftCard.qr_image_path}
                                    alt="Código QR"
                                    className="w-full rounded-lg border-4 border-border shadow-lg bg-white p-3 md:p-4"
                                />
                                {!isActive && (
                                    <div className="absolute inset-0 flex items-center justify-center bg-black/60 rounded-lg">
                                        <Badge variant="destructive" className="text-base md:text-lg px-3 md:px-4 py-1.5 md:py-2">
                                            Inactiva
                                        </Badge>
                                    </div>
                                )}
                            </div>
                        ) : (
                            <div className="w-full aspect-square rounded-lg border-4 border-dashed border-muted-foreground/25 flex flex-col items-center justify-center gap-4 bg-muted/20">
                                <QrCodeIcon className="size-16 md:size-24 text-muted-foreground/50" />
                                <p className="text-sm text-muted-foreground">QR no disponible</p>
                            </div>
                        )}
                    </div>

                    {/* Info Section */}
                    <div className="space-y-4 md:space-y-6">
                        {/* Employee Info */}
                        <div className="flex flex-col items-center md:items-start md:flex-row gap-4 p-4 md:p-6 rounded-lg bg-muted/50">
                            <Avatar className="size-16 md:size-20">
                                <AvatarImage
                                    src={giftCard.user.avatar || undefined}
                                    alt={giftCard.user.name}
                                />
                                <AvatarFallback className="text-lg md:text-xl">
                                    {getInitials(giftCard.user.name)}
                                </AvatarFallback>
                            </Avatar>

                            <div className="flex-1 space-y-2 md:space-y-3 text-center md:text-left">
                                <div className="flex items-center justify-center md:justify-start gap-2 md:gap-3 flex-wrap">
                                    <span className="text-2xl md:text-3xl font-bold tracking-tight">
                                        {giftCard.legacy_id}
                                    </span>
                                    <Badge
                                        variant={isActive ? 'default' : 'destructive'}
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
                                <p className="text-lg md:text-xl font-semibold text-foreground">
                                    {giftCard.user.name}
                                </p>
                                <p className="text-sm text-muted-foreground break-all">
                                    {giftCard.user.email}
                                </p>
                            </div>
                        </div>

                        {/* Balance Section */}
                        <div className="p-6 md:p-8 rounded-lg bg-gradient-to-br from-green-50 to-emerald-50 dark:from-green-950/20 dark:to-emerald-950/20 border-2 border-green-200 dark:border-green-900">
                            <div className="space-y-2 md:space-y-3">
                                <p className="text-sm font-medium text-muted-foreground text-center">
                                    Saldo Disponible
                                </p>
                                <p className="text-5xl md:text-6xl lg:text-7xl font-bold text-green-600 dark:text-green-500 tabular-nums text-center break-all">
                                    ${giftCard.balance.toFixed(2)}
                                </p>
                            </div>
                        </div>

                        {/* Expiry Date */}
                        {giftCard.expiry_date && (
                            <div className="flex items-center justify-center gap-2 p-3 rounded-lg bg-muted/50 flex-wrap">
                                <CalendarIcon className="size-4 text-muted-foreground" />
                                <span className="text-sm text-muted-foreground">
                                    Expira: {giftCard.expiry_date}
                                </span>
                                {isExpired && (
                                    <Badge variant="destructive" className="ml-2">
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
