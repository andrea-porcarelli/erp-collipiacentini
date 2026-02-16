<?php

namespace App\Http\Middleware;

use App\Models\Company;
use Closure;
use Illuminate\Http\Request;

class ApiToken
{
    public function handle(Request $request, Closure $next)
    {
        $token = $request->header('token') ?? $request->bearerToken();

        if (!$token) {
            return response()->json([
                'error' => 'Token mancante',
                'message' => 'Il parametro token è richiesto'
            ], 401);
        }

        $company = Company::where('token', $token)->first();

        if (!$company) {
            return response()->json([
                'error' => 'Token non valido',
                'message' => 'Nessuna azienda trovata con questo token'
            ], 401);
        }

        if (!$company->has_whitelabel) {
            return response()->json([
                'error' => 'Accesso non consentito',
                'message' => 'L\'azienda non ha il servizio WhiteLabel attivo'
            ], 403);
        }

        $request->merge(['company' => $company]);

        return $next($request);
    }
}
