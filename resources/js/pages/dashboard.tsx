import AppLayout from '@/layouts/app-layout';
import { EmployeeCard } from '@/components/dashboard/employee-card';
import { TransactionsTable } from '@/components/dashboard/transactions-table';
import { dashboard } from '@/routes';
import { type BreadcrumbItem } from '@/types';
import { type EmployeeDashboardProps } from '@/types/employee-dashboard';
import { Head } from '@inertiajs/react';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { InfoIcon } from 'lucide-react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard().url,
    },
];

export default function Dashboard({ giftCard }: EmployeeDashboardProps) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Dashboard" />
            <div className="flex h-full flex-1 flex-col gap-4 md:gap-6 overflow-x-auto p-2 md:p-6">
                {giftCard ? (
                    <>
                        {/* Employee Card with QR and Balance */}
                        <EmployeeCard giftCard={giftCard} />

                        {/* Transactions Table */}
                        <TransactionsTable />
                    </>
                ) : (
                    <Alert className="mx-2 md:mx-0">
                        <InfoIcon />
                        <AlertTitle>Bienvenido</AlertTitle>
                        <AlertDescription>
                            No tienes una tarjeta QR asignada. Contacta al administrador para obtener tu tarjeta de empleado.
                        </AlertDescription>
                    </Alert>
                )}
            </div>
        </AppLayout>
    );
}
