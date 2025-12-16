<?php

namespace App\Http\Middleware;

use App\Models\Company;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

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
        Session::put('company', $company);
        Session::put('token', $token);
        $request->merge(['company' => $company]);

        return $next($request);
    }
}
