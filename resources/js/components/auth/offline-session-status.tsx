/**
 * Offline session status component for header/sidebar
 * Shows current session info and allows logout
 */

import { useState } from 'react'
import { useOfflineSession } from '@/hooks/use-offline-session'
import { Button } from '@/components/ui/button'
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuLabel,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu'
import { Avatar, AvatarFallback } from '@/components/ui/avatar'
import { LogOutIcon, LockIcon } from 'lucide-react'

export interface OfflineSessionStatusProps {
  compact?: boolean
}

export function OfflineSessionStatus({ compact = false }: OfflineSessionStatusProps) {
  const { session, logout, hasEncryptedPassword } = useOfflineSession()
  const [hasPassword, setHasPassword] = useState(false)
  const [isLoading, setIsLoading] = useState(true)

  // Check if password is saved
  if (isLoading && session) {
    hasEncryptedPassword().then((has) => {
      setHasPassword(has)
      setIsLoading(false)
    })
  }

  if (!session) {
    return null
  }

  const handleLogout = async () => {
    try {
      await logout()
      window.location.reload()
    } catch (err) {
      console.error('Failed to logout:', err)
    }
  }

  if (compact) {
    return (
      <DropdownMenu>
        <DropdownMenuTrigger asChild>
          <Button variant="ghost" size="sm" className="h-8 w-8 rounded-full p-0">
            <Avatar className="h-8 w-8">
              <AvatarFallback className="text-xs">
                {session.mode === 'guest' ? 'G' : session.user_id.charAt(0).toUpperCase()}
              </AvatarFallback>
            </Avatar>
          </Button>
        </DropdownMenuTrigger>

        <DropdownMenuContent align="end">
          <DropdownMenuLabel>
            <div className="space-y-1">
              <p className="text-sm font-medium">
                {session.mode === 'guest' ? 'Modo Invitado' : session.user_id}
              </p>
              <p className="text-xs text-muted-foreground">
                {session.mode === 'guest' ? 'Sin autenticar' : 'Autenticado'}
              </p>
            </div>
          </DropdownMenuLabel>

          <DropdownMenuSeparator />

          {hasPassword && (
            <DropdownMenuItem disabled className="text-xs text-muted-foreground">
              <LockIcon className="mr-2 h-3 w-3" />
              Contraseña guardada
            </DropdownMenuItem>
          )}

          <DropdownMenuItem onClick={handleLogout} className="text-destructive">
            <LogOutIcon className="mr-2 h-4 w-4" />
            Cerrar sesión
          </DropdownMenuItem>
        </DropdownMenuContent>
      </DropdownMenu>
    )
  }

  // Full size component
  return (
    <div className="rounded-lg border border-slate-200 bg-slate-50 p-4 dark:border-slate-800 dark:bg-slate-900">
      <div className="space-y-3">
        <div className="flex items-center justify-between">
          <div className="flex items-center gap-3">
            <Avatar>
              <AvatarFallback>
                {session.mode === 'guest' ? 'G' : session.user_id.charAt(0).toUpperCase()}
              </AvatarFallback>
            </Avatar>

            <div>
              <p className="text-sm font-medium">
                {session.mode === 'guest' ? 'Modo Invitado' : session.user_id}
              </p>
              <p className="text-xs text-muted-foreground">
                {session.mode === 'guest' ? 'Sin autenticar' : 'Autenticado'}
              </p>
            </div>
          </div>
        </div>

        {hasPassword && (
          <div className="flex items-center gap-2 rounded bg-slate-100 p-2 dark:bg-slate-800">
            <LockIcon className="h-3 w-3 text-amber-600 dark:text-amber-500" />
            <span className="text-xs text-amber-700 dark:text-amber-400">
              Contraseña guardada localmente
            </span>
          </div>
        )}

        <Button
          onClick={handleLogout}
          variant="destructive"
          size="sm"
          className="w-full"
        >
          <LogOutIcon className="mr-2 h-4 w-4" />
          Cerrar sesión
        </Button>
      </div>
    </div>
  )
}
