import { useState, useEffect } from 'react';
import { EmployeeTransaction, TransactionsPagination } from '@/types/employee-dashboard';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Skeleton } from '@/components/ui/skeleton';
import { Alert, AlertDescription } from '@/components/ui/alert';
import {
    HistoryIcon,
    ChevronLeftIcon,
    ChevronRightIcon,
    AlertCircleIcon,
    ArrowUpIcon,
    ArrowDownIcon,
    EditIcon,
} from 'lucide-react';
import axios from '@/lib/axios';

export function TransactionsTable() {
    const [data, setData] = useState<TransactionsPagination | null>(null);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState<string | null>(null);
    const [currentPage, setCurrentPage] = useState(1);

    const fetchTransactions = async (page: number) => {
        setLoading(true);
        setError(null);

        try {
            const response = await axios.get(`/api/my-transactions?page=${page}`);
            setData(response.data);
            setCurrentPage(page);
        } catch (err: any) {
            setError(
                err.response?.data?.error ||
                    'Error al cargar las transacciones. Intente nuevamente.'
            );
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        fetchTransactions(1);
    }, []);

    const handlePrevious = () => {
        if (data && currentPage > 1) {
            fetchTransactions(currentPage - 1);
        }
    };

    const handleNext = () => {
        if (data && currentPage < data.meta.last_page) {
            fetchTransactions(currentPage + 1);
        }
    };

    const getTypeIcon = (type: string) => {
        switch (type) {
            case 'credit':
                return <ArrowUpIcon className="size-4" />;
            case 'debit':
                return <ArrowDownIcon className="size-4" />;
            case 'adjustment':
                return <EditIcon className="size-4" />;
            default:
                return null;
        }
    };

    const getTypeBadge = (type: string, label: string) => {
        switch (type) {
            case 'credit':
                return (
                    <Badge variant="default" className="gap-1 bg-green-600">
                        {getTypeIcon(type)}
                        {label}
                    </Badge>
                );
            case 'debit':
                return (
                    <Badge variant="destructive" className="gap-1">
                        {getTypeIcon(type)}
                        {label}
                    </Badge>
                );
            case 'adjustment':
                return (
                    <Badge variant="secondary" className="gap-1 bg-orange-600 text-white">
                        {getTypeIcon(type)}
                        {label}
                    </Badge>
                );
            default:
                return <Badge variant="outline">{label}</Badge>;
        }
    };

    const getAmountColor = (type: string) => {
        switch (type) {
            case 'credit':
                return 'text-green-600 dark:text-green-500';
            case 'debit':
                return 'text-red-600 dark:text-red-500';
            case 'adjustment':
                return 'text-orange-600 dark:text-orange-500';
            default:
                return '';
        }
    };

    return (
        <Card className="w-full">
            <CardHeader className="px-4 md:px-6">
                <CardTitle className="flex items-center gap-2 text-lg md:text-xl">
                    <HistoryIcon className="size-5" />
                    Mis Transacciones
                </CardTitle>
                <CardDescription className="text-sm">
                    Historial de movimientos en tu tarjeta QR
                </CardDescription>
            </CardHeader>
            <CardContent className="space-y-4 px-2 md:px-6">
                {error && (
                    <Alert variant="destructive" className="mx-2 md:mx-0">
                        <AlertCircleIcon />
                        <AlertDescription>{error}</AlertDescription>
                    </Alert>
                )}

                {loading ? (
                    <div className="space-y-3 px-2 md:px-0">
                        {[...Array(5)].map((_, i) => (
                            <Skeleton key={i} className="h-12 w-full" />
                        ))}
                    </div>
                ) : data && data.data.length > 0 ? (
                    <>
                        {/* Mobile Card View */}
                        <div className="md:hidden space-y-3 px-2">
                            {data.data.map((transaction) => (
                                <div
                                    key={transaction.id}
                                    className="p-3 rounded-lg border bg-card space-y-2"
                                >
                                    <div className="flex items-center justify-between">
                                        <span className="text-xs text-muted-foreground">
                                            {transaction.created_at}
                                        </span>
                                        {getTypeBadge(transaction.type, transaction.type_label)}
                                    </div>
                                    <div className="flex items-center justify-between">
                                        <span
                                            className={`text-xl font-bold tabular-nums ${getAmountColor(
                                                transaction.type
                                            )}`}
                                        >
                                            {transaction.type === 'debit' ? '-' : '+'}$
                                            {transaction.amount.toFixed(2)}
                                        </span>
                                        <span className="text-sm text-muted-foreground">
                                            Saldo: ${transaction.balance_after.toFixed(2)}
                                        </span>
                                    </div>
                                    {transaction.branch_name !== 'N/A' && (
                                        <div className="text-xs text-muted-foreground">
                                            {transaction.branch_name}
                                        </div>
                                    )}
                                    {transaction.description !== '-' && (
                                        <div className="text-sm text-foreground/80 line-clamp-2">
                                            {transaction.description}
                                        </div>
                                    )}
                                </div>
                            ))}
                        </div>

                        {/* Desktop Table View */}
                        <div className="hidden md:block rounded-md border overflow-x-auto">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Fecha/Hora</TableHead>
                                        <TableHead>Tipo</TableHead>
                                        <TableHead className="text-right">Monto</TableHead>
                                        <TableHead className="text-right">Saldo Después</TableHead>
                                        <TableHead>Sucursal</TableHead>
                                        <TableHead>Descripción</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {data.data.map((transaction) => (
                                        <TableRow key={transaction.id}>
                                            <TableCell className="font-medium whitespace-nowrap">
                                                {transaction.created_at}
                                            </TableCell>
                                            <TableCell>
                                                {getTypeBadge(
                                                    transaction.type,
                                                    transaction.type_label
                                                )}
                                            </TableCell>
                                            <TableCell
                                                className={`text-right font-bold tabular-nums ${getAmountColor(
                                                    transaction.type
                                                )}`}
                                            >
                                                {transaction.type === 'debit' ? '-' : '+'}$
                                                {transaction.amount.toFixed(2)}
                                            </TableCell>
                                            <TableCell className="text-right font-semibold tabular-nums">
                                                ${transaction.balance_after.toFixed(2)}
                                            </TableCell>
                                            <TableCell className="text-muted-foreground">
                                                {transaction.branch_name}
                                            </TableCell>
                                            <TableCell className="max-w-xs truncate">
                                                {transaction.description}
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        </div>

                        {/* Pagination */}
                        <div className="flex flex-col md:flex-row items-center justify-between gap-3 px-2 md:px-0">
                            <p className="text-xs md:text-sm text-muted-foreground text-center md:text-left">
                                Mostrando {data.meta.from || 0} a {data.meta.to || 0} de{' '}
                                {data.meta.total} transacciones
                            </p>
                            <div className="flex gap-2">
                                <Button
                                    variant="outline"
                                    size="sm"
                                    onClick={handlePrevious}
                                    disabled={currentPage === 1 || loading}
                                >
                                    <ChevronLeftIcon className="mr-1 size-4" />
                                    Anterior
                                </Button>
                                <Button
                                    variant="outline"
                                    size="sm"
                                    onClick={handleNext}
                                    disabled={currentPage === data.meta.last_page || loading}
                                >
                                    Siguiente
                                    <ChevronRightIcon className="ml-1 size-4" />
                                </Button>
                            </div>
                        </div>
                    </>
                ) : (
                    <div className="text-center py-12 px-4">
                        <HistoryIcon className="size-12 mx-auto mb-4 text-muted-foreground/50" />
                        <p className="text-muted-foreground text-sm md:text-base">
                            No tienes transacciones registradas aún
                        </p>
                    </div>
                )}
            </CardContent>
        </Card>
    );
}
