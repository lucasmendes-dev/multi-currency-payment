<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;

class ForceJsonResponse
{
    /**
     * Force every request under /api to receive JSON responses.
     * This prevents Sanctum from redirecting to /login when the token is missing or expired.
     */
    public function handle(Request $request, Closure $next): mixed
    {
        $request->headers->set('Accept', 'application/json');

        return $next($request);
    }
}
