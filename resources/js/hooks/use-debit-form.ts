import { getError, validation } from '@/lib/validation';
import { DebitFormData, GiftCard } from '@/types/scanner';
import { useCallback, useMemo, useState } from 'react';

interface DebitFormErrors {
    amount?: string;
    reference?: string;
    description?: string;
}

interface UseDebitFormOptions {
    giftCard: GiftCard;
    onSuccess?: () => void;
}

interface UseDebitFormReturn {
    // Form state
    amount: string;
    reference: string;
    description: string;
    errors: DebitFormErrors;

    // Computed values
    remainingBalance: number;
    hasInsufficientBalance: boolean;
    isValid: boolean;

    // Actions
    setAmount: (value: string) => void;
    setReference: (value: string) => void;
    setDescription: (value: string) => void;
    validateForm: () => boolean;
    reset: () => void;
    getFormData: () => DebitFormData;
}

/**
 * Custom hook for managing debit form state and validation
 *
 * Handles:
 * - Form field state (amount, reference, description)
 * - Validation logic (amount range, required fields)
 * - Computed values (remaining balance, insufficient balance check)
 * - Form reset
 *
 * @example
 * ```tsx
 * const form = useDebitForm({ giftCard });
 *
 * <Input
 *   value={form.amount}
 *   onChange={(e) => form.setAmount(e.target.value)}
 * />
 *
 * <Button onClick={() => {
 *   if (form.validateForm()) {
 *     await onSubmit(form.getFormData());
 *     form.reset();
 *   }
 * }}>
 *   Submit
 * </Button>
 * ```
 */
export function useDebitForm({
    giftCard,
    onSuccess,
}: UseDebitFormOptions): UseDebitFormReturn {
    const [amount, setAmount] = useState('');
    const [reference, setReference] = useState('');
    const [description, setDescription] = useState('');
    const [errors, setErrors] = useState<DebitFormErrors>({});

    /**
     * Calculate remaining balance after debit
     */
    const remainingBalance = useMemo(() => {
        const debitAmount = parseFloat(amount || '0');
        return giftCard.balance - debitAmount;
    }, [amount, giftCard.balance]);

    /**
     * Check if amount exceeds available balance
     */
    const hasInsufficientBalance = useMemo(() => {
        const debitAmount = parseFloat(amount || '0');
        return debitAmount > giftCard.balance;
    }, [amount, giftCard.balance]);

    /**
     * Check if form is valid (no errors)
     */
    const isValid = useMemo(() => {
        return Object.keys(errors).length === 0;
    }, [errors]);

    /**
     * Validate all form fields using shared validation rules
     * Sets errors and returns validation result
     */
    const validateForm = useCallback((): boolean => {
        const newErrors: DebitFormErrors = {};

        // Validate amount using shared validation rules
        const amountValue = parseFloat(amount);
        if (!amount || isNaN(amountValue)) {
            newErrors.amount = 'El monto es requerido';
        } else {
            // Check if positive
            const positiveResult = validation.amount.positive(amountValue);
            if (positiveResult !== true) {
                newErrors.amount = positiveResult;
            } else {
                // Check if within balance
                const balanceResult = validation.amount.withinBalance(
                    amountValue,
                    giftCard.balance,
                );
                if (balanceResult !== true) {
                    newErrors.amount = balanceResult;
                }
            }
        }

        // Validate reference using shared validation rules
        const referenceResult = validation.reference.required(reference);
        const referenceError = getError(referenceResult);
        if (referenceError) {
            newErrors.reference = referenceError;
        }

        // Description is optional, no validation needed

        setErrors(newErrors);
        return Object.keys(newErrors).length === 0;
    }, [amount, reference, giftCard.balance]);

    /**
     * Reset form to initial state
     */
    const reset = useCallback(() => {
        setAmount('');
        setReference('');
        setDescription('');
        setErrors({});

        if (onSuccess) {
            onSuccess();
        }
    }, [onSuccess]);

    /**
     * Get form data in the correct format for submission
     */
    const getFormData = useCallback((): DebitFormData => {
        return {
            amount: parseFloat(amount),
            reference: reference.trim(),
            description: description.trim() || undefined,
        };
    }, [amount, reference, description]);

    return {
        // State
        amount,
        reference,
        description,
        errors,

        // Computed
        remainingBalance,
        hasInsufficientBalance,
        isValid,

        // Actions
        setAmount,
        setReference,
        setDescription,
        validateForm,
        reset,
        getFormData,
    };
}
