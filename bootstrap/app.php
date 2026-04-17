<?php

use App\Http\Middleware\ApiToken;
use App\Http\Middleware\CartExists;
use App\Http\Middleware\Token;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: '*');

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
        $exceptions->report(function (NotFoundHttpException $e) {
            $request = request();
            Log::channel('single')->warning('404 NotFound', [
                'host'     => $request->getHost(),
                'method'   => $request->getMethod(),
                'full_url' => $request->fullUrl(),
                'path'     => $request->path(),
                'route'    => optional($request->route())->uri(),
                'route_name' => optional($request->route())->getName(),
                'referer'  => $request->headers->get('referer'),
                'user_agent' => $request->userAgent(),
                'ip'       => $request->ip(),
            ]);
        });
    })->create();
