import { Alert, AlertDescription } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Skeleton } from '@/components/ui/skeleton';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import axios from '@/lib/axios';
import { TransactionsPagination } from '@/types/employee-dashboard';
import {
    AlertCircleIcon,
    ArrowDownIcon,
    ArrowUpIcon,
    ChevronLeftIcon,
    ChevronRightIcon,
    EditIcon,
    HistoryIcon,
} from 'lucide-react';
import { useEffect, useState } from 'react';

export function TransactionsTable() {
    const [data, setData] = useState<TransactionsPagination | null>(null);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState<string | null>(null);
    const [currentPage, setCurrentPage] = useState(1);

    const fetchTransactions = async (page: number) => {
        setLoading(true);
        setError(null);

        try {
            const response = await axios.get(
                `/api/my-transactions?page=${page}`,
            );
            setData(response.data);
            setCurrentPage(page);
        } catch (err: any) {
            setError(
                err.response?.data?.error ||
                    'Error al cargar las transacciones. Intente nuevamente.',
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
                    <Badge variant="default" className="gap-1">
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
                    <Badge
                        variant="secondary"
                        className="gap-1 bg-primary/80 text-primary-foreground"
                    >
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
                return 'text-primary';
            case 'debit':
                return 'text-destructive';
            case 'adjustment':
                return 'text-primary/80';
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
                        <div className="space-y-3 px-2 md:hidden">
                            {data.data.map((transaction) => (
                                <div
                                    key={transaction.id}
                                    className="space-y-2 rounded-lg border bg-card p-3"
                                >
                                    <div className="flex items-center justify-between">
                                        <span className="text-xs text-muted-foreground">
                                            {transaction.created_at}
                                        </span>
                                        {getTypeBadge(
                                            transaction.type,
                                            transaction.type_label,
                                        )}
                                    </div>
                                    <div className="flex items-center justify-between">
                                        <span
                                            className={`text-xl font-bold tabular-nums ${getAmountColor(
                                                transaction.type,
                                            )}`}
                                        >
                                            {transaction.type === 'debit'
                                                ? '-'
                                                : '+'}
                                            ${transaction.amount.toFixed(2)}
                                        </span>
                                        <span className="text-sm text-muted-foreground">
                                            Saldo: $
                                            {transaction.balance_after.toFixed(
                                                2,
                                            )}
                                        </span>
                                    </div>
                                    {transaction.branch_name !== 'N/A' && (
                                        <div className="text-xs text-muted-foreground">
                                            {transaction.branch_name}
                                        </div>
                                    )}
                                    {transaction.description !== '-' && (
                                        <div className="line-clamp-2 text-sm text-foreground/80">
                                            {transaction.description}
                                        </div>
                                    )}
                                </div>
                            ))}
                        </div>

                        {/* Desktop Table View */}
                        <div className="hidden overflow-x-auto rounded-md border md:block">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Fecha/Hora</TableHead>
                                        <TableHead>Tipo</TableHead>
                                        <TableHead className="text-right">
                                            Monto
                                        </TableHead>
                                        <TableHead className="text-right">
                                            Saldo Después
                                        </TableHead>
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
                                                    transaction.type_label,
                                                )}
                                            </TableCell>
                                            <TableCell
                                                className={`text-right font-bold tabular-nums ${getAmountColor(
                                                    transaction.type,
                                                )}`}
                                            >
                                                {transaction.type === 'debit'
                                                    ? '-'
                                                    : '+'}
                                                ${transaction.amount.toFixed(2)}
                                            </TableCell>
                                            <TableCell className="text-right font-semibold tabular-nums">
                                                $
                                                {transaction.balance_after.toFixed(
                                                    2,
                                                )}
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
                        <div className="flex flex-col items-center justify-between gap-3 px-2 md:flex-row md:px-0">
                            <p className="text-center text-xs text-muted-foreground md:text-left md:text-sm">
                                Mostrando {data.meta.from || 0} a{' '}
                                {data.meta.to || 0} de {data.meta.total}{' '}
                                transacciones
                            </p>
                            <div className="flex gap-2">
                                <Button
                                    variant="default"
                                    size="sm"
                                    onClick={handlePrevious}
                                    disabled={currentPage === 1 || loading}
                                >
                                    <ChevronLeftIcon className="mr-1 size-4" />
                                    Anterior
                                </Button>
                                <Button
                                    variant="default"
                                    size="sm"
                                    onClick={handleNext}
                                    disabled={
                                        currentPage === data.meta.last_page ||
                                        loading
                                    }
                                >
                                    Siguiente
                                    <ChevronRightIcon className="ml-1 size-4" />
                                </Button>
                            </div>
                        </div>
                    </>
                ) : (
                    <div className="px-4 py-12 text-center">
                        <HistoryIcon className="mx-auto mb-4 size-12 text-muted-foreground/50" />
                        <p className="text-sm text-muted-foreground md:text-base">
                            No tienes transacciones registradas aún
                        </p>
                    </div>
                )}
            </CardContent>
        </Card>
    );
}
