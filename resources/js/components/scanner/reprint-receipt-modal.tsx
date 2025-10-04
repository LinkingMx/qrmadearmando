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
import { PrinterIcon, XIcon } from 'lucide-react';

interface ReprintReceiptModalProps {
    transaction: Transaction | null;
    isOpen: boolean;
    onClose: () => void;
}

export function ReprintReceiptModal({
    transaction,
    isOpen,
    onClose,
}: ReprintReceiptModalProps) {
    if (!transaction) return null;

    const handlePrint = () => {
        // Create print content
        const printContent = document.getElementById('reprint-receipt-content');
        if (!printContent) return;

        // Create iframe for printing
        const iframe = document.createElement('iframe');
        iframe.style.position = 'absolute';
        iframe.style.width = '0';
        iframe.style.height = '0';
        iframe.style.border = 'none';

        document.body.appendChild(iframe);

        const iframeDoc = iframe.contentWindow?.document;
        if (!iframeDoc) return;

        iframeDoc.open();
        iframeDoc.write(`
            <!DOCTYPE html>
            <html>
            <head>
                <meta charset="utf-8">
                <title>Comprobante de Transacción</title>
                <style>
                    @page {
                        size: 80mm auto;
                        margin: 5mm;
                    }
                    body {
                        margin: 0;
                        padding: 0;
                        font-family: 'Courier New', monospace;
                        font-size: 11px;
                        color: #000;
                        background: white;
                        width: 80mm;
                    }
                    .receipt {
                        padding: 0 8px;
                    }
                    .text-center {
                        text-align: center;
                    }
                    .bold {
                        font-weight: bold;
                    }
                    .separator {
                        border-top: 1px solid #000;
                        margin: 8px 0;
                    }
                    .flex {
                        display: flex;
                        justify-content: space-between;
                        margin: 4px 0;
                    }
                    .label {
                        color: #666;
                    }
                    .transaction-box {
                        border: 1px solid #ccc;
                        padding: 8px;
                        margin: 8px 0;
                    }
                    .text-red {
                        color: #dc2626;
                    }
                    .text-green {
                        color: #16a34a;
                    }
                    .text-lg {
                        font-size: 14px;
                    }
                    .text-xl {
                        font-size: 16px;
                    }
                </style>
            </head>
            <body>
                ${printContent.innerHTML}
            </body>
            </html>
        `);
        iframeDoc.close();

        // Wait for content to load then print
        iframe.contentWindow?.focus();
        setTimeout(() => {
            iframe.contentWindow?.print();
            setTimeout(() => {
                document.body.removeChild(iframe);
            }, 100);
        }, 250);
    };

    const receiptContent = (
        <div className="receipt-content px-2">
            <div className="text-center space-y-2 py-3">
                <div className="text-base sm:text-xl font-bold leading-tight">
                    ════════════════
                </div>
                <h2 className="text-sm sm:text-base font-bold uppercase leading-tight px-2">
                    Comprobante de Transacción
                </h2>
                <div className="text-base sm:text-xl font-bold leading-tight">
                    ════════════════
                </div>
            </div>

            <div className="space-y-3 py-3">
                {/* Branch and Date Info */}
                <div className="space-y-1.5 text-xs sm:text-sm">
                    <div className="flex justify-between gap-2">
                        <span className="text-muted-foreground shrink-0">
                            Sucursal:
                        </span>
                        <span className="font-medium text-right break-words max-w-[60%]">
                            {transaction.branch_name}
                        </span>
                    </div>
                    <div className="flex justify-between gap-2">
                        <span className="text-muted-foreground shrink-0">Fecha:</span>
                        <span className="font-medium text-right text-[11px] sm:text-xs">
                            {transaction.created_at}
                        </span>
                    </div>
                    <div className="flex justify-between gap-2">
                        <span className="text-muted-foreground shrink-0">Cajero:</span>
                        <span className="font-medium text-right break-words max-w-[60%]">
                            {transaction.cashier_name}
                        </span>
                    </div>
                </div>

                <Separator />

                {/* Card Info */}
                <div className="space-y-1.5 text-xs sm:text-sm">
                    <div className="flex justify-between gap-2">
                        <span className="text-muted-foreground shrink-0">Tarjeta:</span>
                        <span className="font-bold text-right break-all max-w-[60%]">
                            {transaction.gift_card.legacy_id}
                        </span>
                    </div>
                    <div className="flex justify-between gap-2">
                        <span className="text-muted-foreground shrink-0">
                            Empleado:
                        </span>
                        <span className="font-medium text-right break-words max-w-[60%]">
                            {transaction.gift_card.user?.name || 'Sin asignar'}
                        </span>
                    </div>
                </div>

                <Separator />

                {/* Transaction Details */}
                <div className="space-y-2 bg-muted/50 p-2.5 sm:p-3 rounded-lg">
                    <div className="flex justify-between items-center gap-2">
                        <span className="text-[11px] sm:text-xs text-muted-foreground shrink-0">
                            Saldo anterior:
                        </span>
                        <span className="font-mono font-medium text-xs sm:text-sm">
                            ${transaction.balance_before.toFixed(2)}
                        </span>
                    </div>
                    <div className="flex justify-between items-center gap-2 text-destructive">
                        <span className="text-[11px] sm:text-xs font-medium shrink-0">
                            Descuento:
                        </span>
                        <span className="font-mono font-bold text-sm sm:text-base">
                            -${transaction.amount.toFixed(2)}
                        </span>
                    </div>
                    <Separator />
                    <div className="flex justify-between items-center gap-2 text-green-600 dark:text-green-500">
                        <span className="text-[11px] sm:text-xs font-medium shrink-0">
                            Saldo actual:
                        </span>
                        <span className="font-mono font-bold text-base sm:text-lg">
                            ${transaction.balance_after.toFixed(2)}
                        </span>
                    </div>
                </div>

                <Separator />

                {/* Reference and Description */}
                <div className="space-y-1.5 text-xs sm:text-sm">
                    <div className="flex justify-between gap-2">
                        <span className="text-muted-foreground shrink-0">
                            Referencia:
                        </span>
                        <span className="font-medium text-right break-all max-w-[60%]">
                            {transaction.reference}
                        </span>
                    </div>
                    {transaction.description && (
                        <div>
                            <span className="text-muted-foreground">Descripción:</span>
                            <p className="font-medium mt-1 break-words">
                                {transaction.description}
                            </p>
                        </div>
                    )}
                </div>

                <Separator />

                {/* Folio */}
                <div className="text-center py-2">
                    <div className="text-base sm:text-xl font-bold leading-tight">
                        ════════════════
                    </div>
                    <p className="text-[10px] sm:text-xs text-muted-foreground mt-1.5 break-all px-2">
                        Folio: {transaction.folio}
                    </p>
                    <div className="text-base sm:text-xl font-bold mt-1.5 leading-tight">
                        ════════════════
                    </div>
                </div>
            </div>
        </div>
    );

    return (
        <>
            <Dialog open={isOpen} onOpenChange={onClose}>
                <DialogContent className="max-w-md w-[calc(100vw-2rem)] max-h-[90vh] overflow-y-auto">
                    <DialogHeader>
                        <DialogTitle>Reimprimir Comprobante</DialogTitle>
                    </DialogHeader>

                    {/* Receipt Content - Visible */}
                    {receiptContent}

                    <DialogFooter className="flex-col sm:flex-row gap-2">
                        <Button
                            onClick={handlePrint}
                            variant="default"
                            size="lg"
                            className="w-full sm:flex-1"
                        >
                            <PrinterIcon className="mr-2" />
                            Imprimir Ticket
                        </Button>
                        <Button
                            onClick={onClose}
                            variant="outline"
                            size="lg"
                            className="w-full sm:flex-1"
                        >
                            <XIcon className="mr-2" />
                            Cerrar
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            {/* Hidden content for printing */}
            <div id="reprint-receipt-content" style={{ display: 'none' }}>
                <div className="receipt">
                    <div className="text-center">
                        <div className="bold">════════════════</div>
                        <div className="bold" style={{ margin: '8px 0' }}>
                            COMPROBANTE DE TRANSACCIÓN
                        </div>
                        <div className="bold">════════════════</div>
                    </div>

                    <div className="separator"></div>

                    <div className="flex">
                        <span className="label">Sucursal:</span>
                        <span className="bold">{transaction.branch_name}</span>
                    </div>
                    <div className="flex">
                        <span className="label">Fecha:</span>
                        <span>{transaction.created_at}</span>
                    </div>
                    <div className="flex">
                        <span className="label">Cajero:</span>
                        <span>{transaction.cashier_name}</span>
                    </div>

                    <div className="separator"></div>

                    <div className="flex">
                        <span className="label">Tarjeta:</span>
                        <span className="bold">{transaction.gift_card.legacy_id}</span>
                    </div>
                    <div className="flex">
                        <span className="label">Empleado:</span>
                        <span>
                            {transaction.gift_card.user?.name || 'Sin asignar'}
                        </span>
                    </div>

                    <div className="separator"></div>

                    <div className="transaction-box">
                        <div className="flex">
                            <span className="label">Saldo anterior:</span>
                            <span className="bold">
                                ${transaction.balance_before.toFixed(2)}
                            </span>
                        </div>
                        <div className="flex text-red">
                            <span className="bold">Descuento:</span>
                            <span className="bold text-lg">
                                -${transaction.amount.toFixed(2)}
                            </span>
                        </div>
                        <div className="separator"></div>
                        <div className="flex text-green">
                            <span className="bold">Saldo actual:</span>
                            <span className="bold text-xl">
                                ${transaction.balance_after.toFixed(2)}
                            </span>
                        </div>
                    </div>

                    <div className="separator"></div>

                    <div className="flex">
                        <span className="label">Referencia:</span>
                        <span>{transaction.reference}</span>
                    </div>
                    {transaction.description && (
                        <div style={{ marginTop: '4px' }}>
                            <div className="label">Descripción:</div>
                            <div>{transaction.description}</div>
                        </div>
                    )}

                    <div className="separator"></div>

                    <div className="text-center">
                        <div className="bold">════════════════</div>
                        <div style={{ margin: '8px 0', fontSize: '10px' }}>
                            Folio: {transaction.folio}
                        </div>
                        <div className="bold">════════════════</div>
                    </div>
                </div>
            </div>
        </>
    );
}
