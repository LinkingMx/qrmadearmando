/**
 * Offline status indicator component
 * Shows connection status and pending sync count
 */

import { useEffect, useState } from 'react'
import { Badge } from '@/components/ui/badge'
import { AlertCircle, Wifi, WifiOff } from 'lucide-react'
import { getPendingActions } from '@/lib/db'

export interface OfflineStatusIndicatorProps {
  showPendingCount?: boolean
  compact?: boolean
}

export function OfflineStatusIndicator({
  showPendingCount = true,
  compact = false,
}: OfflineStatusIndicatorProps) {
  const [isOnline, setIsOnline] = useState(navigator.onLine)
  const [pendingCount, setPendingCount] = useState(0)

  useEffect(() => {
    const handleOnline = () => setIsOnline(true)
    const handleOffline = () => setIsOnline(false)

    window.addEventListener('online', handleOnline)
    window.addEventListener('offline', handleOffline)

    return () => {
      window.removeEventListener('online', handleOnline)
      window.removeEventListener('offline', handleOffline)
    }
  }, [])

  useEffect(() => {
    if (!showPendingCount) return

    const checkPending = async () => {
      try {
        const actions = await getPendingActions()
        setPendingCount(actions.length)
      } catch (err) {
        // Error handled silently
      }
    }

    checkPending()

    // Check every 5 seconds
    const interval = setInterval(checkPending, 5000)

    return () => clearInterval(interval)
  }, [showPendingCount])

  if (isOnline && (!showPendingCount || pendingCount === 0)) {
    return null // Don't show when online and no pending items
  }

  if (compact) {
    return (
      <div className="flex items-center gap-1.5">
        {!isOnline ? (
          <WifiOff className="h-4 w-4 text-amber-600 dark:text-amber-500" />
        ) : (
          <AlertCircle className="h-4 w-4 text-orange-600 dark:text-orange-500" />
        )}
        {!isOnline && <span className="text-xs text-amber-700 dark:text-amber-400">Sin conexión</span>}
        {isOnline && pendingCount > 0 && (
          <span className="text-xs text-orange-700 dark:text-orange-400">
            {pendingCount} pendiente{pendingCount !== 1 ? 's' : ''}
          </span>
        )}
      </div>
    )
  }

  return (
    <div className="flex items-center gap-2 rounded-lg border border-amber-200 bg-amber-50 p-3 dark:border-amber-900 dark:bg-amber-950">
      <div className="flex items-center gap-2">
        {!isOnline ? (
          <WifiOff className="h-5 w-5 text-amber-600 dark:text-amber-500" />
        ) : (
          <AlertCircle className="h-5 w-5 text-orange-600 dark:text-orange-500" />
        )}

        <div>
          <p className="text-sm font-medium text-amber-900 dark:text-amber-100">
            {!isOnline ? 'Sin conexión a internet' : 'Sincronización pendiente'}
          </p>
          <p className="text-xs text-amber-700 dark:text-amber-400">
            {!isOnline
              ? 'Los cambios se guardarán localmente'
              : pendingCount > 0
                ? `${pendingCount} operación${pendingCount !== 1 ? 'es' : ''} pendiente${pendingCount !== 1 ? 's' : ''} de sincronizar`
                : 'Esperando conexión...'}
          </p>
        </div>
      </div>

      {!isOnline && (
        <Badge variant="secondary" className="ml-auto">
          Offline
        </Badge>
      )}
      {isOnline && pendingCount > 0 && (
        <Badge variant="destructive" className="ml-auto">
          {pendingCount}
        </Badge>
      )}
    </div>
  )
}
