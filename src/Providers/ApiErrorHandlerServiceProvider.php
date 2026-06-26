<?php

namespace Devgh\ApiErrorHandler\Providers;

use Devgh\ApiErrorHandler\ApiErrorHandler;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Foundation\Exceptions\Handler;
use Illuminate\Support\ServiceProvider;

class ApiErrorHandlerServiceProvider extends ServiceProvider
{
  public function register(): void
  {
    $this->mergeConfigFrom(
      __DIR__ . '/../../config/error-handler.php',
      'error-handler'
    );

    $this->app->singleton('api-error-handler', function ($app) {
      return new ApiErrorHandler(
        config: $app['config']->get('error-handler', [])
      );
    });
  }

  public function boot(): void
  {
    $this->publishes([
      __DIR__ . '/../../config/error-handler.php' => config_path('error-handler.php'),
    ], 'error-handler-config');

    $this->registerExceptionRendering();
  }

  protected function registerExceptionRendering(): void
  {
    $handler = $this->app->make(ExceptionHandler::class);

    if ($handler instanceof Handler) {
      $handler->renderable(function (\Throwable $e, $request) {
        if ($request->is('api/*') || $request->expectsJson()) {
          return $this->app->make('api-error-handler')->handle($request, $e);
        }
      });
    }
  }
}