<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class RedirectIfNotAuthenticated
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->expectsJson() || $request->is('api/*')) {
            if (!Auth::check()) {
                // For API requests, return a JSON response with a 401 status
                return response()->json(['message' => 'Unauthenticated.'], 401);
            }
        } else {
            // For web requests, redirect as usual
            if (!Auth::check()) {
                return redirect('/'); // Redirect guest users to homepage
            }
        }
        // if (!Auth::check()) {
        //     return redirect('/'); // Redirect guest users to homepage
        // }
        return $next($request);
    }
}
