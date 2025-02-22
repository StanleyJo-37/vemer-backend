<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AddToken
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // $request->getBasePath();

        if (! $request->hasHeader('Authorization')) {
            $token = $request->cookie('sinau_rek_token');
            $request->headers->set('Authorization', "Bearer $token");
        }
        return $next($request);
    }
}
