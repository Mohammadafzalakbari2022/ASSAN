<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserRole
{
    /**
     * Allow the request only when the logged-in account has one of the given roles.
     *
     * The "admin" role also matches accounts flagged as superusers, so an admin
     * created through the shop's own account command keeps full access.
     *
     * @param  string  ...$roles
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if ($user === null) {
            return redirect()->guest(route('login'));
        }

        foreach ($roles as $role) {
            if ($role === 'admin' && $user->isAdmin()) {
                return $next($request);
            }

            if ($user->role === $role) {
                return $next($request);
            }
        }

        abort(403, 'You do not have access to this area.');
    }
}