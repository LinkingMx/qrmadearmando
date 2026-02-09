/**
 * Offline authentication guard component
 * Ensures user is authenticated (or in guest mode) before accessing protected content
 */

import { ReactNode } from 'react'
import { useOfflineSession } from '@/hooks/use-offline-session'
import { OfflineLoginForm } from './offline-login-form'
import { Spinner } from '@/components/ui/spinner'

export interface OfflineAuthGuardProps {
  children: ReactNode
  requireAuth?: boolean
  showGuestOption?: boolean
  fallback?: ReactNode
}

/**
 * Guard component that shows login form if user is not authenticated
 */
export function OfflineAuthGuard({
  children,
  requireAuth = false,
  showGuestOption = true,
  fallback,
}: OfflineAuthGuardProps) {
  const { session, isLoading } = useOfflineSession()

  // Still loading session
  if (isLoading) {
    return (
      fallback || (
        <div className="flex min-h-screen items-center justify-center">
          <Spinner className="h-8 w-8" />
        </div>
      )
    )
  }

  // Check authentication requirement
  const isAuthenticated = session?.mode === 'authenticated'
  const hasSession = session && (isAuthenticated || session.mode === 'guest')

  // If no session and auth is required, show login
  if (!hasSession) {
    return (
      <div className="flex min-h-screen items-center justify-center bg-gradient-to-b from-slate-50 to-slate-100 p-4 dark:from-slate-950 dark:to-slate-900">
        <div className="w-full max-w-md space-y-6">
          <div className="text-center space-y-2">
            <h1 className="text-3xl font-bold">QR Made</h1>
            <p className="text-muted-foreground">Sistema de Gift Cards</p>
          </div>

          <OfflineLoginForm
            showGuestOption={showGuestOption}
            onSuccess={() => {
              // Trigger re-render by refreshing the page
              window.location.reload()
            }}
          />
        </div>
      </div>
    )
  }

  // User has a session (authenticated or guest)
  return <>{children}</>
}

/**
 * Component that shows content only if authenticated (not guest mode)
 */
export function RequireAuthenticated({
  children,
  fallback,
}: {
  children: ReactNode
  fallback?: ReactNode
}) {
  const { session, isLoading } = useOfflineSession()

  if (isLoading) {
    return fallback || null
  }

  if (session?.mode !== 'authenticated') {
    return fallback || null
  }

  return <>{children}</>
}

/**
 * Component that shows content only in guest mode
 */
export function RequireGuest({
  children,
  fallback,
}: {
  children: ReactNode
  fallback?: ReactNode
}) {
  const { session, isLoading } = useOfflineSession()

  if (isLoading) {
    return fallback || null
  }

  if (session?.mode !== 'guest') {
    return fallback || null
  }

  return <>{children}</>
}
