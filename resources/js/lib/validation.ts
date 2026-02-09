/**
 * Shared Validation Rules
 *
 * Centralized validation logic for consistent validation across the application.
 * These rules can be used in both forms and custom hooks.
 *
 * @example
 * ```tsx
 * import { validation } from '@/lib/validation';
 *
 * const amountError = validation.amount.positive(value);
 * if (amountError) {
 *   setError(amountError);
 * }
 * ```
 */

/**
 * Validation result type
 * Returns error message string if validation fails, true if passes
 */
export type ValidationResult = string | true;

/**
 * Amount validation rules
 */
export const amountValidation = {
    /**
     * Check if amount is positive (greater than 0)
     */
    positive: (value: number): ValidationResult => {
        return value > 0 || 'El monto debe ser mayor a 0';
    },

    /**
     * Check if amount is within range
     */
    min: (value: number, min: number): ValidationResult => {
        return value >= min || `El monto mínimo es ${min}`;
    },

    /**
     * Check if amount does not exceed maximum
     */
    max: (value: number, max: number): ValidationResult => {
        return value <= max || `El monto no puede exceder ${max}`;
    },

    /**
     * Check if amount is within balance
     */
    withinBalance: (value: number, balance: number): ValidationResult => {
        return (
            value <= balance ||
            `Saldo insuficiente. Disponible: $${balance.toFixed(2)}`
        );
    },

    /**
     * Check if amount has valid decimal places (max 2)
     */
    validDecimals: (value: number): ValidationResult => {
        const decimals = value.toString().split('.')[1]?.length || 0;
        return decimals <= 2 || 'Máximo 2 decimales permitidos';
    },
};

/**
 * String validation rules
 */
export const stringValidation = {
    /**
     * Check if string is not empty
     */
    required: (value: string): ValidationResult => {
        return !!value.trim() || 'Este campo es requerido';
    },

    /**
     * Check minimum length
     */
    minLength: (value: string, min: number): ValidationResult => {
        return value.length >= min || `Mínimo ${min} caracteres`;
    },

    /**
     * Check maximum length
     */
    maxLength: (value: string, max: number): ValidationResult => {
        return value.length <= max || `Máximo ${max} caracteres`;
    },

    /**
     * Check exact length
     */
    exactLength: (value: string, length: number): ValidationResult => {
        return value.length === length || `Debe tener ${length} caracteres`;
    },

    /**
     * Check if string matches pattern
     */
    pattern: (
        value: string,
        pattern: RegExp,
        message: string,
    ): ValidationResult => {
        return pattern.test(value) || message;
    },
};

/**
 * Email validation rules
 */
export const emailValidation = {
    /**
     * Check if email has valid format
     */
    format: (value: string): ValidationResult => {
        const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailPattern.test(value) || 'Email inválido';
    },

    /**
     * Check if email is from allowed domain
     */
    domain: (value: string, allowedDomains: string[]): ValidationResult => {
        const domain = value.split('@')[1];
        return (
            allowedDomains.includes(domain) ||
            `Solo se permiten correos de: ${allowedDomains.join(', ')}`
        );
    },
};

/**
 * Reference/Code validation rules
 */
export const referenceValidation = {
    /**
     * Check if reference is not empty
     */
    required: (value: string): ValidationResult => {
        return !!value.trim() || 'La referencia es requerida';
    },

    /**
     * Check if reference has valid format (alphanumeric + dash/underscore)
     */
    format: (value: string): ValidationResult => {
        const referencePattern = /^[a-zA-Z0-9-_#]+$/;
        return (
            referencePattern.test(value) ||
            'Solo letras, números, guiones y # permitidos'
        );
    },
};

/**
 * Date validation rules
 */
export const dateValidation = {
    /**
     * Check if date is not in the past
     */
    notPast: (value: Date): ValidationResult => {
        const now = new Date();
        now.setHours(0, 0, 0, 0);
        return value >= now || 'La fecha no puede ser anterior a hoy';
    },

    /**
     * Check if date is not in the future
     */
    notFuture: (value: Date): ValidationResult => {
        const now = new Date();
        now.setHours(23, 59, 59, 999);
        return value <= now || 'La fecha no puede ser futura';
    },

    /**
     * Check if date is within range
     */
    range: (value: Date, min: Date, max: Date): ValidationResult => {
        return (
            (value >= min && value <= max) ||
            `La fecha debe estar entre ${min.toLocaleDateString()} y ${max.toLocaleDateString()}`
        );
    },
};

/**
 * Generic validation rules
 */
export const genericValidation = {
    /**
     * Check if value is required (not null/undefined/empty)
     */
    required: (value: any): ValidationResult => {
        if (value === null || value === undefined) {
            return 'Este campo es requerido';
        }
        if (typeof value === 'string' && !value.trim()) {
            return 'Este campo es requerido';
        }
        if (Array.isArray(value) && value.length === 0) {
            return 'Debe seleccionar al menos una opción';
        }
        return true;
    },

    /**
     * Check if value is one of allowed options
     */
    oneOf: (value: any, options: any[], message?: string): ValidationResult => {
        return (
            options.includes(value) ||
            message ||
            `Valor inválido. Opciones permitidas: ${options.join(', ')}`
        );
    },
};

/**
 * Composite validation - combine multiple rules
 */
export const validate = {
    /**
     * Run multiple validations and return first error
     */
    all: (...validations: ValidationResult[]): ValidationResult => {
        for (const result of validations) {
            if (result !== true) {
                return result;
            }
        }
        return true;
    },

    /**
     * Check if at least one validation passes
     */
    any: (...validations: ValidationResult[]): ValidationResult => {
        const hasValid = validations.some((result) => result === true);
        return hasValid || 'Ninguna validación pasó';
    },
};

/**
 * Main validation object - exports all validation rules
 */
export const validation = {
    amount: amountValidation,
    string: stringValidation,
    email: emailValidation,
    reference: referenceValidation,
    date: dateValidation,
    generic: genericValidation,
    validate,
};

/**
 * Helper to check if validation passed
 */
export const isValid = (result: ValidationResult): result is true => {
    return result === true;
};

/**
 * Helper to get error message from validation result
 */
export const getError = (result: ValidationResult): string | null => {
    return typeof result === 'string' ? result : null;
};
