<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCustomer
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */public function handle(Request $request, Closure $next): Response
{
    if (!$request->user() instanceof \App\Models\Customer) {
        return response()->json(['message' => 'Unauthorized. Customers only.'], 403);
    }

    return $next($request);
}
}
