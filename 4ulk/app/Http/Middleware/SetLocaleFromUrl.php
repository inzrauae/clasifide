<?php

namespace App\Http\Middleware;

use App\Http\LocalizedRequest;
use App\Support\Lang;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Sinhala pages live under /si (e.g. /si/ads, /si/dashboard).
 *
 * Instead of registering every route twice, this strips the /si prefix before
 * routing and switches the app to Sinhala. Every URL the app then generates
 * (route(), url(), redirects) gets /si put back, so visitors stay in Sinhala.
 * Route names are unchanged, so routeIs() and route('home') keep working.
 */
class SetLocaleFromUrl
{
    /** Routes that never get a language prefix: the admin panel and machine callbacks. */
    private const UNPREFIXED_ROUTES = ['admin.*', 'payhere.notify', 'install', 'auth.google.callback'];

    public function handle(Request $request, Closure $next): Response
    {
        $locale = Lang::DEFAULT;

        if (preg_match('#^/('.implode('|', Lang::PREFIXED).')(/.*)?$#', $request->getPathInfo(), $m)) {
            $locale = $m[1];
            $rest = ($m[2] ?? '') === '' ? '/' : $m[2];
            $query = $request->getQueryString();

            $server = $request->server->all();
            $server['REQUEST_URI'] = $request->getBaseUrl().$rest.($query !== null ? '?'.$query : '');
            $request = LocalizedRequest::createFrom($request->duplicate(null, null, null, null, null, $server));
            $request->localePrefix = '/'.$locale;
        }

        App::setLocale($locale);
        Carbon::setLocale($locale);
        $request->attributes->set('locale', $locale);

        // Set on every request (not just Sinhala ones) so a long-lived app never keeps a stale prefix
        URL::formatPathUsing(function (string $path, $route = null) use ($locale) {
            if ($locale === Lang::DEFAULT
                || ($route && $route->getName() && Str::is(self::UNPREFIXED_ROUTES, $route->getName()))
                || $path === '/admin' || str_starts_with($path, '/admin/')) {
                return $path;
            }

            return '/'.$locale.($path === '/' ? '' : $path);
        });

        try {
            return $next($request);
        } finally {
            // The response is built: leave the app in English for whatever runs next in this process
            URL::formatPathUsing(fn (string $path) => $path);
            App::setLocale(Lang::DEFAULT);
            Carbon::setLocale(Lang::DEFAULT);
        }
    }
}
