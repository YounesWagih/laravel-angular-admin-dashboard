<?php

namespace App\Http\Middleware;

use App\Enums\Status;
use Closure;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsActive
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->status !== Status::Active) {
            throw new AuthenticationException;
        }

        return $next($request);
    }
}
