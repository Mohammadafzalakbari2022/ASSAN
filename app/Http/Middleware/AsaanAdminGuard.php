<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Blocks admin backend panels that are not part of the ASAAN single-shop setup.
 *
 * The engine stays multi-site capable but the UI offers exactly one shop.
 * Site panels (site, locale, locale/site) and the site switcher are
 * handled by the setup command and must not be changed from the backend.
 * Language and currency are configurable in the backend.
 * This guard works for every group including "super".
 */
class AsaanAdminGuard
{
    /** @var string[] Admin resources that are locked down */
    protected $blocked = [
        'site',
        'locale',
        'locale/site',
    ];

    public function handle(Request $request, Closure $next)
    {
        $path = str_replace('.', '/', (string) $request->route('resource'));

        if (in_array($path, $this->blocked, true)) {
            abort(403, 'This page is locked for the ASAAN single-shop configuration.');
        }

        return $next($request);
    }
}