<?php

namespace App\Http\Traits;

use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * API Response Trait
 *
 * Provides consistent response formatting for all API endpoints
 * Unified format: { data, meta?, error? }
 */
trait ApiResponse
{
    /**
     * Return a success response
     *
     * @param  mixed  $data  Response data (object or array)
     * @param  array|null  $meta  Optional metadata (pagination, timestamps, etc.)
     * @param  int  $statusCode  HTTP status code (default: 200)
     */
    protected function success(
        mixed $data = null,
        ?array $meta = null,
        int $statusCode = 200
    ): JsonResponse {
        $response = [];

        if ($data !== null) {
            $response['data'] = $data;
        }

        if ($meta !== null) {
            $response['meta'] = $meta;
        }

        return response()->json($response, $statusCode);
    }

    /**
     * Return a paginated response
     *
     * @param  LengthAwarePaginator  $paginator  Laravel paginator instance
     * @param  int  $statusCode  HTTP status code (default: 200)
     */
    protected function paginated(
        LengthAwarePaginator $paginator,
        int $statusCode = 200
    ): JsonResponse {
        return $this->success(
            $paginator->items(),
            [
                'pagination' => [
                    'current_page' => $paginator->currentPage(),
                    'per_page' => $paginator->perPage(),
                    'total' => $paginator->total(),
                    'last_page' => $paginator->lastPage(),
                    'from' => $paginator->firstItem(),
                    'to' => $paginator->lastItem(),
                ],
            ],
            $statusCode
        );
    }

    /**
     * Return an error response
     *
     * @param  string  $code  Error code (e.g., 'VALIDATION_ERROR', 'NOT_FOUND')
     * @param  string  $message  Human-readable error message
     * @param  int  $statusCode  HTTP status code (default: 400)
     * @param  array|null  $details  Optional error details (e.g., validation errors)
     */
    protected function error(
        string $code,
        string $message,
        int $statusCode = 400,
        ?array $details = null
    ): JsonResponse {
        $response = [
            'error' => [
                'code' => $code,
                'message' => $message,
            ],
        ];

        if ($details !== null) {
            $response['error']['details'] = $details;
        }

        return response()->json($response, $statusCode);
    }

    /**
     * Return a validation error response
     *
     * @param  array  $errors  Validation errors from validator
     * @param  string  $message  Optional custom message
     */
    protected function validationError(
        array $errors,
        string $message = 'Los datos proporcionados no son válidos.'
    ): JsonResponse {
        return $this->error(
            'VALIDATION_ERROR',
            $message,
            422,
            $errors
        );
    }

    /**
     * Return a not found error response
     *
     * @param  string  $resource  Resource type (e.g., 'Gift Card', 'Transaction')
     * @param  string|null  $identifier  Optional identifier
     */
    protected function notFound(
        string $resource,
        ?string $identifier = null
    ): JsonResponse {
        $message = $identifier
            ? "{$resource} '{$identifier}' no encontrado."
            : "{$resource} no encontrado.";

        return $this->error(
            'NOT_FOUND',
            $message,
            404
        );
    }

    /**
     * Return an unauthorized error response
     *
     * @param  string  $message  Optional custom message
     */
    protected function unauthorized(
        string $message = 'No autorizado para realizar esta acción.'
    ): JsonResponse {
        return $this->error(
            'UNAUTHORIZED',
            $message,
            401
        );
    }

    /**
     * Return a forbidden error response
     *
     * @param  string  $message  Optional custom message
     */
    protected function forbidden(
        string $message = 'Acceso denegado.'
    ): JsonResponse {
        return $this->error(
            'FORBIDDEN',
            $message,
            403
        );
    }
}
