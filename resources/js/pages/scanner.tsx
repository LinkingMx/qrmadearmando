import { BranchTransactionList } from '@/components/scanner/branch-transaction-list';
import { DebitForm } from '@/components/scanner/debit-form';
import { GiftCardInfo } from '@/components/scanner/gift-card-info';
import { QRScannerSelector } from '@/components/scanner/qr-scanner-selector';
import { ReceiptModal } from '@/components/scanner/receipt-modal';
import { OfflineStatusIndicator } from '@/components/offline-status-indicator';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import axios from '@/lib/axios';
import { BreadcrumbItem } from '@/types';
import {
    DebitFormData,
    GiftCard,
    ScannerMode,
    ScannerPageProps,
    Transaction,
} from '@/types/scanner';
import { extractResponseData } from '@/types/api';
import { Head } from '@inertiajs/react';
import { AlertCircleIcon, ArrowLeftIcon, ScanIcon } from 'lucide-react';
import { useState, useEffect } from 'react';
import { useScannerOffline } from '@/hooks/use-scanner-offline';
import { useSyncManager } from '@/hooks/use-scanner-offline';

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
    const [isProcessing, setIsProcessing] = useState(false);
    const [showReceipt, setShowReceipt] = useState(false);
    const [isOnline, setIsOnline] = useState(navigator.onLine);

    // Offline-first hooks
    const offlineScanner = useScannerOffline();
    const syncManager = useSyncManager();

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

    // Auto-sync when coming back online
    useEffect(() => {
        if (isOnline && syncManager.lastSyncTime === null) {
            syncManager.syncPending().catch(() => {
                // Auto-sync failed silently
            });
        }
    }, [isOnline]);

    const handleScan = async (identifier: string) => {
        setError(null);
        setIsProcessing(true);

        try {
            // Try offline-first approach
            const card = await offlineScanner.scan(identifier);

            if (!card) {
                // Fall back to API if offline scan fails
                try {
                    const response = await axios.post('/api/scanner/lookup', {
                        identifier,
                    });

                    // Support both new format { data: GiftCard } and old format { gift_card: GiftCard }
                    const giftCardData = extractResponseData<GiftCard>(
                        response.data,
                        'gift_card'
                    );

                    if (giftCardData) {
                        setGiftCard(giftCardData);
                        setMode('viewing');
                    } else {
                        throw new Error('Formato de respuesta inválido');
                    }
                } catch (apiErr: any) {
                    const errorMsg =
                        apiErr.response?.data?.error ||
                        'Error al buscar el QR. Intente nuevamente.';
                    setError(errorMsg);
                }
            } else {
                setGiftCard(card as GiftCard);
                setMode('viewing');
            }
        } catch (err: any) {
            const errorMsg = err.message || 'Error al buscar el QR.';
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
            // Use offline-capable debit processing
            const offlineTransaction = await offlineScanner.processDebit(
                giftCard.legacy_id,
                data.amount,
                data.description
            );

            if (offlineTransaction) {
                setTransaction({
                    id: offlineTransaction.id,
                    gift_card_id: offlineTransaction.gift_card_id,
                    type: 'debit',
                    amount: offlineTransaction.amount,
                    balance_before: offlineTransaction.balance_before,
                    balance_after: offlineTransaction.balance_after,
                    created_at: new Date(offlineTransaction.created_at),
                    user_id: user.id,
                    branch_id: branch.id,
                } as Transaction);
                setMode('success');
                setShowReceipt(true);

                // Update gift card balance locally
                setGiftCard({
                    ...giftCard,
                    balance: offlineTransaction.balance_after,
                });
            } else {
                setError(
                    offlineScanner.error ||
                        'Error al procesar el descuento. Intente nuevamente.'
                );
            }
        } catch (err: any) {
            const errorMsg =
                err.response?.data?.error ||
                err.message ||
                'Error al procesar el descuento.';
            setError(errorMsg);
        } finally {
            setIsProcessing(false);
        }
    };

    const handleCancel = () => {
        setGiftCard(null);
        setError(null);
        setMode('scanning');
    };

    const handleCloseReceipt = () => {
        setShowReceipt(false);
        // Reset to scanning mode
        setGiftCard(null);
        setTransaction(null);
        setError(null);
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
                    {!isOnline && (
                        <Button
                            onClick={() => syncManager.syncPending()}
                            disabled={syncManager.isSyncing}
                            variant="outline"
                            size="sm"
                        >
                            {syncManager.isSyncing ? 'Sincronizando...' : 'Sincronizar'}
                        </Button>
                    )}
                </div>

                {/* Offline Status Indicator */}
                {(!isOnline || (syncManager.isSyncing || (offlineScanner.error?.includes('Sin conexión')))) && (
                    <OfflineStatusIndicator showPendingCount={true} />
                )}

                {/* Error Alert */}
                {error && (
                    <Alert variant="destructive">
                        <AlertCircleIcon />
                        <AlertTitle>Error</AlertTitle>
                        <AlertDescription>{error}</AlertDescription>
                    </Alert>
                )}

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
                <ReceiptModal
                    transaction={transaction}
                    isOpen={showReceipt}
                    onClose={handleCloseReceipt}
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
