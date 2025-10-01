import { useState } from 'react';
import { Head, router } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { QRScanner } from '@/components/scanner/qr-scanner';
import { GiftCardInfo } from '@/components/scanner/gift-card-info';
import { DebitForm } from '@/components/scanner/debit-form';
import { ReceiptModal } from '@/components/scanner/receipt-modal';
import { Button } from '@/components/ui/button';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import {
    GiftCard,
    DebitFormData,
    Transaction,
    ScannerMode,
    ScannerPageProps,
} from '@/types/scanner';
import { BreadcrumbItem } from '@/types';
import { ScanIcon, AlertCircleIcon, ArrowLeftIcon } from 'lucide-react';
import axios from 'axios';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Scanner',
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

    const handleScan = async (identifier: string) => {
        setError(null);
        setIsProcessing(true);

        try {
            const response = await axios.post('/api/scanner/lookup', {
                identifier,
            });

            setGiftCard(response.data.gift_card);
            setMode('viewing');
        } catch (err: any) {
            const errorMsg =
                err.response?.data?.error || 'Error al buscar el QR. Intente nuevamente.';
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
            const response = await axios.post('/api/scanner/process-debit', {
                gift_card_id: giftCard.id,
                amount: data.amount,
                reference: data.reference,
                description: data.description,
            });

            setTransaction(response.data.transaction);
            setMode('success');
            setShowReceipt(true);
        } catch (err: any) {
            const errorMsg =
                err.response?.data?.error ||
                'Error al procesar el descuento. Intente nuevamente.';
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
            <Head title="Scanner" />

            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto p-4 md:p-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div className="space-y-1">
                        <h1 className="text-3xl font-bold flex items-center gap-3">
                            <ScanIcon className="size-8" />
                            Scanner QR Empleados
                        </h1>
                        <p className="text-muted-foreground">
                            Sucursal: <span className="font-semibold">{branch.name}</span> •
                            Usuario: <span className="font-semibold">{user.name}</span>
                        </p>
                    </div>
                </div>

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
                        <QRScanner
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

                    {/* Right Column - Gift Card Info + Debit Form */}
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
            </div>
        </AppLayout>
    );
}
