<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ValidateIdentifierHeader
{
    public function handle(Request $request, Closure $next)
    {
        $identifierType = strtolower($request->header('X-Account-Identifier-Type'));

        if (!in_array($identifierType, ['id', 'number'])) {
            return response()->json([
                'message' => 'Invalid or missing X-Account-Identifier-Type header.'
            ], 400);
        }

        // You can add it to the request for easier access later
        $request->merge(['identifier_type' => $identifierType]);

        return $next($request);
    }
}
