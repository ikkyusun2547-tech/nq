<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class EnsureSrruEmailApi
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();
        $domain = config('services.srru.email_domain');

        if ($user && ! Str::endsWith($user->email, '@'.$domain)) {
            $user->currentAccessToken()?->delete();

            return response()->json([
                'message' => __('อนุญาตเฉพาะบัญชีอีเมลของมหาวิทยาลัย (@:domain) เท่านั้น', ['domain' => $domain]),
                'error_code' => 'EMAIL_DOMAIN_NOT_ALLOWED',
            ], 403);
        }

        return $next($request);
    }
}
