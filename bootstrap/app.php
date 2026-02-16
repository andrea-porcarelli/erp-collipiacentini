<?php

use App\Http\Middleware\ApiToken;
use App\Http\Middleware\CartExists;
use App\Http\Middleware\Token;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'token' => Token::class,
            'api.token' => ApiToken::class,
            'cart.exists' => CartExists::class,
        ]);

        // Escludi webhook Stripe dalla verifica CSRF
        $middleware->validateCsrfTokens(except: [
            'stripe/webhook',
            'api/*',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
