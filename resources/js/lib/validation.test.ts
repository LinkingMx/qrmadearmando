import { describe, expect, it } from 'vitest';
import { getError, isValid, validation } from './validation';

describe('Validation Library', () => {
    describe('Amount Validation', () => {
        it('should validate positive amounts', () => {
            expect(validation.amount.positive(100)).toBe(true);
            expect(validation.amount.positive(0.01)).toBe(true);
        });

        it('should reject zero and negative amounts', () => {
            expect(validation.amount.positive(0)).toBe(
                'El monto debe ser mayor a 0',
            );
            expect(validation.amount.positive(-50)).toBe(
                'El monto debe ser mayor a 0',
            );
        });

        it('should validate minimum amount', () => {
            expect(validation.amount.min(100, 50)).toBe(true);
            expect(validation.amount.min(50, 50)).toBe(true);
            expect(validation.amount.min(30, 50)).toBe('El monto mínimo es 50');
        });

        it('should validate maximum amount', () => {
            expect(validation.amount.max(50, 100)).toBe(true);
            expect(validation.amount.max(100, 100)).toBe(true);
            expect(validation.amount.max(150, 100)).toBe(
                'El monto no puede exceder 100',
            );
        });

        it('should validate amount within balance', () => {
            expect(validation.amount.withinBalance(500, 1000)).toBe(true);
            expect(validation.amount.withinBalance(1000, 1000)).toBe(true);
            expect(validation.amount.withinBalance(1500, 1000)).toContain(
                'Saldo insuficiente',
            );
        });

        it('should validate decimal places', () => {
            expect(validation.amount.validDecimals(100)).toBe(true);
            expect(validation.amount.validDecimals(100.5)).toBe(true);
            expect(validation.amount.validDecimals(100.99)).toBe(true);
            expect(validation.amount.validDecimals(100.999)).toBe(
                'Máximo 2 decimales permitidos',
            );
        });
    });

    describe('String Validation', () => {
        it('should validate required strings', () => {
            expect(validation.string.required('test')).toBe(true);
            expect(validation.string.required('')).toBe(
                'Este campo es requerido',
            );
            expect(validation.string.required('   ')).toBe(
                'Este campo es requerido',
            );
        });

        it('should validate minimum length', () => {
            expect(validation.string.minLength('hello', 3)).toBe(true);
            expect(validation.string.minLength('hello', 5)).toBe(true);
            expect(validation.string.minLength('hi', 5)).toBe(
                'Mínimo 5 caracteres',
            );
        });

        it('should validate maximum length', () => {
            expect(validation.string.maxLength('hello', 10)).toBe(true);
            expect(validation.string.maxLength('hello', 5)).toBe(true);
            expect(validation.string.maxLength('hello world', 5)).toBe(
                'Máximo 5 caracteres',
            );
        });

        it('should validate exact length', () => {
            expect(validation.string.exactLength('12345', 5)).toBe(true);
            expect(validation.string.exactLength('123', 5)).toBe(
                'Debe tener 5 caracteres',
            );
            expect(validation.string.exactLength('1234567', 5)).toBe(
                'Debe tener 5 caracteres',
            );
        });

        it('should validate regex patterns', () => {
            const alphanumeric = /^[a-zA-Z0-9]+$/;
            expect(
                validation.string.pattern('abc123', alphanumeric, 'Invalid'),
            ).toBe(true);
            expect(
                validation.string.pattern('abc-123', alphanumeric, 'Invalid'),
            ).toBe('Invalid');
        });
    });

    describe('Email Validation', () => {
        it('should validate correct email format', () => {
            expect(validation.email.format('user@example.com')).toBe(true);
            expect(validation.email.format('test.user@domain.co.uk')).toBe(
                true,
            );
        });

        it('should reject invalid email format', () => {
            expect(validation.email.format('invalid')).toBe('Email inválido');
            expect(validation.email.format('user@')).toBe('Email inválido');
            expect(validation.email.format('@domain.com')).toBe(
                'Email inválido',
            );
            expect(validation.email.format('user @domain.com')).toBe(
                'Email inválido',
            );
        });

        it('should validate allowed domains', () => {
            const allowedDomains = ['example.com', 'test.com'];
            expect(
                validation.email.domain('user@example.com', allowedDomains),
            ).toBe(true);
            expect(
                validation.email.domain('user@test.com', allowedDomains),
            ).toBe(true);
            expect(
                validation.email.domain('user@other.com', allowedDomains),
            ).toContain('Solo se permiten correos de');
        });
    });

    describe('Reference Validation', () => {
        it('should validate required reference', () => {
            expect(validation.reference.required('REF-001')).toBe(true);
            expect(validation.reference.required('')).toBe(
                'La referencia es requerida',
            );
            expect(validation.reference.required('   ')).toBe(
                'La referencia es requerida',
            );
        });

        it('should validate reference format', () => {
            expect(validation.reference.format('REF-001')).toBe(true);
            expect(validation.reference.format('ABC123')).toBe(true);
            expect(validation.reference.format('test_ref')).toBe(true);
            expect(validation.reference.format('#12345')).toBe(true);
            expect(validation.reference.format('ref with spaces')).toContain(
                'Solo letras',
            );
            expect(validation.reference.format('ref@123')).toContain(
                'Solo letras',
            );
        });
    });

    describe('Date Validation', () => {
        it('should validate future dates', () => {
            const future = new Date();
            future.setDate(future.getDate() + 1);

            expect(validation.date.notPast(future)).toBe(true);
        });

        it('should reject past dates', () => {
            const past = new Date();
            past.setDate(past.getDate() - 1);

            expect(validation.date.notPast(past)).toBe(
                'La fecha no puede ser anterior a hoy',
            );
        });

        it('should validate past dates', () => {
            const past = new Date();
            past.setDate(past.getDate() - 1);

            expect(validation.date.notFuture(past)).toBe(true);
        });

        it('should reject future dates when not allowed', () => {
            const future = new Date();
            future.setDate(future.getDate() + 1);

            expect(validation.date.notFuture(future)).toBe(
                'La fecha no puede ser futura',
            );
        });

        it('should validate date ranges', () => {
            const min = new Date('2024-01-01');
            const max = new Date('2024-12-31');
            const valid = new Date('2024-06-15');
            const invalid = new Date('2025-01-01');

            expect(validation.date.range(valid, min, max)).toBe(true);
            expect(validation.date.range(invalid, min, max)).toContain(
                'debe estar entre',
            );
        });
    });

    describe('Generic Validation', () => {
        it('should validate required values', () => {
            expect(validation.generic.required('value')).toBe(true);
            expect(validation.generic.required(123)).toBe(true);
            expect(validation.generic.required(true)).toBe(true);
            expect(validation.generic.required(null)).toBe(
                'Este campo es requerido',
            );
            expect(validation.generic.required(undefined)).toBe(
                'Este campo es requerido',
            );
            expect(validation.generic.required('')).toBe(
                'Este campo es requerido',
            );
            expect(validation.generic.required('   ')).toBe(
                'Este campo es requerido',
            );
        });

        it('should validate array values', () => {
            expect(validation.generic.required(['item'])).toBe(true);
            expect(validation.generic.required([])).toBe(
                'Debe seleccionar al menos una opción',
            );
        });

        it('should validate oneOf options', () => {
            const options = ['a', 'b', 'c'];
            expect(validation.generic.oneOf('a', options)).toBe(true);
            expect(validation.generic.oneOf('d', options)).toContain(
                'Valor inválido',
            );
            expect(validation.generic.oneOf('d', options, 'Custom error')).toBe(
                'Custom error',
            );
        });
    });

    describe('Composite Validation', () => {
        it('should pass when all validations pass', () => {
            const result = validation.validate.all(
                validation.amount.positive(100),
                validation.string.required('test'),
                validation.email.format('user@example.com'),
            );

            expect(result).toBe(true);
        });

        it('should fail when any validation fails', () => {
            const result = validation.validate.all(
                validation.amount.positive(100),
                validation.string.required(''),
                validation.email.format('user@example.com'),
            );

            expect(result).toBe('Este campo es requerido');
        });

        it('should return first error encountered', () => {
            const result = validation.validate.all(
                validation.amount.positive(-1),
                validation.string.required(''),
            );

            expect(result).toBe('El monto debe ser mayor a 0');
        });

        it('should pass when any validation passes', () => {
            const result = validation.validate.any(
                validation.amount.positive(-1),
                validation.amount.positive(100),
            );

            expect(result).toBe(true);
        });

        it('should fail when all validations fail', () => {
            const result = validation.validate.any(
                validation.amount.positive(-1),
                validation.amount.positive(0),
            );

            expect(result).toBe('Ninguna validación pasó');
        });
    });

    describe('Helper Functions', () => {
        it('isValid should correctly identify valid results', () => {
            expect(isValid(true)).toBe(true);
            expect(isValid('error message')).toBe(false);
        });

        it('getError should extract error messages', () => {
            expect(getError(true)).toBeNull();
            expect(getError('error message')).toBe('error message');
        });
    });
});
