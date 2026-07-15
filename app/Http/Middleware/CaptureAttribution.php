<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

/**
 * First-touch attribution: alla prima visita con parametri utm_* / gclid / fbclid
 * o con un referrer esterno li salviamo in sessione. Visite successive nella
 * stessa sessione NON sovrascrivono i valori raccolti, così l'ordine viene
 * attribuito al canale che ha effettivamente portato il cliente.
 *
 * Vedi OrderService::createOrderFromCart per la lettura e la persistenza sull'ordine.
 */
class CaptureAttribution
{
    public const SESSION_KEY = 'attribution';

    private const UTM_KEYS = ['utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content'];

    private const CLICK_ID_KEYS = ['gclid', 'fbclid'];

    public function handle(Request $request, Closure $next)
    {
        if ($request->isMethod('GET') && ! Session::has(self::SESSION_KEY)) {
            $attribution = $this->buildAttribution($request);
            if (! empty(array_filter($attribution))) {
                Session::put(self::SESSION_KEY, $attribution);
            }
        }

        return $next($request);
    }

    private function buildAttribution(Request $request): array
    {
        $data = [];
        foreach (array_merge(self::UTM_KEYS, self::CLICK_ID_KEYS) as $key) {
            $value = $request->query($key);
            if (is_string($value) && $value !== '') {
                // Tronca in modo conservativo per rispettare i limiti di colonna
                // (255 char sui click id, 191 sugli utm_*).
                $data[$key] = mb_substr(trim($value), 0, 255);
            }
        }

        $referer = $request->headers->get('referer');
        if ($referer) {
            $host = parse_url($referer, PHP_URL_HOST);
            $currentHost = $request->getHost();
            // Ignoriamo il referrer interno: non aggiunge informazione di attribuzione.
            if ($host && $host !== $currentHost) {
                $data['referrer'] = mb_substr($referer, 0, 500);
            }
        }

        return $data;
    }
}
