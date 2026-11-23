<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;

/**
 * One-time installer for cPanel hosts without SSH access.
 * Enabled only while INSTALL_KEY is set in .env. Remove the key once the site is running.
 */
class InstallController extends Controller
{
    public function __invoke(string $key)
    {
        $expected = (string) config('app.install_key');
        abort_if($expected === '' || ! hash_equals($expected, $key), 404);

        @set_time_limit(300);
        $log = [];

        try {
            \Illuminate\Support\Facades\DB::connection()->getPdo();
        } catch (\Throwable $e) {
            return response("Cannot connect to the database:\n\n".$e->getMessage()
                ."\n\nCheck DB_DATABASE, DB_USERNAME and DB_PASSWORD in 4ulk/.env, and that the user is added to the database with ALL PRIVILEGES.",
                500, ['Content-Type' => 'text/plain; charset=UTF-8']);
        }

        try {
        Artisan::call('migrate', ['--force' => true]);
        $log[] = '$ php artisan migrate --force'."\n".trim(Artisan::output());

        // Seed only a fresh database so running the installer twice never duplicates data
        if (! Schema::hasTable('users') || User::count() === 0) {
            Artisan::call('db:seed', ['--force' => true]);
            $log[] = '$ php artisan db:seed --force'."\n".trim(Artisan::output());
        } else {
            $log[] = 'Database already has users: seeding skipped.';
        }

        foreach (['config:clear', 'route:clear', 'view:clear', 'cache:clear'] as $cmd) {
            Artisan::call($cmd);
        }
        $log[] = 'Caches cleared.';
        $log[] = "DONE. Now remove INSTALL_KEY from your .env file so this page is switched off.";
        } catch (\Throwable $e) {
            $log[] = 'FAILED: '.$e->getMessage();
        }

        return response(implode("\n\n", $log), 200, ['Content-Type' => 'text/plain; charset=UTF-8', 'X-Robots-Tag' => 'noindex']);
    }
}
