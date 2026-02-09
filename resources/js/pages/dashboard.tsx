'use client';

import { EmployeeCard } from '@/components/dashboard/employee-card';
import { TransactionsTable } from '@/components/dashboard/transactions-table';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Skeleton } from '@/components/ui/skeleton';
import AppLayout from '@/layouts/app-layout';
import { useOfflineGiftCard } from '@/hooks/use-offline-gift-card';
import { dashboard } from '@/routes';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';
import { AlertCircle, InfoIcon, Wifi, WifiOff } from 'lucide-react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard().url,
    },
];

export default function Dashboard() {
    const { data, isLoading, error, isOffline } = useOfflineGiftCard();

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Dashboard" />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto p-2 md:gap-6 md:p-6">
                {/* Offline indicator */}
                {isOffline && (
                    <Alert className="border-yellow-200 bg-yellow-50 dark:border-yellow-900 dark:bg-yellow-950">
                        <WifiOff className="h-4 w-4 text-yellow-600 dark:text-yellow-400" />
                        <AlertTitle className="text-yellow-800 dark:text-yellow-200">
                            Sin conexión
                        </AlertTitle>
                        <AlertDescription className="text-yellow-700 dark:text-yellow-300">
                            Viendo datos del último sincronización. Algunos datos pueden no estar actualizados.
                        </AlertDescription>
                    </Alert>
                )}

                {/* Loading state */}
                {isLoading ? (
                    <div className="space-y-4">
                        <Skeleton className="h-96 w-full rounded-lg" />
                        <Skeleton className="h-64 w-full rounded-lg" />
                    </div>
                ) : data ? (
                    <>
                        {/* Employee Card with QR and Balance */}
                        <EmployeeCard
                            giftCard={{
                                id: data.gift_card.id,
                                legacy_id: data.gift_card.legacy_id,
                                balance: data.gift_card.balance,
                                status: data.gift_card.status,
                                expiry_date: data.gift_card.expiry_date,
                                qr_image_path: data.gift_card.qr_image_path,
                                user: {
                                    name: data.user.name,
                                    email: data.user.email,
                                    avatar: data.user.avatar,
                                },
                            }}
                        />

                        {/* Transactions Table */}
                        <TransactionsTable />
                    </>
                ) : (
                    <Alert className="border-red-200 bg-red-50 dark:border-red-900 dark:bg-red-950">
                        <AlertCircle className="h-4 w-4 text-red-600 dark:text-red-400" />
                        <AlertTitle className="text-red-800 dark:text-red-200">
                            Bienvenido
                        </AlertTitle>
                        <AlertDescription className="text-red-700 dark:text-red-300">
                            {error || 'No tienes una tarjeta QR asignada. Contacta al administrador.'}
                        </AlertDescription>
                    </Alert>
                )}
            </div>
        </AppLayout>
    );
}
