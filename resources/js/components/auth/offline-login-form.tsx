/**
 * Login form with offline session and optional password persistence
 */

import { useState } from 'react'
import { useForm } from '@inertiajs/react'
import { Button } from '@/components/ui/button'
import {
  Form,
  FormControl,
  FormField,
  FormItem,
  FormLabel,
  FormMessage,
} from '@/components/ui/form'
import { Input } from '@/components/ui/input'
import { Checkbox } from '@/components/ui/checkbox'
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert'
import { useOfflineSession } from '@/hooks/use-offline-session'
import { AlertCircleIcon, CheckCircleIcon, EyeIcon, EyeOffIcon } from 'lucide-react'
import { useEffect } from 'react'

export interface OfflineLoginFormProps {
  onSuccess?: () => void
  showGuestOption?: boolean
}

export function OfflineLoginForm({
  onSuccess,
  showGuestOption = true,
}: OfflineLoginFormProps) {
  const { login, logout, session, error: sessionError } = useOfflineSession()
  const [showPassword, setShowPassword] = useState(false)
  const [savePassword, setSavePassword] = useState(false)
  const [isSubmitting, setIsSubmitting] = useState(false)
  const [successMessage, setSuccessMessage] = useState<string | null>(null)

  const form = useForm({
    email: '',
    password: '',
  })

  // Clear authenticated session first
  useEffect(() => {
    if (session?.mode === 'authenticated') {
      logout()
    }
  }, [])

  const onSubmit = form.transform(({ email, password }) => ({ email, password })).post('/login', {
    onSuccess: async () => {
      setIsSubmitting(true)
      try {
        // Get authenticated user data from server response
        // In a real app, this would come from the response
        await login(email, email, password, savePassword)

        setSuccessMessage('¡Sesión iniciada exitosamente!')
        setTimeout(() => {
          setSuccessMessage(null)
          onSuccess?.()
        }, 1500)
      } catch (err) {
        // Error handled silently
      } finally {
        setIsSubmitting(false)
      }
    },
    onError: () => {
      // Form errors are handled below
    },
  })

  const handleGuestMode = async () => {
    setIsSubmitting(true)
    try {
      // In guest mode, we use a dummy user_id
      await login('guest', 'guest@offline.local', '', false)
      setSuccessMessage('Modo invitado iniciado')
      setTimeout(() => {
        setSuccessMessage(null)
        onSuccess?.()
      }, 1500)
    } catch (err) {
      // Error handled silently
    } finally {
      setIsSubmitting(false)
    }
  }

  return (
    <div className="w-full max-w-md space-y-4">
      {/* Success Message */}
      {successMessage && (
        <Alert className="border-green-200 bg-green-50 dark:border-green-900 dark:bg-green-950">
          <CheckCircleIcon className="h-4 w-4 text-green-600 dark:text-green-500" />
          <AlertTitle className="text-green-800 dark:text-green-200">
            Éxito
          </AlertTitle>
          <AlertDescription className="text-green-700 dark:text-green-300">
            {successMessage}
          </AlertDescription>
        </Alert>
      )}

      {/* Session or Form Error */}
      {(sessionError || form.errors.email) && (
        <Alert variant="destructive">
          <AlertCircleIcon className="h-4 w-4" />
          <AlertTitle>Error</AlertTitle>
          <AlertDescription>
            {sessionError || form.errors.email}
          </AlertDescription>
        </Alert>
      )}

      {/* Login Form */}
      <form onSubmit={form.submit} className="space-y-4">
        <FormField
          key="email"
          name="email"
          render={() => (
            <FormItem>
              <FormLabel>Email</FormLabel>
              <FormControl>
                <Input
                  type="email"
                  placeholder="usuario@ejemplo.com"
                  value={form.data.email}
                  onChange={(e) => form.setData('email', e.target.value)}
                  disabled={isSubmitting}
                  className="dark:bg-slate-900"
                />
              </FormControl>
              <FormMessage>{form.errors.email}</FormMessage>
            </FormItem>
          )}
        />

        <FormField
          key="password"
          name="password"
          render={() => (
            <FormItem>
              <FormLabel>Contraseña</FormLabel>
              <FormControl>
                <div className="relative">
                  <Input
                    type={showPassword ? 'text' : 'password'}
                    placeholder="••••••••"
                    value={form.data.password}
                    onChange={(e) => form.setData('password', e.target.value)}
                    disabled={isSubmitting}
                    className="pr-10 dark:bg-slate-900"
                  />
                  <button
                    type="button"
                    onClick={() => setShowPassword(!showPassword)}
                    className="absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-foreground"
                    tabIndex={-1}
                  >
                    {showPassword ? (
                      <EyeOffIcon className="h-4 w-4" />
                    ) : (
                      <EyeIcon className="h-4 w-4" />
                    )}
                  </button>
                </div>
              </FormControl>
              <FormMessage>{form.errors.password}</FormMessage>
            </FormItem>
          )}
        />

        {/* Save Password Option */}
        <div className="flex items-center space-x-2">
          <Checkbox
            id="save-password"
            checked={savePassword}
            onCheckedChange={(checked) => setSavePassword(checked as boolean)}
            disabled={isSubmitting}
          />
          <label
            htmlFor="save-password"
            className="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70"
          >
            Guardar contraseña (30 días)
          </label>
        </div>

        {/* Helper Text */}
        <p className="text-xs text-muted-foreground">
          ✓ Si guardas tu contraseña, podrás acceder sin internet durante 30 días.
          La contraseña se encripta de forma segura en tu dispositivo.
        </p>

        {/* Submit Button */}
        <Button
          type="submit"
          disabled={isSubmitting || form.processing}
          className="w-full"
        >
          {isSubmitting || form.processing ? 'Iniciando sesión...' : 'Iniciar sesión'}
        </Button>
      </form>

      {/* Divider */}
      {showGuestOption && (
        <>
          <div className="relative">
            <div className="absolute inset-0 flex items-center">
              <div className="w-full border-t border-muted" />
            </div>
            <div className="relative flex justify-center text-sm">
              <span className="bg-background px-2 text-muted-foreground">o</span>
            </div>
          </div>

          {/* Guest Mode Button */}
          <Button
            type="button"
            variant="outline"
            className="w-full"
            onClick={handleGuestMode}
            disabled={isSubmitting}
          >
            Modo invitado (Sin login)
          </Button>

          <p className="text-xs text-center text-muted-foreground">
            Escanea códigos QR sin necesidad de iniciar sesión.
            Los cambios se guardarán localmente.
          </p>
        </>
      )}
    </div>
  )
}
