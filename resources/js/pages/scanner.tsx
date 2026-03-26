import { OfflineStatusIndicator } from '@/components/offline-status-indicator';
import { BranchTransactionList } from '@/components/scanner/branch-transaction-list';
import { DebitForm } from '@/components/scanner/debit-form';
import { GiftCardInfo } from '@/components/scanner/gift-card-info';
import { QRScannerSelector } from '@/components/scanner/qr-scanner-selector';
import { TransactionReceiptModal } from '@/components/scanner/transaction-receipt-modal';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { useScannerOffline, useSyncManager } from '@/hooks/use-scanner-offline';
import AppLayout from '@/layouts/app-layout';
import axios from '@/lib/axios';
import { BreadcrumbItem } from '@/types';
import { extractResponseData } from '@/types/api';
import {
    DebitFormData,
    GiftCard,
    ScannerMode,
    ScannerPageProps,
    Transaction,
} from '@/types/scanner';
import { Head } from '@inertiajs/react';
import { AlertCircleIcon, ArrowLeftIcon, BanIcon, MapPinOffIcon, ScanIcon, ShieldXIcon } from 'lucide-react';
import { useEffect, useState } from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Escáner',
        href: '/scanner',
    },
];

export default function Scanner({ branch, user }: ScannerPageProps) {
    const [mode, setMode] = useState<ScannerMode>('scanning');
    const [giftCard, setGiftCard] = useState<GiftCard | null>(null);
    const [transaction, setTransaction] = useState<Transaction | null>(null);
    const [error, setError] = useState<string | null>(null);
    const [errorCode, setErrorCode] = useState<string | null>(null);
    const [isProcessing, setIsProcessing] = useState(false);
    const [showReceipt, setShowReceipt] = useState(false);
    const [isOnline, setIsOnline] = useState(navigator.onLine);

    // Offline-first hooks - DISABLED FOR TESTING
    // const offlineScanner = useScannerOffline();
    // const syncManager = useSyncManager();

    // Monitor online/offline status
    useEffect(() => {
        const handleOnline = () => setIsOnline(true);
        const handleOffline = () => setIsOnline(false);

        window.addEventListener('online', handleOnline);
        window.addEventListener('offline', handleOffline);

        return () => {
            window.removeEventListener('online', handleOnline);
            window.removeEventListener('offline', handleOffline);
        };
    }, []);

    // Auto-sync when coming back online - DISABLED
    // useEffect(() => {
    //     if (isOnline && syncManager.lastSyncTime === null) {
    //         syncManager.syncPending().catch(() => {
    //             // Auto-sync failed silently
    //         });
    //     }
    // }, [isOnline]);

    const handleScan = async (identifier: string) => {
        setError(null);
        setErrorCode(null);
        setIsProcessing(true);

        try {
            const response = await axios.post('/api/scanner/lookup', {
                identifier,
            });

            if (response?.data) {
                const giftCardData = extractResponseData<GiftCard>(
                    response.data,
                    'gift_card',
                );

                if (giftCardData) {
                    setGiftCard(giftCardData);
                    setMode('viewing');
                } else {
                    setError('Formato de respuesta inválido');
                }
            } else {
                setError('Respuesta del servidor vacía');
            }
        } catch (apiErr: any) {
            let errorMsg = 'Tarjeta no encontrada. Verifica el código QR e intenta nuevamente.';
            let errCode: string | null = null;

            try {
                if (apiErr?.response?.data) {
                    const errorData = apiErr.response.data;

                    if (typeof errorData.error === 'string') {
                        errorMsg = errorData.error;
                    } else if (typeof errorData.error === 'object' && errorData.error?.message) {
                        errorMsg = errorData.error.message;
                        errCode = errorData.error.code || null;
                    } else if (typeof errorData.message === 'string') {
                        errorMsg = errorData.message;
                    }
                }
            } catch {
                // Ignore extraction errors, use default message
            }

            setErrorCode(errCode);
            setError(errorMsg);
        } finally {
            setIsProcessing(false);
        }
    };

    const handleProcessDebit = async (data: DebitFormData) => {
        if (!giftCard) return;

        setError(null);
        setIsProcessing(true);

        try {
            // OFFLINE DISABLED - Use API directly
            const response = await axios.post('/api/scanner/process-debit', {
                gift_card_id: giftCard.id,
                amount: data.amount,
                description: data.description,
                reference: data.reference,
            });

            if (response?.data?.data) {
                const transactionData = response.data.data as Transaction;

                if (transactionData && transactionData.gift_card) {
                    setTransaction(transactionData);
                    setGiftCard(transactionData.gift_card);
                    setMode('success');
                    setShowReceipt(true);
                } else {
                    setError('Formato de respuesta inválido');
                }
            } else {
                setError('Respuesta del servidor vacía');
            }
        } catch (err: any) {
            let errorMsg = 'Error al procesar el descuento.';

            try {
                if (err?.response?.data) {
                    const errorData = err.response.data;

                    if (typeof errorData.error === 'string') {
                        errorMsg = errorData.error;
                    } else if (typeof errorData.error === 'object' && errorData.error?.message) {
                        errorMsg = errorData.error.message;
                    } else if (typeof errorData.message === 'string') {
                        errorMsg = errorData.message;
                    }
                }
            } catch {
                // Ignore extraction errors, use default message
            }

            setError(errorMsg);
        } finally {
            setIsProcessing(false);
        }
    };

    const handleCancel = () => {
        setGiftCard(null);
        setError(null);
        setErrorCode(null);
        setMode('scanning');
    };

    const handleCloseReceipt = () => {
        setShowReceipt(false);
        // Reset to scanning mode
        setGiftCard(null);
        setTransaction(null);
        setError(null);
        setErrorCode(null);
        setMode('scanning');
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Escáner" />

            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto p-4 md:p-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div className="space-y-1">
                        <h1 className="flex items-center gap-3 text-3xl font-bold">
                            <ScanIcon className="size-8" />
                            Scanner QR Empleados
                        </h1>
                        <p className="text-muted-foreground">
                            Sucursal:{' '}
                            <span className="font-semibold">{branch.name}</span>{' '}
                            • Usuario:{' '}
                            <span className="font-semibold">{user.name}</span>
                        </p>
                    </div>
                    {/* OFFLINE DISABLED
                    {!isOnline && (
                        <Button
                            onClick={() => syncManager.syncPending()}
                            disabled={syncManager.isSyncing}
                            variant="outline"
                            size="sm"
                        >
                            {syncManager.isSyncing
                                ? 'Sincronizando...'
                                : 'Sincronizar'}
                        </Button>
                    )}
                    */}
                </div>

                {/* Offline Status Indicator - DISABLED
                {(!isOnline ||
                    syncManager.isSyncing ||
                    offlineScanner.error?.includes('Sin conexión')) && (
                    <OfflineStatusIndicator showPendingCount={true} />
                )}
                */}

                {/* Error Display */}
                {error && (errorCode === 'INVALID_SCOPE' || errorCode === 'INACTIVE_CARD') ? (
                    <div className="flex items-center gap-4 rounded-lg border-2 border-red-300 bg-red-50 p-4 dark:border-red-800 dark:bg-red-950/30">
                        {errorCode === 'INVALID_SCOPE' ? (
                            <MapPinOffIcon className="size-10 shrink-0 text-red-500" />
                        ) : (
                            <BanIcon className="size-10 shrink-0 text-red-500" />
                        )}
                        <div className="flex-1">
                            <h2 className="text-lg font-bold text-red-700 dark:text-red-400">
                                {errorCode === 'INVALID_SCOPE'
                                    ? 'QR No Válido en esta Sucursal'
                                    : 'QR Inactivo'}
                            </h2>
                            <p className="text-sm text-red-600 dark:text-red-300">
                                {error}
                            </p>
                        </div>
                        <Button
                            onClick={handleCancel}
                            variant="outline"
                            size="sm"
                            className="shrink-0"
                        >
                            <ArrowLeftIcon className="mr-1 size-4" />
                            Otro QR
                        </Button>
                    </div>
                ) : error ? (
                    <Alert variant="destructive">
                        <AlertCircleIcon />
                        <AlertTitle>Error</AlertTitle>
                        <AlertDescription>{error}</AlertDescription>
                    </Alert>
                ) : null}

                {/* Main Content */}
                <div className="grid gap-6 lg:grid-cols-2">
                    {/* Left Column - Scanner or Back Button */}
                    {mode === 'scanning' ? (
                        <QRScannerSelector
                            onScan={handleScan}
                            onError={setError}
                            isActive={mode === 'scanning'}
                        />
                    ) : (
                        <div className="space-y-4">
                            <Button
                                onClick={handleCancel}
                                variant="outline"
                                size="lg"
                                className="w-full"
                            >
                                <ArrowLeftIcon className="mr-2" />
                                Escanear otro QR
                            </Button>

                            {giftCard && <GiftCardInfo giftCard={giftCard} />}
                        </div>
                    )}

                    {/* Right Column - Debit Form */}
                    {mode === 'viewing' && giftCard && (
                        <div className="space-y-4">
                            <DebitForm
                                giftCard={giftCard}
                                onSubmit={handleProcessDebit}
                                onCancel={handleCancel}
                                isProcessing={isProcessing}
                            />
                        </div>
                    )}
                </div>

                {/* Receipt Modal */}
                <TransactionReceiptModal
                    transaction={transaction}
                    isOpen={showReceipt}
                    onClose={handleCloseReceipt}
                    variant="success"
                />

                {/* Transaction History Section */}
                <div className="mt-8">
                    <BranchTransactionList
                        branchId={branch.id}
                        branchName={branch.name}
                    />
                </div>
            </div>
        </AppLayout>
    );
}
