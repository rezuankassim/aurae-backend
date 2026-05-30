<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class OptionalSanctumAuth
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->bearerToken() && Auth::guard('sanctum')->check()) {
            Auth::setUser(Auth::guard('sanctum')->user());

            dd(auth('sanctum')->user());
        }

        return $next($request);
    }
}
