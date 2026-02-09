import { Alert, AlertDescription } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardFooter,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useDebitForm } from '@/hooks/use-debit-form';
import { DebitFormData, GiftCard } from '@/types/scanner';
import { AlertCircleIcon, Loader2Icon, MinusCircleIcon } from 'lucide-react';

interface DebitFormProps {
    giftCard: GiftCard;
    onSubmit: (data: DebitFormData) => Promise<void>;
    onCancel: () => void;
    isProcessing: boolean;
}

export function DebitForm({
    giftCard,
    onSubmit,
    onCancel,
    isProcessing,
}: DebitFormProps) {
    const form = useDebitForm({ giftCard });

    const handleSubmit = async (e: React.FormEvent) => {
        e.preventDefault();

        if (!form.validateForm()) {
            return;
        }

        try {
            await onSubmit(form.getFormData());
            form.reset();
        } catch (error) {
            // Error handling is done by parent component
        }
    };

    return (
        <Card className="w-full">
            <CardHeader>
                <CardTitle className="flex items-center gap-2">
                    <MinusCircleIcon className="size-5" />
                    Procesar Descuento
                </CardTitle>
            </CardHeader>
            <form onSubmit={handleSubmit}>
                <CardContent className="space-y-4">
                    {form.hasInsufficientBalance && (
                        <Alert variant="destructive">
                            <AlertCircleIcon />
                            <AlertDescription>
                                El monto excede el saldo disponible
                            </AlertDescription>
                        </Alert>
                    )}

                    {/* Amount Input */}
                    <div className="space-y-2">
                        <Label htmlFor="amount">
                            Monto a descontar{' '}
                            <span className="text-destructive">*</span>
                        </Label>
                        <div className="relative">
                            <span className="absolute top-1/2 left-3 -translate-y-1/2 text-lg font-semibold text-muted-foreground">
                                $
                            </span>
                            <Input
                                id="amount"
                                type="number"
                                step="0.01"
                                min="0.01"
                                max={giftCard.balance}
                                placeholder="0.00"
                                value={form.amount}
                                onChange={(e) => form.setAmount(e.target.value)}
                                className="h-14 pl-8 text-2xl font-bold tabular-nums"
                                aria-invalid={!!form.errors.amount}
                                disabled={isProcessing}
                            />
                        </div>
                        {form.errors.amount && (
                            <p className="text-sm text-destructive">
                                {form.errors.amount}
                            </p>
                        )}
                        {form.amount && !form.errors.amount && (
                            <p className="text-sm text-muted-foreground">
                                Saldo restante: ${form.remainingBalance.toFixed(2)}
                            </p>
                        )}
                    </div>

                    {/* Reference Input */}
                    <div className="space-y-2">
                        <Label htmlFor="reference">
                            Referencia{' '}
                            <span className="text-destructive">*</span>
                        </Label>
                        <Input
                            id="reference"
                            type="text"
                            placeholder="Ej: #12345, Ticket 001"
                            value={form.reference}
                            onChange={(e) => form.setReference(e.target.value)}
                            maxLength={255}
                            className="h-12 text-base"
                            aria-invalid={!!form.errors.reference}
                            disabled={isProcessing}
                        />
                        {form.errors.reference && (
                            <p className="text-sm text-destructive">
                                {form.errors.reference}
                            </p>
                        )}
                    </div>

                    {/* Description Input */}
                    <div className="space-y-2">
                        <Label htmlFor="description">
                            Descripción (opcional)
                        </Label>
                        <Input
                            id="description"
                            type="text"
                            placeholder="Ej: Comida del día, Compra en tienda"
                            value={form.description}
                            onChange={(e) => form.setDescription(e.target.value)}
                            maxLength={500}
                            className="h-12 text-base"
                            disabled={isProcessing}
                        />
                        <p className="text-xs text-muted-foreground">
                            {form.description.length}/500 caracteres
                        </p>
                    </div>
                </CardContent>

                <CardFooter className="flex gap-3">
                    <Button
                        type="button"
                        variant="outline"
                        onClick={onCancel}
                        disabled={isProcessing}
                        className="h-12 flex-1 text-base"
                    >
                        Cancelar
                    </Button>
                    <Button
                        type="submit"
                        disabled={isProcessing || form.hasInsufficientBalance}
                        className="h-12 flex-1 text-base"
                    >
                        {isProcessing ? (
                            <>
                                <Loader2Icon className="mr-2 size-4 animate-spin" />
                                Procesando...
                            </>
                        ) : (
                            <>
                                <MinusCircleIcon className="mr-2" />
                                Procesar Descuento
                            </>
                        )}
                    </Button>
                </CardFooter>
            </form>
        </Card>
    );
}
