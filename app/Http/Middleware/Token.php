<?php

namespace App\Http\Middleware;

use App\Models\Company;
use Closure;
use Illuminate\Http\Request;

class Token
{
    public function handle(Request $request, Closure $next)
    {
        $token = $request->input('token') ?? $request->header('token') ?? $request->bearerToken();

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

        $request->merge(['company' => $company]);

        return $next($request);
    }
}
