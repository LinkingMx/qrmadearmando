import { GiftCard } from '@/types/scanner';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import {
    CreditCardIcon,
    UserIcon,
    CalendarIcon,
    CheckCircle2Icon,
    XCircleIcon,
} from 'lucide-react';

interface GiftCardInfoProps {
    giftCard: GiftCard;
}

export function GiftCardInfo({ giftCard }: GiftCardInfoProps) {
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
        new Date(giftCard.expiry_date) < new Date();

    return (
        <Card className="w-full">
            <CardHeader>
                <CardTitle className="flex items-center gap-2">
                    <CreditCardIcon className="size-5" />
                    Información del QR Empleado
                </CardTitle>
            </CardHeader>
            <CardContent className="space-y-6">
                {/* Employee Info Section */}
                <div className="flex items-start gap-4 p-4 rounded-lg bg-muted/50">
                    <Avatar className="size-16">
                        <AvatarImage
                            src={giftCard.user?.avatar || undefined}
                            alt={giftCard.user?.name || 'Sin asignar'}
                        />
                        <AvatarFallback className="text-lg">
                            {giftCard.user ? (
                                getInitials(giftCard.user.name)
                            ) : (
                                <UserIcon className="size-6" />
                            )}
                        </AvatarFallback>
                    </Avatar>

                    <div className="flex-1 space-y-3">
                        <div>
                            <div className="flex items-center gap-2 mb-1">
                                <span className="text-xl font-bold">
                                    {giftCard.legacy_id}
                                </span>
                                <Badge
                                    variant={isActive ? 'default' : 'destructive'}
                                    className="gap-1"
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
                            <p className="text-lg font-medium text-foreground">
                                {giftCard.user?.name || 'Sin asignar'}
                            </p>
                        </div>

                        {giftCard.expiry_date && (
                            <div className="flex items-center gap-2 text-sm text-muted-foreground">
                                <CalendarIcon className="size-4" />
                                <span>
                                    Expira: {giftCard.expiry_date}
                                    {isExpired && (
                                        <Badge
                                            variant="destructive"
                                            className="ml-2"
                                        >
                                            Expirada
                                        </Badge>
                                    )}
                                </span>
                            </div>
                        )}
                    </div>
                </div>

                {/* Balance Section */}
                <div className="p-6 rounded-lg bg-gradient-to-br from-green-50 to-emerald-50 dark:from-green-950/20 dark:to-emerald-950/20 border border-green-200 dark:border-green-900">
                    <div className="text-center space-y-2">
                        <p className="text-sm font-medium text-muted-foreground">
                            Saldo Disponible
                        </p>
                        <p className="text-5xl font-bold text-green-600 dark:text-green-500 tabular-nums">
                            ${giftCard.balance.toFixed(2)}
                        </p>
                    </div>
                </div>

                {/* QR Image Section */}
                {giftCard.qr_image_path && (
                    <div className="flex justify-center p-4 rounded-lg bg-muted/50">
                        <img
                            src={giftCard.qr_image_path}
                            alt="QR Code"
                            className="size-48 rounded-lg border-2 border-border"
                        />
                    </div>
                )}

                {/* UUID Info */}
                <div className="text-xs text-muted-foreground text-center p-2 bg-muted/30 rounded font-mono">
                    UUID: {giftCard.id}
                </div>
            </CardContent>
        </Card>
    );
}
