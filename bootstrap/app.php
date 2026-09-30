<?php

use App\Exceptions\ApiExceptionHandler;
use App\Http\Middleware\CacheResponse;
use App\Http\Middleware\EnsureIdempotency;
use App\Http\Middleware\HandleTransactions;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies('*');
        $middleware->throttleApi();
        $middleware->convertEmptyStringsToNull();
        $middleware->trimStrings();
        $middleware->statefulApi();
        $middleware->redirectGuestsTo(fn () => null);
        $middleware->append(EnsureIdempotency::class);
        $middleware->append(HandleTransactions::class);
        $middleware->alias(['cache.response' => CacheResponse::class]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => true,
        );
        $exceptions->render(function (Throwable $e, Request $request) {
            if ($request->is('api/*')) {
                return (new ApiExceptionHandler)->handle($e);
            }
        });
    })->create();
