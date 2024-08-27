<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureEmailIsVerified
{
     /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Check if the user is logged in and if their email is verified
        if ($request->user() && !$request->user()->email_verified_at) {
            return response()->json(['error' => 'Your email address is not verified.'], 403);
        }

        return $next($request);
    }
}