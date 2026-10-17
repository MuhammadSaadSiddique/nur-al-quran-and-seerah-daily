<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiSecureToken
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->header('X-Admin-Token');
        $expected = (string) config('services.bulk_api_token');
        if (!$token || $expected === '' || !hash_equals($expected, $token)) {
            return response()->json(['error' => 'Unauthorized. Invalid or missing X-Admin-Token header.'], 401);
        }

        return $next($request);
    }
}
