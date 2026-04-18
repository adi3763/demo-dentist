<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IsDoctor
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
         $user = $request->user();

        // Both admin and doctor can access doctor routes
        // Admin can see everything
        if (! $user || (! $user->isDoctor() && ! $user->isAdmin())) {
            return response()->json([
                'message' => 'Access denied.',
            ], 403);
        }
        return $next($request);
    }
}
