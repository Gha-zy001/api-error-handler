# API Error Handler

A Laravel package that provides centralized JSON error handling for API applications.

Instead of repeating exception handling logic in every project, this package automatically converts Laravel exceptions into consistent JSON responses and optionally logs them.

## Features

- Centralized API exception handling
- Standardized JSON error responses
- Automatic exception logging
- Configurable response keys
- Configurable log channel
- Debug mode support
- Publishable configuration
- Supports common Laravel exceptions

## Supported Exceptions

- ValidationException
- AuthenticationException
- AuthorizationException
- ModelNotFoundException
- NotFoundHttpException
- MethodNotAllowedHttpException
- TooManyRequestsHttpException
- QueryException
- HttpException
- Generic Exception

---

## Installation

Install the package via Composer.

```bash
composer require devgh/api-error-handler
```

Publish the configuration file.

```bash
php artisan vendor:publish --tag=error-handler-config
```

---

## Usage

No additional setup is required.

The package automatically intercepts API exceptions for requests that:

- use the `api/*` route prefix
- or expect JSON responses.

Example:

```php
Route::get('/users/{id}', function ($id) {
    return User::findOrFail($id);
});
```

Request:

```
GET /api/users/999
```

Response:

```json
{
    "success": false,
    "message": "User not found.",
    "code": 404
}
```

---

## Validation Errors

```json
{
    "success": false,
    "message": "The given data was invalid.",
    "code": 422,
    "errors": {
        "email": [
            "The email field is required."
        ]
    }
}
```

---

## Debug Mode

When `APP_DEBUG=true`, responses include debugging information.

```json
{
    "success": false,
    "message": "...",
    "code": 500,
    "debug": {
        "exception": "...",
        "file": "...",
        "line": 45,
        "trace": [...]
    }
}
```

---

## Configuration

```php
return [

    'debug' => env('APP_DEBUG', false),

    'log_errors' => true,

    'log_channel' => env('LOG_CHANNEL', 'stack'),

    'dont_report' => [

    ],

    'response_keys' => [
        'success' => 'success',
        'message' => 'message',
        'errors' => 'errors',
        'code' => 'code',
        'debug' => 'debug',
    ],

];
```

---

## Logging

Handled exceptions are automatically logged using Laravel's logging system.

You may configure:

- log channel
- exceptions that should not be reported
- disable logging completely

---

## Example Response

```json
{
    "success": false,
    "message": "Unauthenticated.",
    "code": 401
}
```

---

## License

MIT
