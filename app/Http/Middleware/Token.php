<?php

namespace App\Http\Middleware;

use App\Models\Company;
use App\Models\Partner;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class Token
{
    public function handle(Request $request, Closure $next)
    {
        $token = $request->input('token') ?? $request->header('token') ?? $request->bearerToken();

        if (!$token) {
            if (!self::checkHost($request, $next)) {
                return response()->json([
                    'error' => 'Token mancante',
                    'message' => 'Il parametro token è richiesto'
                ], 401);
            }
        }

        $company = Company::where('token', $token)->first();

        if (!$company) {
            return response()->json([
                'error' => 'Token non valido',
                'message' => 'Nessuna azienda trovata con questo token'
            ], 401);
        }

        Session::put('company', $company);
        Session::put('token', $token);
        $request->merge(['company' => $company]);

        return $next($request);
    }

    private function checkHost($request, $next)
    {
        // Get the current host
        $host = $request->getHost();

        $partner = Partner::where('domain_name', $host)->first();
        if ($partner) {
            Session::put('partner', $partner);
            return $next($request);
        }


        // Get the current path
        $path = $request->path();

        $partner = Partner::where('slug_name', $path)->first();
        if ($partner) {
            Session::put('partner', $partner);
            return $next($request);
        }

        return false;
    }
}
