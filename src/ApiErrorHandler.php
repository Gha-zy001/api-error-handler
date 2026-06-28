<?php

namespace Devgh\ApiErrorHandler;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Devgh\ApiErrorHandler\Response\ApiResponse;
use Throwable;

final class ApiErrorHandler
{
    private const DEFAULT_MESSAGES = [
        'authentication' => 'Unauthenticated.',
        'authorization' => 'Forbidden.',
        'not_found' => 'The requested resource was not found.',
        'method_not_allowed' => 'Method not allowed.',
        'too_many_requests' => 'Too many requests. Please slow down.',
        'query' => 'A database error occurred.',
        'generic' => 'Internal server error.',
    ];

  
    private const MAX_TRACE_FRAMES = 5;

  
    public function __construct(
        protected array $config = [],
    ) {
    }

    
    public function handle(Request $request, Throwable $e): JsonResponse
    {
        $this->logException($e);

        return match (true) {
            $e instanceof ValidationException => $this->validationException($e),
            $e instanceof AuthenticationException => $this->authenticationException($e),
            $e instanceof AuthorizationException => $this->authorizationException($e),
            $e instanceof ModelNotFoundException => $this->modelNotFoundException($e),
            $e instanceof NotFoundHttpException => $this->notFoundException($e),
            $e instanceof MethodNotAllowedHttpException => $this->methodNotAllowedException($e),
            $e instanceof TooManyRequestsHttpException => $this->tooManyRequestsException($e),
            $e instanceof QueryException => $this->queryException($e),
            $e instanceof HttpException => $this->httpException($e),
            default => $this->genericException($e),
        };
    }

    protected function validationException(ValidationException $e): JsonResponse
    {
        return $this->respond(
            message: $e->getMessage(),
            code: Response::HTTP_UNPROCESSABLE_ENTITY,
            errors: $e->errors(),
            exception: $e,
        );
    }

    protected function authenticationException(AuthenticationException $e): JsonResponse
    {
        return $this->respond(
            message: self::DEFAULT_MESSAGES['authentication'],
            code: Response::HTTP_UNAUTHORIZED,
            exception: $e,
        );
    }

    protected function authorizationException(AuthorizationException $e): JsonResponse
    {
        return $this->respond(
            message: $e->getMessage() ?: self::DEFAULT_MESSAGES['authorization'],
            code: Response::HTTP_FORBIDDEN,
            exception: $e,
        );
    }

    protected function modelNotFoundException(ModelNotFoundException $e): JsonResponse
    {
        $model = class_basename($e->getModel() ?? 'Model');

        return $this->respond(
            message: "{$model} not found.",
            code: Response::HTTP_NOT_FOUND,
            exception: $e,
        );
    }

    protected function notFoundException(NotFoundHttpException $e): JsonResponse
    {
        return $this->respond(
            message: $this->isDebug() ? $e->getMessage() : self::DEFAULT_MESSAGES['not_found'],
            code: Response::HTTP_NOT_FOUND,
            exception: $e,
        );
    }

    protected function methodNotAllowedException(MethodNotAllowedHttpException $e): JsonResponse
    {
        return $this->respond(
            message: $this->isDebug() ? $e->getMessage() : self::DEFAULT_MESSAGES['method_not_allowed'],
            code: Response::HTTP_METHOD_NOT_ALLOWED,
            exception: $e,
        );
    }

    protected function tooManyRequestsException(TooManyRequestsHttpException $e): JsonResponse
    {
        return $this->respond(
            message: self::DEFAULT_MESSAGES['too_many_requests'],
            code: Response::HTTP_TOO_MANY_REQUESTS,
            exception: $e,
        );
    }

    protected function queryException(QueryException $e): JsonResponse
    {
        return $this->respond(
            message: $this->isDebug() ? $e->getMessage() : self::DEFAULT_MESSAGES['query'],
            code: Response::HTTP_INTERNAL_SERVER_ERROR,
            exception: $e,
        );
    }

    protected function httpException(HttpException $e): JsonResponse
    {
        $message = $e->getMessage();
        $statusText = Response::$statusTexts[$e->getStatusCode()] ?? 'Error';

        return $this->respond(
            message: $message ?: $statusText,
            code: $e->getStatusCode(),
            exception: $e,
        );
    }

    protected function genericException(Throwable $e): JsonResponse
    {
        return $this->respond(
            message: $this->isDebug() ? $e->getMessage() : self::DEFAULT_MESSAGES['generic'],
            code: Response::HTTP_INTERNAL_SERVER_ERROR,
            exception: $e,
        );
    }

    protected function respond(
        string $message,
        int $code,
        array $errors = [],
        ?Throwable $exception = null,
    ): JsonResponse {
        $debug = [];

        if ($this->isDebug() && $exception !== null) {
            $debug['exception'] = $exception::class;
            $debug['file'] = $exception->getFile();
            $debug['line'] = $exception->getLine();
            $debug['trace'] = array_slice($exception->getTrace(), 0, self::MAX_TRACE_FRAMES);
        }

        return ApiResponse::error($errors, $message, $code, $debug);
    }

    protected function logException(Throwable $e): void
    {
        if (!$this->shouldLog()) {
            return;
        }

        if ($this->isDontReport($e)) {
            return;
        }

        Log::channel($this->logChannel())->error($e->getMessage(), [
            'exception' => $e::class,
            'file' => $e->getFile(),
            'line' => $e->getLine(),
        ]);
    }

    private function isDebug(): bool
    {
        return (bool) ($this->config['debug'] ?? false);
    }

    private function shouldLog(): bool
    {
        return (bool) ($this->config['log_errors'] ?? true);
    }

    private function logChannel(): string
    {
        return (string) ($this->config['log_channel'] ?? 'stack');
    }

    private function isDontReport(Throwable $e): bool
    {
        $dontReport = $this->config['dont_report'] ?? [];

        foreach ($dontReport as $type) {
            if ($e instanceof $type) {
                return true;
            }
        }

        return false;
    }
}
