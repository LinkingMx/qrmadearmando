/**
 * Hook for offline-capable QR code scanning and transaction processing
 * Queues debits for sync when offline
 */

import { useCallback, useState, useRef } from 'react'
import {
  initDB,
  GiftCard,
  Transaction,
  OfflineAction,
  queueOfflineAction,
  removeOfflineAction,
  getPendingActions,
} from '@/lib/db'

export interface ScanResult {
  legacy_id: string
  amount: number
  description?: string
}

export interface ScanTransaction {
  id: string
  gift_card_id: string
  type: 'debit'
  amount: number
  balance_before: number
  balance_after: number
  created_at: number
  synced: boolean
  offline_id?: string
}

export interface UseScannerOfflineReturn {
  scan: (legacy_id: string) => Promise<GiftCard | null>
  processDebit: (
    legacy_id: string,
    amount: number,
    description?: string
  ) => Promise<ScanTransaction | null>
  getSyncQueue: () => Promise<OfflineAction[]>
  syncPendingTransactions: () => Promise<void>
  isProcessing: boolean
  error: string | null
  lastScannedCard: GiftCard | null
}

/**
 * Process a debit transaction (online or queued offline)
 */
async function processDebitOnline(
  legacy_id: string,
  amount: number,
  description?: string
): Promise<ScanTransaction> {
  const response = await fetch('/api/v1/debit', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      Accept: 'application/json',
    },
    body: JSON.stringify({
      legacy_id,
      amount,
      description,
    }),
  })

  if (!response.ok) {
    throw new Error(`Failed to process debit: ${response.statusText}`)
  }

  const data = await response.json()
  return data.data
}

/**
 * Sync pending transactions to server
 */
async function syncTransactionsToAPI(
  actions: OfflineAction[]
): Promise<void> {
  for (const action of actions) {
    try {
      const response = await fetch('/api/v1/sync/transactions', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          Accept: 'application/json',
        },
        body: JSON.stringify({
          offline_id: action.id,
          ...action.payload,
        }),
      })

      if (response.ok) {
        // Remove from queue after successful sync
        await removeOfflineAction(action.id)
      } else {
        // Update error info if sync failed
        await updateOfflineActionError(
          action.id,
          `Server error: ${response.statusText}`
        )
      }
    } catch (err) {
      const errorMsg =
        err instanceof Error ? err.message : 'Sync failed'
      await updateOfflineActionError(action.id, errorMsg)
    }
  }
}

/**
 * Update offline action error
 */
async function updateOfflineActionError(
  id: string,
  error: string
): Promise<void> {
  const db = await initDB()
  const action = await db.get('offline_queue', id)

  if (action) {
    await db.put('offline_queue', {
      ...action,
      retry_count: action.retry_count + 1,
      last_error: error,
    })
  }
}

/**
 * Hook for QR scanning with offline debit support
 */
