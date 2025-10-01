import { Transaction } from '@/types/scanner';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogFooter,
} from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';
import { CheckCircle2Icon, PrinterIcon, XIcon } from 'lucide-react';

interface ReceiptModalProps {
    transaction: Transaction | null;
    isOpen: boolean;
    onClose: () => void;
}

export function ReceiptModal({ transaction, isOpen, onClose }: ReceiptModalProps) {
    if (!transaction) return null;

    const handlePrint = () => {
        window.print();
    };

    return (
        <Dialog open={isOpen} onOpenChange={onClose}>
            <DialogContent className="max-w-md receipt-modal">
                <DialogHeader className="no-print">
                    <div className="flex items-center gap-2 text-green-600 dark:text-green-500">
                        <CheckCircle2Icon className="size-6" />
                        <DialogTitle>Transacción Exitosa</DialogTitle>
                    </div>
                </DialogHeader>

                {/* Receipt Content - Printable */}
                <div className="receipt-printable">
                    <div className="text-center space-y-3 py-4">
                        <div className="text-2xl font-bold">════════════════════</div>
                        <h2 className="text-lg font-bold uppercase">
                            Comprobante de Transacción
                        </h2>
                        <div className="text-2xl font-bold">════════════════════</div>
                    </div>

                    <div className="space-y-4 py-4">
                        {/* Branch and Date Info */}
                        <div className="space-y-1">
                            <div className="flex justify-between text-sm">
                                <span className="text-muted-foreground">Sucursal:</span>
                                <span className="font-medium">{transaction.branch_name}</span>
                            </div>
                            <div className="flex justify-between text-sm">
                                <span className="text-muted-foreground">Fecha:</span>
                                <span className="font-medium">{transaction.created_at}</span>
                            </div>
                            <div className="flex justify-between text-sm">
                                <span className="text-muted-foreground">Cajero:</span>
                                <span className="font-medium">{transaction.cashier_name}</span>
                            </div>
                        </div>

                        <Separator />

                        {/* Card Info */}
                        <div className="space-y-1">
                            <div className="flex justify-between text-sm">
                                <span className="text-muted-foreground">Tarjeta:</span>
                                <span className="font-bold">{transaction.gift_card.legacy_id}</span>
                            </div>
                            <div className="flex justify-between text-sm">
                                <span className="text-muted-foreground">Empleado:</span>
                                <span className="font-medium">
                                    {transaction.gift_card.user?.name || 'Sin asignar'}
                                </span>
                            </div>
                        </div>

                        <Separator />

                        {/* Transaction Details */}
                        <div className="space-y-2 bg-muted/50 p-3 rounded-lg">
                            <div className="flex justify-between">
                                <span className="text-sm text-muted-foreground">
                                    Saldo anterior:
                                </span>
                                <span className="font-mono font-medium">
                                    ${transaction.balance_before.toFixed(2)}
                                </span>
                            </div>
                            <div className="flex justify-between text-destructive">
                                <span className="text-sm font-medium">Descuento:</span>
                                <span className="font-mono font-bold text-lg">
                                    -${transaction.amount.toFixed(2)}
                                </span>
                            </div>
                            <Separator />
                            <div className="flex justify-between text-green-600 dark:text-green-500">
                                <span className="text-sm font-medium">Saldo actual:</span>
                                <span className="font-mono font-bold text-xl">
                                    ${transaction.balance_after.toFixed(2)}
                                </span>
                            </div>
                        </div>

                        <Separator />

                        {/* Reference and Description */}
                        <div className="space-y-1">
                            <div className="flex justify-between text-sm">
                                <span className="text-muted-foreground">Referencia:</span>
                                <span className="font-medium">{transaction.reference}</span>
                            </div>
                            {transaction.description && (
                                <div className="text-sm">
                                    <span className="text-muted-foreground">Descripción:</span>
                                    <p className="font-medium mt-1">{transaction.description}</p>
                                </div>
                            )}
                        </div>

                        <Separator />

                        {/* Folio */}
                        <div className="text-center py-2">
                            <div className="text-2xl font-bold">════════════════════</div>
                            <p className="text-xs text-muted-foreground mt-2">
                                Folio: {transaction.folio}
                            </p>
                            <div className="text-2xl font-bold mt-2">════════════════════</div>
                        </div>
                    </div>
                </div>

                <DialogFooter className="no-print gap-2">
                    <Button
                        onClick={handlePrint}
                        variant="default"
                        size="lg"
                        className="flex-1"
                    >
                        <PrinterIcon className="mr-2" />
                        Imprimir Ticket
                    </Button>
                    <Button
                        onClick={onClose}
                        variant="outline"
                        size="lg"
                        className="flex-1"
                    >
                        <XIcon className="mr-2" />
                        Cerrar
                    </Button>
                </DialogFooter>

                <style>{`
                    @media print {
                        @page {
                            size: 80mm auto;
                            margin: 5mm;
                        }

                        body * {
                            visibility: hidden;
                        }

                        .receipt-printable,
                        .receipt-printable * {
                            visibility: visible;
                        }

                        .receipt-printable {
                            position: absolute;
                            left: 0;
                            top: 0;
                            width: 80mm;
                            font-family: 'Courier New', monospace;
                            font-size: 12px;
                            color: black;
                        }

                        .no-print {
                            display: none !important;
                        }

                        .receipt-modal {
                            max-width: 80mm !important;
                            border: none !important;
                            box-shadow: none !important;
                        }
                    }
                `}</style>
            </DialogContent>
        </Dialog>
    );
}
