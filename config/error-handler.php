<?php

return [

  /*
  |--------------------------------------------------------------------------
  | Debug Mode
  |--------------------------------------------------------------------------
  | When true, the error response will include the exception class,
  | file, line, and stack trace. Falls back to APP_DEBUG.
  */
  'debug' => env('APP_DEBUG', false),

  /*
  |--------------------------------------------------------------------------
  | Log Errors
  |--------------------------------------------------------------------------
  | Whether to log exceptions that are handled by this package.
  */
  'log_errors' => true,

  /*
  |--------------------------------------------------------------------------
  | Log Channel
  |--------------------------------------------------------------------------
  | The log channel to use for error logging.
  */
  'log_channel' => env('LOG_CHANNEL', 'stack'),

  /*
  |--------------------------------------------------------------------------
  | Don't Report
  |--------------------------------------------------------------------------
  | Exception classes that should NOT be logged.
  */
  'dont_report' => [
    \Illuminate\Auth\AuthenticationException::class,
    \Illuminate\Validation\ValidationException::class,
    \Symfony\Component\HttpKernel\Exception\NotFoundHttpException::class,
    \Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException::class,
  ],
];
