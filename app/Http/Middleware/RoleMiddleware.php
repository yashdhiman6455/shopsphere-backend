<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        if (! $request->user() || ! $request->user()->hasRole($roles)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. You do not have access to this resource.',
            ], Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}
