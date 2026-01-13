<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class ApiBasicAuth
{
    /**
     * Handle an incoming request.
     *
     * @param \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response) $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!empty(config('api-auth.password')) &&
            config('api-auth.user') === $request->getUser() &&
            config('api-auth.password') === $request->getPassword()) {
            return $next($request);
        }
        Log::error($request->getUser() . ' tried to access ' . $request->getRequestUri() . ' with invalid credentials.',[
            config('api-auth.user'),
        ]);
        return response('You shall not pass!', 401, ['WWW-Authenticate' => 'Basic']);
    }
}
