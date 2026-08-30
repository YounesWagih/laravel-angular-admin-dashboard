<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;
use Symfony\Component\HttpFoundation\Response;

final class EnsureStatefulRequest
{
    /**
     * @param  Closure(Request): (Response)  $next
     *
     * @throws TokenMismatchException
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->hasSession()) {
            throw new TokenMismatchException('Stateful authentication is required.');
        }

        return $next($request);
    }
}
