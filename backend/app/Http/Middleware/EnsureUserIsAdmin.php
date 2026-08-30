<?php

namespace App\Http\Middleware;

use App\Enums\UserType;
use Closure;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsureUserIsAdmin
{
    /**
     * @param  Closure(Request): Response  $next
     *
     * @throws AuthorizationException
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->type !== UserType::Admin) {
            throw new AuthorizationException;
        }

        return $next($request);
    }
}
