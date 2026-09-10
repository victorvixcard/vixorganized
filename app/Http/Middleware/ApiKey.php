<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Protege a API JSON com uma chave fixa (VIX_API_KEY no .env).
 * Uso: header "X-Api-Key: <chave>" ou ?api_key=<chave>.
 */
class ApiKey
{
    public function handle(Request $request, Closure $next): Response
    {
        $expected = (string) config('vix.api_key');
        $given = (string) ($request->header('X-Api-Key') ?? $request->query('api_key'));

        if ($expected === '' || ! hash_equals($expected, $given)) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        return $next($request);
    }
}
