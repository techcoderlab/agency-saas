<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckTenantStatus
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->tenant && $user->tenant->status === 'suspended') {
            return response()->json([
                'message' => 'Your tenant account is suspended.',
            ], Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}


