import { useEffect, useState } from 'react';
import {
    BranchTransactionsResponse,
    PaginationMeta,
    Transaction,
} from '@/types/scanner';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { Button } from '@/components/ui/button';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { ReprintReceiptModal } from './reprint-receipt-modal';
import {
    PrinterIcon,
    ReceiptIcon,
    AlertCircleIcon,
    ChevronLeftIcon,
    ChevronRightIcon,
} from 'lucide-react';
import axios from '@/lib/axios';

interface BranchTransactionListProps {
    branchId: number;
    branchName: string;
}

export function BranchTransactionList({
    branchId,
    branchName,
}: BranchTransactionListProps) {
    const [transactions, setTransactions] = useState<Transaction[]>([]);
    const [meta, setMeta] = useState<PaginationMeta | null>(null);
    const [isLoading, setIsLoading] = useState(true);
    const [error, setError] = useState<string | null>(null);
    const [selectedTransaction, setSelectedTransaction] =
        useState<Transaction | null>(null);
    const [showReprintModal, setShowReprintModal] = useState(false);

    const fetchTransactions = async (page: number = 1) => {
        setIsLoading(true);
        setError(null);

        try {
            const response = await axios.get<BranchTransactionsResponse>(
                `/api/scanner/branch-transactions?page=${page}`
            );
            setTransactions(response.data.data);
            setMeta(response.data.meta);
        } catch (err: any) {
            const errorMsg =
                err.response?.data?.message ||
                'Error al cargar las transacciones. Intente nuevamente.';
            setError(errorMsg);
        } finally {
            setIsLoading(false);
        }
    };

    useEffect(() => {
        fetchTransactions();
    }, []);

    const handleReprint = (transaction: Transaction) => {
        setSelectedTransaction(transaction);
        setShowReprintModal(true);
    };

    const handleCloseReprint = () => {
        setShowReprintModal(false);
        setSelectedTransaction(null);
    };

    const handlePreviousPage = () => {
        if (meta && meta.current_page > 1) {
            fetchTransactions(meta.current_page - 1);
        }
    };

    const handleNextPage = () => {
        if (meta && meta.current_page < meta.last_page) {
            fetchTransactions(meta.current_page + 1);
        }
    };

    // Loading skeleton
    if (isLoading && transactions.length === 0) {
        return (
            <Card>
                <CardHeader>
                    <CardTitle className="flex items-center gap-2">
                        <ReceiptIcon className="size-5" />
                        Historial de Transacciones
                    </CardTitle>
                    <CardDescription>Cargando transacciones...</CardDescription>
                </CardHeader>
                <CardContent>
                    <div className="space-y-3">
                        {[1, 2, 3, 4, 5].map((i) => (
                            <div
                                key={i}
                                className="h-12 bg-muted/50 rounded animate-pulse"
                            />
                        ))}
                    </div>
                </CardContent>
            </Card>
        );
    }

    // Error state
    if (error && !isLoading) {
        return (
            <Card>
                <CardHeader>
                    <CardTitle className="flex items-center gap-2">
                        <ReceiptIcon className="size-5" />
                        Historial de Transacciones
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <Alert variant="destructive">
                        <AlertCircleIcon />
                        <AlertDescription className="flex items-center justify-between">
                            <span>{error}</span>
                            <Button
                                variant="outline"
                                size="sm"
                                onClick={() => fetchTransactions()}
                            >
                                Reintentar
                            </Button>
                        </AlertDescription>
                    </Alert>
                </CardContent>
            </Card>
        );
    }

    // Empty state
    if (transactions.length === 0 && !isLoading) {
        return (
            <Card>
                <CardHeader>
                    <CardTitle className="flex items-center gap-2">
                        <ReceiptIcon className="size-5" />
                        Historial de Transacciones
                    </CardTitle>
                    <CardDescription>
                        Últimas transacciones procesadas en {branchName}
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <div className="text-center py-12">
                        <ReceiptIcon className="size-16 mx-auto mb-4 text-muted-foreground/50" />
                        <p className="text-muted-foreground">
                            No se han registrado transacciones en esta sucursal
                        </p>
                    </div>
                </CardContent>
            </Card>
        );
    }

    return (
        <>
            <Card>
                <CardHeader>
                    <CardTitle className="flex items-center gap-2">
                        <ReceiptIcon className="size-5" />
                        Historial de Transacciones
                    </CardTitle>
                    <CardDescription>
                        Últimas transacciones procesadas en {branchName}
                    </CardDescription>
                </CardHeader>
                <CardContent className="space-y-4">
                    {/* Desktop Table */}
                    <div className="hidden md:block overflow-x-auto">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead className="w-[140px]">Folio</TableHead>
                                    <TableHead className="w-[140px]">
                                        Fecha/Hora
                                    </TableHead>
                                    <TableHead>Empleado</TableHead>
                                    <TableHead className="w-[120px]">Tarjeta</TableHead>
                                    <TableHead className="w-[100px] text-right">
                                        Monto
                                    </TableHead>
                                    <TableHead className="w-[100px] text-right">
                                        Acciones
                                    </TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {transactions.map((tx) => (
                                    <TableRow key={tx.id}>
                                        <TableCell className="font-mono text-xs">
                                            {tx.folio}
                                        </TableCell>
                                        <TableCell className="text-xs">
                                            {tx.created_at}
                                        </TableCell>
                                        <TableCell>
                                            {tx.gift_card.user?.name || 'N/A'}
                                        </TableCell>
                                        <TableCell className="font-mono text-sm">
                                            {tx.gift_card.legacy_id}
                                        </TableCell>
                                        <TableCell className="text-right font-bold text-destructive">
                                            -${tx.amount.toFixed(2)}
                                        </TableCell>
                                        <TableCell className="text-right">
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                onClick={() => handleReprint(tx)}
                                            >
                                                <PrinterIcon className="mr-2 h-4 w-4" />
                                                Reimprimir
                                            </Button>
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    </div>

                    {/* Mobile Cards */}
                    <div className="md:hidden space-y-3">
                        {transactions.map((tx) => (
                            <Card key={tx.id}>
                                <CardContent className="p-4 space-y-3">
                                    <div className="flex justify-between items-start">
                                        <div className="space-y-1">
                                            <p className="text-xs text-muted-foreground font-mono">
                                                {tx.folio}
                                            </p>
                                            <p className="font-medium">
                                                {tx.gift_card.user?.name || 'N/A'}
                                            </p>
                                            <p className="text-sm font-mono text-muted-foreground">
                                                {tx.gift_card.legacy_id}
                                            </p>
                                        </div>
                                        <div className="text-right">
                                            <p className="font-bold text-lg text-destructive">
                                                -${tx.amount.toFixed(2)}
                                            </p>
                                            <p className="text-xs text-muted-foreground">
                                                {tx.created_at}
                                            </p>
                                        </div>
                                    </div>
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        className="w-full"
                                        onClick={() => handleReprint(tx)}
                                    >
                                        <PrinterIcon className="mr-2 h-4 w-4" />
                                        Reimprimir
                                    </Button>
                                </CardContent>
                            </Card>
                        ))}
                    </div>

                    {/* Pagination */}
                    {meta && meta.last_page > 1 && (
                        <div className="flex items-center justify-between pt-4 border-t">
                            <div className="text-sm text-muted-foreground">
                                {meta.from && meta.to ? (
                                    <>
                                        Mostrando {meta.from} a {meta.to} de {meta.total}{' '}
                                        transacciones
                                    </>
                                ) : (
                                    <>0 transacciones</>
                                )}
                            </div>
                            <div className="flex gap-2">
                                <Button
                                    variant="outline"
                                    size="sm"
                                    disabled={meta.current_page === 1 || isLoading}
                                    onClick={handlePreviousPage}
                                >
                                    <ChevronLeftIcon className="h-4 w-4 mr-1" />
                                    Anterior
                                </Button>
                                <div className="flex items-center px-3 text-sm">
                                    Página {meta.current_page} de {meta.last_page}
                                </div>
                                <Button
                                    variant="outline"
                                    size="sm"
                                    disabled={
                                        meta.current_page === meta.last_page ||
                                        isLoading
                                    }
                                    onClick={handleNextPage}
                                >
                                    Siguiente
                                    <ChevronRightIcon className="h-4 w-4 ml-1" />
                                </Button>
                            </div>
                        </div>
                    )}
                </CardContent>
            </Card>

            {/* Reprint Modal */}
            <ReprintReceiptModal
                transaction={selectedTransaction}
                isOpen={showReprintModal}
                onClose={handleCloseReprint}
            />
        </>
    );
}
