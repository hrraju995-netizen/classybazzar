<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Resolves the Sanctum user when a bearer token is present, but lets the
 * request continue as a guest when it is not.
 *
 * Used for guest checkout and guest reviews: if the customer happens to be
 * logged in we want the order attached to their account, but a missing
 * token must not turn into a 401.
 */
class OptionalSanctumAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->bearerToken()) {
            $user = Auth::guard('sanctum')->user();

            if ($user) {
                Auth::setUser($user);
                $request->setUserResolver(fn () => $user);
            }
        }

        return $next($request);
    }
}
