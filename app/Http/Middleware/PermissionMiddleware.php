<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PermissionMiddleware
{
    public function handle(Request $request, Closure $next, ...$permissions): Response
    {
        if (! $request->user() || ! $request->user()->hasAnyPermission($permissions)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. You do not have the required permission.',
            ], Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}