export function useScannerOffline(): UseScannerOfflineReturn {
  const [isProcessing, setIsProcessing] = useState(false)
  const [error, setError] = useState<string | null>(null)
  const [lastScannedCard, setLastScannedCard] = useState<GiftCard | null>(null)
  const maxRetriesRef = useRef(3)

  /**
   * Scan a QR code (lookup gift card)
   */
  const scan = useCallback(async (legacy_id: string) => {
    try {
      setError(null)
      const db = await initDB()

      // Look up by legacy_id index
      const index = await db.getAllFromIndex(
        'gift_cards',
        'by-legacy-id',
        legacy_id
      )

      if (index.length > 0) {
        const card = index[0]
        setLastScannedCard(card)
        return card
      }

      // If not in cache and online, fetch from API
      if (navigator.onLine) {
        const response = await fetch(
          `/api/v1/gift-cards/search?legacy_id=${legacy_id}`,
          {
            headers: {
              'Content-Type': 'application/json',
              Accept: 'application/json',
            },
          }
        )

        if (response.ok) {
          const data = await response.json()
          const card = data.data

          // Cache the result
          if (card) {
            await db.put('gift_cards', {
              ...card,
              cached_at: Date.now(),
            })
            setLastScannedCard(card)
            return card
          }
        }
      }

      setError('Gift card not found')
      return null
    } catch (err) {
      const errorMsg = err instanceof Error ? err.message : 'Scan failed'
      setError(errorMsg)
      return null
    }
  }, [])

  /**
   * Process debit (online or queue offline)
   */
  const processDebit = useCallback(
    async (
      legacy_id: string,
      amount: number,
      description?: string
    ): Promise<ScanTransaction | null> => {
      try {
        setError(null)
        setIsProcessing(true)

        // First, get the gift card
        const card = await scan(legacy_id)
        if (!card) {
          throw new Error('Gift card not found')
        }

        // Check if we have sufficient balance
        if (card.balance < amount) {
          throw new Error(
            `Insufficient balance. Available: $${card.balance.toFixed(2)}`
          )
        }

        // Try to process online
        if (navigator.onLine) {
          try {
            const transaction = await processDebitOnline(
              legacy_id,
              amount,
              description
            )

            // Update local cache with new balance
            const db = await initDB()
            await db.put('gift_cards', {
              ...card,
              balance: transaction.balance_after,
              updated_at: Date.now(),
              cached_at: Date.now(),
            })

            setLastScannedCard({
              ...card,
              balance: transaction.balance_after,
            })

            return transaction
          } catch (err) {
            // If online request fails, try queuing for offline sync
            if (navigator.onLine) {
              throw err
            }
            // Fall through to offline processing
          }
        }

        // Process offline - queue for sync
        const db = await initDB()
        const newBalance = card.balance - amount

        // Create offline transaction record
        const offlineTransaction: Transaction = {
          id: crypto.randomUUID(),
          gift_card_id: card.id,
          type: 'debit',
          amount,
          balance_before: card.balance,
          balance_after: newBalance,
          description: description || 'Offline debit',
          created_at: Date.now(),
          synced: false,
          offline_id: crypto.randomUUID(),
        }

        // Save to local transactions
        await db.add('transactions', offlineTransaction)

        // Queue for sync
        await queueOfflineAction({
          action_type: 'debit',
          payload: {
            legacy_id,
            amount,
            description,
            offline_id: offlineTransaction.offline_id,
          },
          created_at: Date.now(),
          retry_count: 0,
          last_error: null,
        })

        // Update local gift card balance
        await db.put('gift_cards', {
          ...card,
          balance: newBalance,
          updated_at: Date.now(),
          is_dirty: true, // Mark for sync
        })

        setLastScannedCard({
          ...card,
          balance: newBalance,
        })

        return offlineTransaction
      } catch (err) {
        const errorMsg = err instanceof Error ? err.message : 'Debit failed'
        setError(errorMsg)
        return null
      } finally {
        setIsProcessing(false)
      }
    },
    [scan]
  )

  /**
   * Get pending transactions in sync queue
   */
  const getSyncQueue = useCallback(async () => {
    try {
      return await getPendingActions()
    } catch (err) {
      console.error('Failed to get sync queue:', err)
      return []
    }
  }, [])

  /**
   * Sync pending transactions to server
   */
  const syncPendingTransactions = useCallback(async () => {
    try {
      setIsProcessing(true)
      setError(null)

      if (!navigator.onLine) {
        setError('No internet connection')
        return
      }

      const pendingActions = await getPendingActions()

      if (pendingActions.length === 0) {
        return
      }

      // Sync all pending actions
      await syncTransactionsToAPI(pendingActions)

      // Refresh gift cards from API
      const response = await fetch('/api/v1/gift-cards', {
        headers: {
          'Content-Type': 'application/json',
          Accept: 'application/json',
        },
      })

      if (response.ok) {
        const data = await response.json()
        const db = await initDB()

        for (const card of data.data || []) {
          await db.put('gift_cards', {
            ...card,
            cached_at: Date.now(),
            is_dirty: false,
          })
        }
      }
    } catch (err) {
      const errorMsg =
        err instanceof Error ? err.message : 'Sync failed'
      setError(errorMsg)
      throw err
    } finally {
      setIsProcessing(false)
    }
  }, [])

  return {
    scan,
    processDebit,
    getSyncQueue,
    syncPendingTransactions,
    isProcessing,
    error,
    lastScannedCard,
  }
}

/**
 * Hook for manual sync control
 */
export function useSyncManager() {
  const [isSyncing, setIsSyncing] = useState(false)
  const [error, setError] = useState<string | null>(null)
  const [lastSyncTime, setLastSyncTime] = useState<number | null>(null)

  const syncPending = useCallback(async () => {
    try {
      setIsSyncing(true)
      setError(null)

      if (!navigator.onLine) {
        throw new Error('No internet connection')
      }

      const pendingActions = await getPendingActions()

      if (pendingActions.length > 0) {
        await syncTransactionsToAPI(pendingActions)
      }

      setLastSyncTime(Date.now())
    } catch (err) {
      const errorMsg =
        err instanceof Error ? err.message : 'Sync failed'
      setError(errorMsg)
      throw err
    } finally {
      setIsSyncing(false)
    }
  }, [])

  return {
    syncPending,
    isSyncing,
    error,
    lastSyncTime,
  }
}
