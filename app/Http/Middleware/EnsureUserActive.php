<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Signs out an account that has been disabled while it still had a session, so
 * turning a delivery person off takes effect immediately.
 */
class EnsureUserActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user !== null && !$user->active) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('delivery.login')->withErrors([
                'email' => 'This delivery account has been disabled.',
            ]);
        }

        return $next($request);
    }
}
