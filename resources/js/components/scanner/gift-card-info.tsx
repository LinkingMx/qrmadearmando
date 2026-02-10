import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { GiftCard } from '@/types/scanner';
import {
    CalendarIcon,
    CheckCircle2Icon,
    CreditCardIcon,
    UserIcon,
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
        giftCard.expiry_date && new Date(giftCard.expiry_date) < new Date();

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
                <div className="flex items-start gap-4 rounded-lg bg-muted/50 p-4">
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
                            <div className="mb-1 flex items-center gap-2">
                                <span className="text-xl font-bold">
                                    {giftCard.legacy_id}
                                </span>
                                <Badge
                                    variant={
                                        isActive ? 'default' : 'destructive'
                                    }
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
                <div className="rounded-lg border border-green-200 bg-gradient-to-br from-green-50 to-emerald-50 p-6 dark:border-green-900 dark:from-green-950/20 dark:to-emerald-950/20">
                    <div className="space-y-2 text-center">
                        <p className="text-sm font-medium text-muted-foreground">
                            Saldo Disponible
                        </p>
                        <p className="text-5xl font-bold text-green-600 tabular-nums dark:text-green-500">
                            ${Number(giftCard.balance).toFixed(2)}
                        </p>
                    </div>
                </div>

                {/* QR Image Section */}
                {giftCard.qr_image_path && (
                    <div className="flex justify-center rounded-lg bg-muted/50 p-4">
                        <img
                            src={giftCard.qr_image_path}
                            alt="QR Code"
                            className="size-48 rounded-lg border-2 border-border"
                        />
                    </div>
                )}

                {/* UUID Info */}
                <div className="rounded bg-muted/30 p-2 text-center font-mono text-xs text-muted-foreground">
                    UUID: {giftCard.id}
                </div>
            </CardContent>
        </Card>
    );
}
