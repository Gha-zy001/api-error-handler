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
use Throwable;

class ApiErrorHandler
{
  protected array $config;

  public function __construct(array $config = [])
  {
    $this->config = $config;
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

  // -------------------------------------------------------------------------
  //  Exception-specific handlers
  // -------------------------------------------------------------------------

  protected function validationException(ValidationException $e): JsonResponse
  {
    return $this->respond(
      message: $e->getMessage(),
      code: Response::HTTP_UNPROCESSABLE_ENTITY,
      errors: $e->errors(),
    );
  }

  protected function authenticationException(AuthenticationException $e): JsonResponse
  {
    return $this->respond(
      message: 'Unauthenticated.',
      code: Response::HTTP_UNAUTHORIZED,
    );
  }

  protected function authorizationException(AuthorizationException $e): JsonResponse
  {
    return $this->respond(
      message: $e->getMessage() ?: 'Forbidden.',
      code: Response::HTTP_FORBIDDEN,
    );
  }

  protected function modelNotFoundException(ModelNotFoundException $e): JsonResponse
  {
    $model = class_basename($e->getModel());

    return $this->respond(
      message: "{$model} not found.",
      code: Response::HTTP_NOT_FOUND,
    );
  }

  protected function notFoundException(NotFoundHttpException $e): JsonResponse
  {
    return $this->respond(
      message: 'The requested resource was not found.',
      code: Response::HTTP_NOT_FOUND,
    );
  }

  protected function methodNotAllowedException(MethodNotAllowedHttpException $e): JsonResponse
  {
    return $this->respond(
      message: 'Method not allowed.',
      code: Response::HTTP_METHOD_NOT_ALLOWED,
    );
  }

  protected function tooManyRequestsException(TooManyRequestsHttpException $e): JsonResponse
  {
    return $this->respond(
      message: 'Too many requests. Please slow down.',
      code: Response::HTTP_TOO_MANY_REQUESTS,
    );
  }

  protected function queryException(QueryException $e): JsonResponse
  {
    $debug = $this->config['debug'] ?? false;

    return $this->respond(
      message: $debug ? $e->getMessage() : 'A database error occurred.',
      code: Response::HTTP_INTERNAL_SERVER_ERROR,
      exception: $e,
    );
  }

  protected function httpException(HttpException $e): JsonResponse
  {
    return $this->respond(
      message: $e->getMessage() ?: Response::$statusTexts[$e->getStatusCode()] ?? 'Error',
      code: $e->getStatusCode(),
    );
  }

  protected function genericException(Throwable $e): JsonResponse
  {
    $debug = $this->config['debug'] ?? false;

    return $this->respond(
      message: $debug ? $e->getMessage() : 'Internal server error.',
      code: Response::HTTP_INTERNAL_SERVER_ERROR,
      exception: $e,
    );
  }

  // -------------------------------------------------------------------------
  //  Helpers
  // -------------------------------------------------------------------------

  protected function respond(
    string $message,
    int $code,
    array $errors = [],
    ?Throwable $exception = null,
  ): JsonResponse {
    $keys = $this->config['response_keys'] ?? [
      'success' => 'success',
      'message' => 'message',
      'errors' => 'errors',
      'code' => 'code',
      'debug' => 'debug',
    ];

    $payload = [
      $keys['success'] => false,
      $keys['message'] => $message,
      $keys['code'] => $code,
    ];

    if (!empty($errors)) {
      $payload[$keys['errors']] = $errors;
    }

    if (($this->config['debug'] ?? false) && $exception) {
      $payload[$keys['debug']] = [
        'exception' => get_class($exception),
        'file' => $exception->getFile(),
        'line' => $exception->getLine(),
        'trace' => collect($exception->getTrace())->take(5)->toArray(),
      ];
    }

    return new JsonResponse($payload, $code);
  }

  protected function logException(Throwable $e): void
  {
    if (!($this->config['log_errors'] ?? true)) {
      return;
    }

    $dontReport = $this->config['dont_report'] ?? [];

    foreach ($dontReport as $type) {
      if ($e instanceof $type) {
        return;
      }
    }

    $channel = $this->config['log_channel'] ?? 'stack';

    Log::channel($channel)->error($e->getMessage(), [
      'exception' => get_class($e),
      'file' => $e->getFile(),
      'line' => $e->getLine(),
    ]);
  }
}
