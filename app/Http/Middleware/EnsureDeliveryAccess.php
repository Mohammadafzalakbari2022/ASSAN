<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureDeliveryAccess
{
    /**
     * Let delivery staff through, send everyone else to the delivery sign-in.
     *
     * Guests and shop-account holders (admin or customer) both end up at
     * /delivery/login: guests so they can sign in, shop-account holders so they
     * can sign in as a delivery person instead of hitting a dead-end "forbidden"
     * page. Disabled delivery accounts are signed out on the spot.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null) {
            return redirect()->guest(route('delivery.login'));
        }

        if ($user->active === false && $user->isDelivery()) {
            Auth::logout();

            return redirect()->route('delivery.login')->with(
                'error',
                'This delivery account has been disabled. Ask the shop to enable it again.'
            );
        }

        if ($user->isDelivery()) {
            return $next($request);
        }

        return redirect()->route('delivery.login')->with(
            'info',
            'You are signed in with a shop account. Sign in here as a delivery person to see deliveries, or use a private browser window to keep the shop session open.'
        );
    }
}