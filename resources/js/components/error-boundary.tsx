import React, { Component, ErrorInfo, ReactNode } from 'react';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardFooter,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { AlertCircleIcon, RefreshCwIcon } from 'lucide-react';

interface Props {
    children: ReactNode;
    fallback?: ReactNode;
}

interface State {
    hasError: boolean;
    error: Error | null;
    errorInfo: ErrorInfo | null;
}

/**
 * Error Boundary Component
 *
 * Catches unhandled errors in the React component tree and displays
 * a user-friendly error message instead of a white screen.
 *
 * Features:
 * - Graceful error handling with Spanish UI
 * - Error details (collapsible for debugging)
 * - Reload button to recover
 * - Optional error tracking integration
 *
 * @example
 * ```tsx
 * <ErrorBoundary>
 *   <App />
 * </ErrorBoundary>
 * ```
 */
export class ErrorBoundary extends Component<Props, State> {
    constructor(props: Props) {
        super(props);
        this.state = {
            hasError: false,
            error: null,
            errorInfo: null,
        };
    }

    /**
     * Update state when an error is caught
     * This is called during the render phase
     */
    static getDerivedStateFromError(error: Error): Partial<State> {
        return {
            hasError: true,
            error,
        };
    }

    /**
     * Log error details for debugging and analytics
     * This is called during the commit phase (after render)
     */
    componentDidCatch(error: Error, errorInfo: ErrorInfo): void {
        this.setState({
            errorInfo,
        });

        // Log to console in development
        if (import.meta.env.DEV) {
            console.group('🚨 Error Boundary Caught Error');
            console.error('Error:', error);
            console.error('Error Info:', errorInfo);
            console.error('Component Stack:', errorInfo.componentStack);
            console.groupEnd();
        }

        // TODO: Send to error tracking service in production
        // Example: Sentry.captureException(error, { contexts: { react: errorInfo } });
    }

    /**
     * Reload the page to recover from error
     */
    handleReload = (): void => {
        window.location.reload();
    };

    /**
     * Reset error state and try to recover
     */
    handleReset = (): void => {
        this.setState({
            hasError: false,
            error: null,
            errorInfo: null,
        });
    };

    render(): ReactNode {
        const { hasError, error, errorInfo } = this.state;
        const { children, fallback } = this.props;

        if (hasError) {
            // Use custom fallback if provided
            if (fallback) {
                return fallback;
            }

            // Default error UI
            return (
                <div className="flex min-h-screen items-center justify-center bg-muted/30 p-4">
                    <Card className="w-full max-w-lg shadow-lg">
                        <CardHeader>
                            <div className="flex items-center gap-3">
                                <div className="flex size-12 items-center justify-center rounded-full bg-destructive/10">
                                    <AlertCircleIcon className="size-6 text-destructive" />
                                </div>
                                <div>
                                    <CardTitle className="text-2xl">
                                        Algo salió mal
                                    </CardTitle>
                                    <CardDescription>
                                        Ocurrió un error inesperado
                                    </CardDescription>
                                </div>
                            </div>
                        </CardHeader>

                        <CardContent className="space-y-4">
                            <Alert variant="destructive">
                                <AlertCircleIcon className="size-4" />
                                <AlertDescription>
                                    Lo sentimos, la aplicación encontró un error
                                    y no puede continuar. Por favor, recarga la
                                    página para intentar nuevamente.
                                </AlertDescription>
                            </Alert>

                            {/* Error Details (Collapsible) */}
                            {error && import.meta.env.DEV && (
                                <details className="rounded-lg border bg-muted/50 p-4">
                                    <summary className="cursor-pointer font-medium text-sm text-muted-foreground hover:text-foreground">
                                        Detalles del error (desarrollo)
                                    </summary>
                                    <div className="mt-4 space-y-2">
                                        <div>
                                            <p className="font-semibold text-sm">
                                                Error:
                                            </p>
                                            <pre className="mt-1 overflow-auto rounded bg-muted p-2 text-xs">
                                                {error.message}
                                            </pre>
                                        </div>

                                        {error.stack && (
                                            <div>
                                                <p className="font-semibold text-sm">
                                                    Stack Trace:
                                                </p>
                                                <pre className="mt-1 max-h-48 overflow-auto rounded bg-muted p-2 text-xs">
                                                    {error.stack}
                                                </pre>
                                            </div>
                                        )}

                                        {errorInfo?.componentStack && (
                                            <div>
                                                <p className="font-semibold text-sm">
                                                    Component Stack:
                                                </p>
                                                <pre className="mt-1 max-h-48 overflow-auto rounded bg-muted p-2 text-xs">
                                                    {errorInfo.componentStack}
                                                </pre>
                                            </div>
                                        )}
                                    </div>
                                </details>
                            )}

                            {/* Production Error Message */}
                            {!import.meta.env.DEV && (
                                <p className="text-center text-muted-foreground text-sm">
                                    Si el problema persiste, contacta al equipo
                                    de soporte técnico.
                                </p>
                            )}
                        </CardContent>

                        <CardFooter className="flex gap-3">
                            {import.meta.env.DEV && (
                                <Button
                                    variant="outline"
                                    onClick={this.handleReset}
                                    className="flex-1"
                                >
                                    Intentar Recuperar
                                </Button>
                            )}
                            <Button
                                onClick={this.handleReload}
                                className="flex-1"
                            >
                                <RefreshCwIcon className="mr-2 size-4" />
                                Recargar Página
                            </Button>
                        </CardFooter>
                    </Card>
                </div>
            );
        }

        return children;
    }
}
