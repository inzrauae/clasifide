<?php

namespace App\Providers;

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Shared hosting: find the web root (where index.php lives) when the app has no public/ folder.
        //  - cPanel zip layout: public_html/index.php + public_html/4ulk/ (the app)
        //  - split layout:      ~/public_html/index.php + ~/4ulk/
        $public = config('app.public_path');
        if (! $public && ! is_dir(base_path('public'))) {
            foreach ([dirname(base_path()), dirname(base_path()).'/public_html'] as $candidate) {
                if (is_file($candidate.'/index.php')) {
                    $public = $candidate;
                    break;
                }
            }
        }
        if ($public) {
            $this->app->usePublicPath($public);
        }
    }

    public function boot(): void
    {
        // Older MySQL / MariaDB versions on shared hosts limit index length (error 1071)
        Schema::defaultStringLength(191);

        // Behind cPanel / Cloudflare proxies make generated links use https when the site is https
        if (str_starts_with((string) config('app.url'), 'https://') && $this->app->isProduction()) {
            URL::forceScheme('https');
        }
    }
}
