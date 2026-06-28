<?php

namespace Devgh\ApiErrorHandler\Response;

use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\LengthAwarePaginator;

final class ApiResponse
{
    public static function success(string $message = '', int $statusCode = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => (object)[],
            'errors' => (object)[],
        ], $statusCode);
    }

    public static function error(array $errors, string $message = '', int $statusCode = 422, array $debug = []): JsonResponse
    {
        $response = [
            'success' => false,
            'message' => $message,
            'data' => (object)[],
            'errors' => (object)$errors,
        ];

        if (!empty($debug)) {
            $response['debug'] = (object)$debug;
        }

        return response()->json($response, $statusCode);
    }

    public static function data(mixed $data, string $message = '', int $statusCode = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
            'errors' => (object)[],
        ], $statusCode);
    }

    public static function paginated(
        mixed $data,
        LengthAwarePaginator $paginator,
        string $message = '',
        int $statusCode = 200,
    ): JsonResponse {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
                'has_more' => $paginator->hasMorePages(),
            ],
            'errors' => (object)[],
        ], $statusCode);
    }
}
