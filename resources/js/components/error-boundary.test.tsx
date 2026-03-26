import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { describe, expect, it, vi } from 'vitest';
import { ErrorBoundary } from './error-boundary';

// Component that throws an error on demand
function ThrowError({ shouldThrow }: { shouldThrow: boolean }) {
    if (shouldThrow) {
        throw new Error('Test error message');
    }
    return <div>No error</div>;
}

describe('ErrorBoundary', () => {
    // Suppress console.error in tests
    const originalError = console.error;
    beforeEach(() => {
        console.error = vi.fn();
    });

    afterEach(() => {
        console.error = originalError;
    });

    describe('Normal Rendering', () => {
        it('should render children when no error occurs', () => {
            render(
                <ErrorBoundary>
                    <div>Test content</div>
                </ErrorBoundary>,
            );

            expect(screen.getByText('Test content')).toBeInTheDocument();
        });

        it('should not show error UI when children render successfully', () => {
            render(
                <ErrorBoundary>
                    <ThrowError shouldThrow={false} />
                </ErrorBoundary>,
            );

            expect(
                screen.queryByText('Algo salió mal'),
            ).not.toBeInTheDocument();
            expect(screen.getByText('No error')).toBeInTheDocument();
        });
    });

    describe('Error Handling', () => {
        it('should catch errors and display error UI', () => {
            render(
                <ErrorBoundary>
                    <ThrowError shouldThrow={true} />
                </ErrorBoundary>,
            );

            expect(screen.getByText('Algo salió mal')).toBeInTheDocument();
            expect(
                screen.getByText('Ocurrió un error inesperado'),
            ).toBeInTheDocument();
        });

        it('should show error message in error UI', () => {
            render(
                <ErrorBoundary>
                    <ThrowError shouldThrow={true} />
                </ErrorBoundary>,
            );

            expect(
                screen.getByText(/la aplicación encontró un error/i),
            ).toBeInTheDocument();
        });

        it('should display reload button', () => {
            render(
                <ErrorBoundary>
                    <ThrowError shouldThrow={true} />
                </ErrorBoundary>,
            );

            const reloadButton = screen.getByRole('button', {
                name: /Recargar Página/i,
            });
            expect(reloadButton).toBeInTheDocument();
        });
    });

    describe('Error Recovery', () => {
        it('should reload page when reload button is clicked', async () => {
            const user = userEvent.setup();

            // Mock window.location.reload
            const reloadMock = vi.fn();
            Object.defineProperty(window, 'location', {
                value: { reload: reloadMock },
                writable: true,
            });

            render(
                <ErrorBoundary>
                    <ThrowError shouldThrow={true} />
                </ErrorBoundary>,
            );

            const reloadButton = screen.getByRole('button', {
                name: /Recargar Página/i,
            });

            await user.click(reloadButton);

            expect(reloadMock).toHaveBeenCalledOnce();
        });
    });

    describe('Custom Fallback', () => {
        it('should render custom fallback when provided', () => {
            const customFallback = <div>Custom error message</div>;

            render(
                <ErrorBoundary fallback={customFallback}>
                    <ThrowError shouldThrow={true} />
                </ErrorBoundary>,
            );

            expect(
                screen.getByText('Custom error message'),
            ).toBeInTheDocument();
            expect(
                screen.queryByText('Algo salió mal'),
            ).not.toBeInTheDocument();
        });
    });

    describe('Error Details', () => {
        it('should show error details when in development', () => {
            // In test environment, DEV is usually true
            // If error boundary renders details, this test will pass
            render(
                <ErrorBoundary>
                    <ThrowError shouldThrow={true} />
                </ErrorBoundary>,
            );

            // Check for details element (visible in dev mode)
            const details = screen.queryByText(/Detalles del error/i);

            // In development, should show details
            if (import.meta.env.DEV) {
                expect(details).toBeInTheDocument();
            } else {
                // In production, should show support message
                expect(
                    screen.getByText(/contacta al equipo de soporte/i),
                ).toBeInTheDocument();
            }
        });

        it('should show appropriate buttons based on environment', () => {
            render(
                <ErrorBoundary>
                    <ThrowError shouldThrow={true} />
                </ErrorBoundary>,
            );

            const recoveryButton = screen.queryByRole('button', {
                name: /Intentar Recuperar/i,
            });
            const reloadButton = screen.queryByRole('button', {
                name: /Recargar Página/i,
            });

            // Reload button should always be present
            expect(reloadButton).toBeInTheDocument();

            // Recovery button only in development
            if (import.meta.env.DEV) {
                expect(recoveryButton).toBeInTheDocument();
            } else {
                expect(recoveryButton).not.toBeInTheDocument();
            }
        });
    });
});
