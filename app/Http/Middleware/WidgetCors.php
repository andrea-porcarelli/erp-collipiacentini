<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * CORS laschi per gli endpoint del widget embeddabile.
 * Il widget è caricato da qualunque sito WordPress partner, quindi serve Access-Control-Allow-Origin: *
 * (gli endpoint pubblici espongono solo info già visibili su miticko.com).
 */
class WidgetCors
{
    private const HEADERS = [
        'Access-Control-Allow-Origin' => '*',
        'Access-Control-Allow-Methods' => 'GET, POST, OPTIONS',
        'Access-Control-Allow-Headers' => 'Content-Type, Accept, Authorization, X-Miticko-Widget-Token',
        'Access-Control-Max-Age' => '3600',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        if ($request->isMethod('OPTIONS')) {
            return response('', 204)->withHeaders(self::HEADERS);
        }

        $response = $next($request);
        foreach (self::HEADERS as $key => $value) {
            $response->headers->set($key, $value);
        }

        return $response;
    }
}
