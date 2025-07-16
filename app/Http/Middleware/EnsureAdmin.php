<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class EnsureAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return \Symfony\Component\HttpFoundation\Response
     */
   public function handle(Request $request, Closure $next): Response
{
    $user = Auth::guard('admin')->user(); // 👈 force use of admin guard

    if (!$user || !$user instanceof \App\Models\Admin) {
        return response()->json(['message' => 'Unauthorized. Admins only.'], 403);
    }

    return $next($request);
}
}
