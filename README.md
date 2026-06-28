<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://packagist.org/packages/devgh/api-error-handler"><img src="https://img.shields.io/packagist/dt/devgh/api-error-handler" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/devgh/api-error-handler"><img src="https://img.shields.io/packagist/v/devgh/api-error-handler" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/devgh/api-error-handler"><img src="https://img.shields.io/packagist/l/devgh/api-error-handler" alt="License"></a>
</p>

# Laravel API Error Handler

A Laravel package that provides centralized API exception handling and standardized JSON API responses.

---

## Quick Example

```php
use ApiResponse;

// Success response
ApiResponse::success('Operation completed successfully.');

// Data response
ApiResponse::data($user, 'User retrieved successfully.');

// Error response
ApiResponse::error(['email' => ['The email field is required.']], 'Validation failed.', 422);

// Paginated response
ApiResponse::paginated($users, $paginator, 'Users retrieved successfully.');
```

---

## Features

- Centralized API exception handling
- Standardized JSON API responses
- Global exception rendering
- Success, error, data and paginated response helpers
- Automatic exception logging
- Debug mode support
- Facade support
- Composer auto-discovery
- Publishable configuration
- Supports common Laravel exceptions

---

## Installation

```bash
composer require devgh/api-error-handler
```

### Service Provider & Facade

Laravel's auto-discovery will automatically register the service provider and facade. If you disable auto-discovery, add the following manually:

```php
// config/app.php
'providers' => [
    Devgh\ApiErrorHandler\Providers\ApiErrorHandlerServiceProvider::class,
],

'aliases' => [
    'ApiResponse' => Devgh\ApiErrorHandler\Facades\ApiResponse::class,
],
```

### Publish Configuration

```bash
php artisan vendor:publish --tag=error-handler-config
```

---

## Usage

The package automatically handles exceptions for API requests. When a request targets an `api/*` route or expects a JSON response, exceptions are caught and rendered as standardized JSON responses.

You can also use the `ApiResponse` facade to return standardized JSON responses manually in your controllers.

```php
<?php

namespace App\Http\Controllers;

use ApiResponse;

class UserController extends Controller
{
    public function index()
    {
        $users = User::paginate(15);

        return ApiResponse::paginated(
            $users->items(),
            $users,
            'Users retrieved successfully.'
        );
    }

    public function show(User $user)
    {
        return ApiResponse::data($user, 'User retrieved successfully.');
    }
}
```

---

## API Responses

### `ApiResponse::success()`

Return a generic success response.

```php
ApiResponse::success('Operation completed successfully.');
```

```json
{
    "success": true,
    "message": "Operation completed successfully.",
    "data": {},
    "errors": {}
}
```

### `ApiResponse::data()`

Return a success response with data.

```php
ApiResponse::data($user, 'User retrieved successfully.');
```

```json
{
    "success": true,
    "message": "User retrieved successfully.",
    "data": {
        "id": 1,
        "name": "John Doe",
        "email": "john@example.com"
    },
    "errors": {}
}
```

### `ApiResponse::error()`

Return an error response with validation errors or custom error messages.

```php
ApiResponse::error(
    ['email' => ['The email field is required.']],
    'Validation failed.',
    422
);
```

```json
{
    "success": false,
    "message": "Validation failed.",
    "data": {},
    "errors": {
        "email": [
            "The email field is required."
        ]
    }
}
```

### `ApiResponse::paginated()`

Return a paginated success response with metadata.

```php
ApiResponse::paginated($users->items(), $users, 'Users retrieved successfully.');
```

```json
{
    "success": true,
    "message": "Users retrieved successfully.",
    "data": [
        {
            "id": 1,
            "name": "John Doe"
        },
        {
            "id": 2,
            "name": "Jane Doe"
        }
    ],
    "meta": {
        "current_page": 1,
        "last_page": 5,
        "per_page": 15,
        "total": 75,
        "from": 1,
        "to": 15,
        "has_more": true
    },
    "errors": {}
}
```

---

## Response Helpers

| Method | Description |
|---|---|
| `ApiResponse::success(string $message, int $statusCode)` | Return a generic success response |
| `ApiResponse::data(mixed $data, string $message, int $statusCode)` | Return a success response with data |
| `ApiResponse::error(array $errors, string $message, int $statusCode, array $debug)` | Return an error response |
| `ApiResponse::paginated(mixed $data, LengthAwarePaginator $paginator, string $message, int $statusCode)` | Return a paginated success response |

---

## Exception Handling

The package automatically renders the following exceptions for API requests:

| Exception | HTTP Status | Response Message |
|---|---|---|
| `ValidationException` | 422 | The given data was invalid. |
| `AuthenticationException` | 401 | Unauthenticated. |
| `AuthorizationException` | 403 | Forbidden. |
| `ModelNotFoundException` | 404 | {Model} not found. |
| `NotFoundHttpException` | 404 | The requested resource was not found. |
| `MethodNotAllowedHttpException` | 405 | Method not allowed. |
| `TooManyRequestsHttpException` | 429 | Too many requests. Please slow down. |
| `QueryException` | 500 | A database error occurred. |
| `HttpException` | * | Varies by status code. |
| Generic `Throwable` | 500 | Internal server error. |

Non-API requests are unaffected and continue to use Laravel's default exception handling.

---

## Debug Mode

When enabled, the error response includes exception details. Debug mode is enabled when `APP_DEBUG` is `true` or when explicitly set in the configuration.

```json
{
    "success": false,
    "message": "Internal server error.",
    "data": {},
    "errors": {},
    "debug": {
        "exception": "Symfony\\Component\\HttpKernel\\Exception\\NotFoundHttpException",
        "file": "/var/www/app/Http/Controllers/UserController.php",
        "line": 45,
        "trace": []
    }
}
```

---

## Configuration

Publish the configuration file to customize behavior:

```bash
php artisan vendor:publish --tag=error-handler-config
```

The following options are available in `config/error-handler.php`:

### `debug`

When `true`, the error response includes the exception class, file, line, and stack trace. Defaults to the value of `APP_DEBUG`.

```php
'debug' => env('APP_DEBUG', false),
```

### `log_errors`

Whether to log exceptions that are handled by this package.

```php
'log_errors' => true,
```

### `log_channel`

The log channel to use for error logging.

```php
'log_channel' => env('LOG_CHANNEL', 'stack'),
```

### `dont_report`

An array of exception classes that should NOT be logged.

```php
'dont_report' => [
    \Illuminate\Auth\AuthenticationException::class,
    \Illuminate\Validation\ValidationException::class,
    \Symfony\Component\HttpKernel\Exception\NotFoundHttpException::class,
    \Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException::class,
],
```

---

## Compatibility

- **PHP**: 8.2+
- **Laravel**: 10+

---


## License

MIT
