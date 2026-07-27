<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

/**
 * Restrict a route to users holding at least one of the given roles.
 *
 * Usage in routes: ->middleware('role:DOCTOR,NURSE').
 * Returns 401 when unauthenticated and 403 when authenticated without the role,
 * without revealing details about the resource (frontend section 4.3).
 */
class EnsureUserHasRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (SymfonyResponse)  $next
     */
    public function handle(Request $request, Closure $next, string ...$roles): SymfonyResponse
    {
        $user = $request->user();

        if ($user === null) {
            return response()->json([
                'data' => null,
                'message' => 'Unauthenticated.',
                'errors' => null,
            ], Response::HTTP_UNAUTHORIZED);
        }

        if (! $user->hasAnyRole($roles)) {
            return response()->json([
                'data' => null,
                'message' => 'You do not have permission to perform this action.',
                'errors' => null,
            ], Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}
