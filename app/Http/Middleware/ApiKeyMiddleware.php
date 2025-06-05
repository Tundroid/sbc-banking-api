<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ApiKeyMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $key = $request->header('X-API-KEY');
        if ($key !== config('services.api.key')) {
            return response()->json(['message' => 'Invalid API Key'], 401);
        }
        return $next($request);
    }
}
