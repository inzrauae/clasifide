<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'admin' => \App\Http\Middleware\EnsureAdmin::class,
        ]);
        $middleware->web(prepend: [
            \App\Http\Middleware\ForceHttps::class,
        ]);
        $middleware->web(append: [
            \App\Http\Middleware\BlockBannedUsers::class,
        ]);
        $middleware->validateCsrfTokens(except: ['payhere/notify']);
        // cPanel / Cloudflare sit in front of PHP: trust their forwarded HTTPS headers
        $middleware->trustProxies(at: '*');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Fresh cPanel install: explain database problems instead of a bare "500 Server Error"
        $exceptions->render(function (\Illuminate\Database\QueryException $e) {
            if (config('app.debug')) {
                return null;
            }
            $msg = $e->getMessage();
            if (preg_match('/no such table|Base table or view not found|42S02/i', $msg)) {
                return response()->view('errors.setup', ['problem' => 'tables'], 503);
            }
            if (preg_match('/SQLSTATE\[HY000\] \[(1044|1045|1049|2002|2005|2054)\]|Access denied|Unknown database|Connection refused/i', $msg)) {
                return response()->view('errors.setup', ['problem' => 'connection'], 503);
            }

            return null;
        });
    })->create();
