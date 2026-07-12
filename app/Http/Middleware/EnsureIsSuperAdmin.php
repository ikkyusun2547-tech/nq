<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gates the small set of admin actions that change who else has admin
 * access at all (promote/demote/ban) — a regular admin managing day-to-day
 * approvals shouldn't also be able to grant themselves or others more
 * power, or lock another admin out.
 */
class EnsureIsSuperAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::user()?->role !== 'super_admin') {
            abort(403);
        }

        return $next($request);
    }
}
