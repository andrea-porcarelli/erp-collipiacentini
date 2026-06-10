<?php

use App\Http\Middleware\ApiToken;
use App\Http\Middleware\CartExists;
use App\Http\Middleware\Token;
use App\Notifications\ErrorTelegramNotify;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Request;
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

        $exceptions->report(function (Throwable $e) {
            try {
                if ($e instanceof NotFoundHttpException) {
                    return;
                }

                $chatId = config('services.telegram.error_chat_id');
                if (empty($chatId)) {
                    return;
                }

                $fingerprint = 'tg_err:' . md5(get_class($e) . '|' . $e->getFile() . ':' . $e->getLine() . '|' . $e->getMessage());
                if (Cache::has($fingerprint)) {
                    return;
                }
                Cache::put($fingerprint, 1, now()->addMinutes(5));

                $context = [];
                if (app()->bound('request') && Request::instance() !== null) {
                    $context['url'] = Request::fullUrl();
                    $context['method'] = Request::method();
                }
                if (Auth::check()) {
                    $context['user_id'] = Auth::id();
                }

                Notification::route('telegram', $chatId)
                    ->notify(new ErrorTelegramNotify($e, $context));
            } catch (Throwable $inner) {
                logger()->error('ErrorTelegramNotify failed', [
                    'message' => $inner->getMessage(),
                ]);
            }
        });
    })->create();
