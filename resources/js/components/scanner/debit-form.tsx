import { useState } from 'react';
import { DebitFormData, GiftCard } from '@/types/scanner';
import { Card, CardContent, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { MinusCircleIcon, AlertCircleIcon, Loader2Icon } from 'lucide-react';

interface DebitFormProps {
    giftCard: GiftCard;
    onSubmit: (data: DebitFormData) => Promise<void>;
    onCancel: () => void;
    isProcessing: boolean;
}

export function DebitForm({ giftCard, onSubmit, onCancel, isProcessing }: DebitFormProps) {
    const [amount, setAmount] = useState('');
    const [reference, setReference] = useState('');
    const [description, setDescription] = useState('');
    const [errors, setErrors] = useState<Record<string, string>>({});

    const validateForm = (): boolean => {
        const newErrors: Record<string, string> = {};

        if (!amount || parseFloat(amount) <= 0) {
            newErrors.amount = 'El monto debe ser mayor a 0';
        } else if (parseFloat(amount) > giftCard.balance) {
            newErrors.amount = `Saldo insuficiente. Disponible: $${giftCard.balance.toFixed(2)}`;
        }

        if (!reference.trim()) {
            newErrors.reference = 'La referencia es requerida';
        }

        setErrors(newErrors);
        return Object.keys(newErrors).length === 0;
    };

    const handleSubmit = async (e: React.FormEvent) => {
        e.preventDefault();

        if (!validateForm()) {
            return;
        }

        try {
            await onSubmit({
                amount: parseFloat(amount),
                reference: reference.trim(),
                description: description.trim() || undefined,
            });
        } catch (error) {
            // Error handling is done by parent component
        }
    };

    const remainingBalance = amount
        ? giftCard.balance - parseFloat(amount || '0')
        : giftCard.balance;

    const hasInsufficientBalance = parseFloat(amount || '0') > giftCard.balance;

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
                    {hasInsufficientBalance && (
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
                            Monto a descontar <span className="text-destructive">*</span>
                        </Label>
                        <div className="relative">
                            <span className="absolute left-3 top-1/2 -translate-y-1/2 text-muted-foreground text-lg font-semibold">
                                $
                            </span>
                            <Input
                                id="amount"
                                type="number"
                                step="0.01"
                                min="0.01"
                                max={giftCard.balance}
                                placeholder="0.00"
                                value={amount}
                                onChange={(e) => setAmount(e.target.value)}
                                className="h-14 text-2xl font-bold pl-8 tabular-nums"
                                aria-invalid={!!errors.amount}
                                disabled={isProcessing}
                            />
                        </div>
                        {errors.amount && (
                            <p className="text-sm text-destructive">{errors.amount}</p>
                        )}
                        {amount && !errors.amount && (
                            <p className="text-sm text-muted-foreground">
                                Saldo restante: ${remainingBalance.toFixed(2)}
                            </p>
                        )}
                    </div>

                    {/* Reference Input */}
                    <div className="space-y-2">
                        <Label htmlFor="reference">
                            Referencia <span className="text-destructive">*</span>
                        </Label>
                        <Input
                            id="reference"
                            type="text"
                            placeholder="Ej: #12345, Ticket 001"
                            value={reference}
                            onChange={(e) => setReference(e.target.value)}
                            maxLength={255}
                            className="h-12 text-base"
                            aria-invalid={!!errors.reference}
                            disabled={isProcessing}
                        />
                        {errors.reference && (
                            <p className="text-sm text-destructive">{errors.reference}</p>
                        )}
                    </div>

                    {/* Description Input */}
                    <div className="space-y-2">
                        <Label htmlFor="description">Descripción (opcional)</Label>
                        <Input
                            id="description"
                            type="text"
                            placeholder="Ej: Comida del día, Compra en tienda"
                            value={description}
                            onChange={(e) => setDescription(e.target.value)}
                            maxLength={500}
                            className="h-12 text-base"
                            disabled={isProcessing}
                        />
                        <p className="text-xs text-muted-foreground">
                            {description.length}/500 caracteres
                        </p>
                    </div>
                </CardContent>

                <CardFooter className="flex gap-3">
                    <Button
                        type="button"
                        variant="outline"
                        onClick={onCancel}
                        disabled={isProcessing}
                        className="flex-1 h-12 text-base"
                    >
                        Cancelar
                    </Button>
                    <Button
                        type="submit"
                        disabled={isProcessing || hasInsufficientBalance}
                        className="flex-1 h-12 text-base"
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
