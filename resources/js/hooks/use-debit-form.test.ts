import type { GiftCard } from '@/types/scanner';
import { act, renderHook } from '@testing-library/react';
import { beforeEach, describe, expect, it } from 'vitest';
import { useDebitForm } from './use-debit-form';

describe('useDebitForm', () => {
    const mockGiftCard: GiftCard = {
        id: '123e4567-e89b-12d3-a456-426614174000',
        legacy_id: 'TEST000001',
        balance: 1000,
        status: true,
        expiry_date: null,
        qr_image_path: null,
        user: null,
    };

    beforeEach(() => {
        // Reset any state if needed
    });

    describe('Initial State', () => {
        it('should initialize with empty values', () => {
            const { result } = renderHook(() =>
                useDebitForm({ giftCard: mockGiftCard }),
            );

            expect(result.current.amount).toBe('');
            expect(result.current.reference).toBe('');
            expect(result.current.description).toBe('');
            expect(result.current.errors).toEqual({});
        });

        it('should calculate remaining balance correctly on init', () => {
            const { result } = renderHook(() =>
                useDebitForm({ giftCard: mockGiftCard }),
            );

            expect(result.current.remainingBalance).toBe(1000);
            expect(result.current.hasInsufficientBalance).toBe(false);
        });

        it('should be valid initially with no errors', () => {
            const { result } = renderHook(() =>
                useDebitForm({ giftCard: mockGiftCard }),
            );

            expect(result.current.isValid).toBe(true);
        });
    });

    describe('Amount Validation', () => {
        it('should validate positive amounts', () => {
            const { result } = renderHook(() =>
                useDebitForm({ giftCard: mockGiftCard }),
            );

            act(() => {
                result.current.setAmount('100');
                result.current.setReference('REF-001');
            });

            let isValid: boolean;
            act(() => {
                isValid = result.current.validateForm();
            });

            expect(isValid).toBe(true);
            expect(result.current.errors.amount).toBeUndefined();
        });

        it('should reject zero amount', () => {
            const { result } = renderHook(() =>
                useDebitForm({ giftCard: mockGiftCard }),
            );

            act(() => {
                result.current.setAmount('0');
                result.current.setReference('REF-001');
            });

            let isValid: boolean;
            act(() => {
                isValid = result.current.validateForm();
            });

            expect(isValid).toBe(false);
            expect(result.current.errors.amount).toBe(
                'El monto debe ser mayor a 0',
            );
        });

        it('should reject negative amount', () => {
            const { result } = renderHook(() =>
                useDebitForm({ giftCard: mockGiftCard }),
            );

            act(() => {
                result.current.setAmount('-50');
                result.current.setReference('REF-001');
            });

            let isValid: boolean;
            act(() => {
                isValid = result.current.validateForm();
            });

            expect(isValid).toBe(false);
            expect(result.current.errors.amount).toBe(
                'El monto debe ser mayor a 0',
            );
        });

        it('should reject amount exceeding balance', () => {
            const { result } = renderHook(() =>
                useDebitForm({ giftCard: mockGiftCard }),
            );

            act(() => {
                result.current.setAmount('1500');
                result.current.setReference('REF-001');
            });

            let isValid: boolean;
            act(() => {
                isValid = result.current.validateForm();
            });

            expect(isValid).toBe(false);
            expect(result.current.errors.amount).toContain(
                'Saldo insuficiente',
            );
        });

        it('should reject empty amount', () => {
            const { result } = renderHook(() =>
                useDebitForm({ giftCard: mockGiftCard }),
            );

            act(() => {
                result.current.setAmount('');
                result.current.setReference('REF-001');
            });

            let isValid: boolean;
            act(() => {
                isValid = result.current.validateForm();
            });

            expect(isValid).toBe(false);
            expect(result.current.errors.amount).toBe('El monto es requerido');
        });

        it('should accept amount exactly at balance', () => {
            const { result } = renderHook(() =>
                useDebitForm({ giftCard: mockGiftCard }),
            );

            act(() => {
                result.current.setAmount('1000');
                result.current.setReference('REF-001');
            });

            let isValid: boolean;
            act(() => {
                isValid = result.current.validateForm();
            });

            expect(isValid).toBe(true);
            expect(result.current.errors.amount).toBeUndefined();
        });
    });

    describe('Reference Validation', () => {
        it('should validate non-empty reference', () => {
            const { result } = renderHook(() =>
                useDebitForm({ giftCard: mockGiftCard }),
            );

            act(() => {
                result.current.setAmount('100');
                result.current.setReference('REF-001');
            });

            let isValid: boolean;
            act(() => {
                isValid = result.current.validateForm();
            });

            expect(isValid).toBe(true);
            expect(result.current.errors.reference).toBeUndefined();
        });

        it('should reject empty reference', () => {
            const { result } = renderHook(() =>
                useDebitForm({ giftCard: mockGiftCard }),
            );

            act(() => {
                result.current.setAmount('100');
                result.current.setReference('');
            });

            let isValid: boolean;
            act(() => {
                isValid = result.current.validateForm();
            });

            expect(isValid).toBe(false);
            expect(result.current.errors.reference).toBe(
                'La referencia es requerida',
            );
        });

        it('should reject whitespace-only reference', () => {
            const { result } = renderHook(() =>
                useDebitForm({ giftCard: mockGiftCard }),
            );

            act(() => {
                result.current.setAmount('100');
                result.current.setReference('   ');
            });

            let isValid: boolean;
            act(() => {
                isValid = result.current.validateForm();
            });

            expect(isValid).toBe(false);
            expect(result.current.errors.reference).toBe(
                'La referencia es requerida',
            );
        });
    });

    describe('Description Field', () => {
        it('should allow empty description (optional)', () => {
            const { result } = renderHook(() =>
                useDebitForm({ giftCard: mockGiftCard }),
            );

            act(() => {
                result.current.setAmount('100');
                result.current.setReference('REF-001');
                result.current.setDescription('');
            });

            let isValid: boolean;
            act(() => {
                isValid = result.current.validateForm();
            });

            expect(isValid).toBe(true);
        });

        it('should allow description with content', () => {
            const { result } = renderHook(() =>
                useDebitForm({ giftCard: mockGiftCard }),
            );

            act(() => {
                result.current.setAmount('100');
                result.current.setReference('REF-001');
                result.current.setDescription('Test purchase');
            });

            let isValid: boolean;
            act(() => {
                isValid = result.current.validateForm();
            });

            expect(isValid).toBe(true);
        });
    });

    describe('Computed Values', () => {
        it('should calculate remaining balance correctly', () => {
            const { result } = renderHook(() =>
                useDebitForm({ giftCard: mockGiftCard }),
            );

            act(() => {
                result.current.setAmount('250');
            });

            expect(result.current.remainingBalance).toBe(750);
        });

        it('should detect insufficient balance', () => {
            const { result } = renderHook(() =>
                useDebitForm({ giftCard: mockGiftCard }),
            );

            act(() => {
                result.current.setAmount('1500');
            });

            expect(result.current.hasInsufficientBalance).toBe(true);
        });

        it('should not detect insufficient balance for valid amount', () => {
            const { result } = renderHook(() =>
                useDebitForm({ giftCard: mockGiftCard }),
            );

            act(() => {
                result.current.setAmount('500');
            });

            expect(result.current.hasInsufficientBalance).toBe(false);
        });

        it('should track validation state', () => {
            const { result } = renderHook(() =>
                useDebitForm({ giftCard: mockGiftCard }),
            );

            // Initially valid (no errors)
            expect(result.current.isValid).toBe(true);

            // After validation fails
            act(() => {
                result.current.setAmount('2000');
                result.current.validateForm();
            });

            expect(result.current.isValid).toBe(false);
        });
    });

    describe('Form Actions', () => {
        it('should reset form to initial state', () => {
            const { result } = renderHook(() =>
                useDebitForm({ giftCard: mockGiftCard }),
            );

            // Set some values
            act(() => {
                result.current.setAmount('100');
                result.current.setReference('REF-001');
                result.current.setDescription('Test');
            });

            // Reset
            act(() => {
                result.current.reset();
            });

            expect(result.current.amount).toBe('');
            expect(result.current.reference).toBe('');
            expect(result.current.description).toBe('');
            expect(result.current.errors).toEqual({});
        });

        it('should call onSuccess callback when reset', () => {
            let callbackCalled = false;
            const onSuccess = () => {
                callbackCalled = true;
            };

            const { result } = renderHook(() =>
                useDebitForm({ giftCard: mockGiftCard, onSuccess }),
            );

            act(() => {
                result.current.reset();
            });

            expect(callbackCalled).toBe(true);
        });

        it('should return correct form data', () => {
            const { result } = renderHook(() =>
                useDebitForm({ giftCard: mockGiftCard }),
            );

            act(() => {
                result.current.setAmount('250.50');
                result.current.setReference('REF-123');
                result.current.setDescription('  Test description  ');
            });

            const formData = result.current.getFormData();

            expect(formData).toEqual({
                amount: 250.5,
                reference: 'REF-123',
                description: 'Test description',
            });
        });

        it('should omit description if empty', () => {
            const { result } = renderHook(() =>
                useDebitForm({ giftCard: mockGiftCard }),
            );

            act(() => {
                result.current.setAmount('100');
                result.current.setReference('REF-001');
                result.current.setDescription('');
            });

            const formData = result.current.getFormData();

            expect(formData).toEqual({
                amount: 100,
                reference: 'REF-001',
                description: undefined,
            });
        });
    });

    describe('Edge Cases', () => {
        it('should handle decimal amounts correctly', () => {
            const { result } = renderHook(() =>
                useDebitForm({ giftCard: mockGiftCard }),
            );

            act(() => {
                result.current.setAmount('99.99');
                result.current.setReference('REF-001');
            });

            let isValid: boolean;
            act(() => {
                isValid = result.current.validateForm();
            });

            expect(isValid).toBe(true);
            expect(result.current.remainingBalance).toBeCloseTo(900.01, 2);
        });

        it('should handle gift card with zero balance', () => {
            const zeroBalanceCard: GiftCard = {
                ...mockGiftCard,
                balance: 0,
            };

            const { result } = renderHook(() =>
                useDebitForm({ giftCard: zeroBalanceCard }),
            );

            act(() => {
                result.current.setAmount('1');
                result.current.setReference('REF-001');
            });

            let isValid: boolean;
            act(() => {
                isValid = result.current.validateForm();
            });

            expect(isValid).toBe(false);
            expect(result.current.errors.amount).toContain(
                'Saldo insuficiente',
            );
        });

        it('should handle very large amounts', () => {
            const { result } = renderHook(() =>
                useDebitForm({ giftCard: mockGiftCard }),
            );

            act(() => {
                result.current.setAmount('999999');
                result.current.setReference('REF-001');
            });

            let isValid: boolean;
            act(() => {
                isValid = result.current.validateForm();
            });

            expect(isValid).toBe(false);
            expect(result.current.errors.amount).toContain(
                'Saldo insuficiente',
            );
        });
    });
});
