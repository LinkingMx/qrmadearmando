/**
 * API Response Types - Unified format across all endpoints
 * All API responses follow the format: { data: T, meta?: {...}, error?: {...} }
 */

import { GiftCard, Transaction } from './scanner';

/**
 * Pagination metadata included in list responses
 */
export interface PaginationMeta {
  current_page: number;
  per_page: number;
  total: number;
  last_page: number;
  from: number | null;
  to: number | null;
}

/**
 * Response metadata container
 */
export interface ResponseMeta {
  pagination?: PaginationMeta;
  timestamp?: string;
}

/**
 * Generic API response wrapper
 * All endpoints return data wrapped in a { data: T } structure
 */
export interface ApiResponse<T = unknown> {
  data: T;
  meta?: ResponseMeta;
  error?: {
    code: string;
    message: string;
    details?: Record<string, any>;
  };
}

/**
 * Error response format
 */
export interface ErrorResponse {
  error: {
    code: string;
    message: string;
    details?: Record<string, any>;
  };
}

/**
 * Specialized response types for common domains
 */

export type GiftCardResponse = ApiResponse<GiftCard>;

export type GiftCardListResponse = ApiResponse<GiftCard[]>;

export type TransactionResponse = ApiResponse<Transaction>;

export type TransactionListResponse = ApiResponse<Transaction[]>;

export interface GiftCardCategory {
  id: string;
  name: string;
  prefix: string;
  nature: 'payment_method' | 'discount';
}

export type CategoryListResponse = ApiResponse<GiftCardCategory[]>;

/**
 * Fallback helper to support both old and new response formats during transition
 * Handles responses that may have varying structures
 */
export function extractResponseData<T>(
  response: any,
  fallbackPath?: string
): T | null {
  // Try new format first: { data: T }
  if (response && 'data' in response && typeof response.data === 'object') {
    if (Array.isArray(response.data) || typeof response.data !== 'object' || !('gift_card' in response.data)) {
      return response.data;
    }
  }

  // Try old format with fallbackPath (e.g., 'gift_card' or 'transaction')
  if (fallbackPath && response && fallbackPath in response) {
    return response[fallbackPath];
  }

  return null;
}
