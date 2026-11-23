<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Sends visitors to https:// – but only in production and only when APP_URL is an https address,
 * so a site without SSL yet keeps working (set APP_URL=http://... until AutoSSL is active).
 */
class ForceHttps
{
    public function handle(Request $request, Closure $next): Response
    {
        if (app()->isProduction()
            && str_starts_with((string) config('app.url'), 'https://')
            && ! $request->secure()
            && ! $request->is('payhere/notify', 'install/*')) {
            return redirect()->secure($request->getRequestUri(), 301);
        }

        return $next($request);
    }
}
