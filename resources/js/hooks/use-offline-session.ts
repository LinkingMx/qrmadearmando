/**
 * Hook for managing offline-first persistent sessions
 * Stores user_id and encrypted password for 30 days
 * No network required for session persistence
 */

import { useEffect, useState, useCallback } from 'react'
import { initDB, SessionData } from '@/lib/db'
import {
  encryptPassword,
  decryptPassword,
  hashPassword,
  EncryptedData,
} from '@/lib/crypto'

export interface OfflineSession {
  user_id: string
  email: string
  mode: 'guest' | 'authenticated'
  is_active: boolean
}

export interface UseOfflineSessionReturn {
  session: OfflineSession | null
  isLoading: boolean
  error: string | null
  login: (
    user_id: string,
    email: string,
    password: string,
    savePassword: boolean
  ) => Promise<void>
  logout: () => Promise<void>
  validatePassword: (password: string) => Promise<boolean>
  hasEncryptedPassword: () => Promise<boolean>
}

/**
 * Hook for managing offline sessions with optional password persistence
 * @returns Session state and methods
 */
export function useOfflineSession(): UseOfflineSessionReturn {
  const [session, setSession] = useState<OfflineSession | null>(null)
  const [isLoading, setIsLoading] = useState(true)
  const [error, setError] = useState<string | null>(null)

  /**
   * Load session from IndexedDB on mount
   */
  useEffect(() => {
    const loadSession = async () => {
      try {
        const db = await initDB()
        const dbSession = await db.get('session', 'current_session')

        if (!dbSession) {
          setSession(null)
          setIsLoading(false)
          return
        }

        // Check if session has expired
        if (dbSession.expires_at < Date.now()) {
          await db.delete('session', 'current_session')
          setSession(null)
          setIsLoading(false)
          return
        }

        // Restore session
        setSession({
          user_id: dbSession.user_id || '',
          email: '', // Not stored in session to avoid exposing PII
          mode: dbSession.mode,
          is_active: true,
        })
        setError(null)
      } catch (err) {
        setError(
          err instanceof Error ? err.message : 'Failed to load session'
        )
        setSession(null)
      } finally {
        setIsLoading(false)
      }
    }

    loadSession()
  }, [])

  /**
   * Login and optionally save encrypted password
   */
  const login = useCallback(
    async (
      user_id: string,
      email: string,
      password: string,
      savePassword: boolean
    ) => {
      try {
        setError(null)
        const db = await initDB()

        let encrypted_password: string | null = null
        let encryption_key: string | null = null

        // Encrypt password if save requested
        if (savePassword) {
          const encrypted = await encryptPassword(password)
          encrypted_password = JSON.stringify(encrypted)

          // Store encryption key (hash of password for verification)
          encryption_key = await hashPassword(password)
        }

        // Store session in IndexedDB
        const sessionData: SessionData = {
          id: 'current_session',
          user_id: user_id,
          mode: 'authenticated',
          encrypted_password: encrypted_password,
          encryption_key: encryption_key,
          login_timestamp: Date.now(),
          expires_at: Date.now() + 30 * 24 * 60 * 60 * 1000, // 30 days
          token: null, // For future API token storage
        }

        await db.put('session', sessionData)

        // Update local state
        setSession({
          user_id,
          email,
          mode: 'authenticated',
          is_active: true,
        })
      } catch (err) {
        const errorMsg =
          err instanceof Error ? err.message : 'Failed to save session'
        setError(errorMsg)
        throw err
      }
    },
    []
  )

  /**
   * Logout and clear session
   */
  const logout = useCallback(async () => {
    try {
      setError(null)
      const db = await initDB()
      await db.delete('session', 'current_session')
      setSession(null)
    } catch (err) {
      const errorMsg =
        err instanceof Error ? err.message : 'Failed to logout'
      setError(errorMsg)
      throw err
    }
  }, [])

  /**
   * Validate password against stored encrypted version
   */
  const validatePassword = useCallback(async (password: string) => {
    try {
      const db = await initDB()
      const dbSession = await db.get('session', 'current_session')

      if (!dbSession || !dbSession.encrypted_password) {
        return false
      }

      // Check if hash matches
      const providedHash = await hashPassword(password)
      if (providedHash !== dbSession.encryption_key) {
        return false
      }

      // Optional: Also verify decryption works
      const encryptedData: EncryptedData = JSON.parse(
        dbSession.encrypted_password
      )
      const decrypted = await decryptPassword(password, encryptedData)
      return decrypted === password
    } catch {
      return false
    }
  }, [])

  /**
   * Check if an encrypted password is stored
   */
  const hasEncryptedPassword = useCallback(async () => {
    try {
      const db = await initDB()
      const dbSession = await db.get('session', 'current_session')
      return !!(dbSession && dbSession.encrypted_password)
    } catch {
      return false
    }
  }, [])

  return {
    session,
    isLoading,
    error,
    login,
    logout,
    validatePassword,
    hasEncryptedPassword,
  }
}

/**
 * Hook for guest mode (no authentication)
 * Stores minimal session state without password
 */
export function useGuestSession(): UseOfflineSessionReturn {
  const [session, setSession] = useState<OfflineSession | null>(null)
  const [isLoading, setIsLoading] = useState(true)
  const [error, setError] = useState<string | null>(null)

  useEffect(() => {
    const loadSession = async () => {
      try {
        const db = await initDB()
        const dbSession = await db.get('session', 'current_session')

        if (!dbSession) {
          setSession({
            user_id: '',
            email: '',
            mode: 'guest',
            is_active: true,
          })
          setIsLoading(false)
          return
        }

        // Check if session expired
        if (dbSession.expires_at < Date.now()) {
          await db.delete('session', 'current_session')
        }

        // In guest mode, always show guest state
        setSession({
          user_id: '',
          email: '',
          mode: 'guest',
          is_active: true,
        })
      } catch (err) {
        setError(
          err instanceof Error ? err.message : 'Failed to load session'
        )
      } finally {
        setIsLoading(false)
      }
    }

    loadSession()
  }, [])

  const login = useCallback(
    async (user_id: string, email: string, password: string) => {
      try {
        const db = await initDB()

        // Store guest session (no password)
        const sessionData: SessionData = {
          id: 'current_session',
          user_id: '', // Empty in guest mode
          mode: 'guest',
          encrypted_password: null,
          encryption_key: null,
          login_timestamp: Date.now(),
          expires_at: Date.now() + 30 * 24 * 60 * 60 * 1000, // 30 days
          token: null,
        }

        await db.put('session', sessionData)

        setSession({
          user_id: '',
          email: '',
          mode: 'guest',
          is_active: true,
        })
      } catch (err) {
        const errorMsg =
          err instanceof Error ? err.message : 'Failed to save session'
        setError(errorMsg)
        throw err
      }
    },
    []
  )

  const logout = useCallback(async () => {
    try {
      const db = await initDB()
      await db.delete('session', 'current_session')
      setSession({
        user_id: '',
        email: '',
        mode: 'guest',
        is_active: true,
      })
    } catch (err) {
      const errorMsg =
        err instanceof Error ? err.message : 'Failed to logout'
      setError(errorMsg)
      throw err
    }
  }, [])

  return {
    session,
    isLoading,
    error,
    login,
    logout,
    validatePassword: async () => false,
    hasEncryptedPassword: async () => false,
  }
}
