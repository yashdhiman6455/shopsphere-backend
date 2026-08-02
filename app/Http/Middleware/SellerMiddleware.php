<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SellerMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->user() || !$request->user()->isApprovedSeller()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Approved seller access required.',
            ], Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}
