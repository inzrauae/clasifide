<?php

/*
 | 4U.lk – entry point for cPanel / shared hosting.
 | Works with the app folder next to this file (public_html/4ulk)
 | or one level up (~/4ulk next to ~/public_html).
 */

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// (kept compatible with very old PHP so the version message itself can always be shown)
function fourulk_fail($title, $help)
{
    http_response_code(500);
    header('Content-Type: text/html; charset=UTF-8');
    echo '<!doctype html><meta name="viewport" content="width=device-width,initial-scale=1"><title>Setup needed</title>'
        .'<div style="font:16px/1.6 system-ui,sans-serif;max-width:640px;margin:10vh auto;padding:24px;border:1px solid #e9e5f5;border-radius:16px">'
        .'<h1 style="font-size:22px;color:#1E1B4B">'.$title.'</h1><p style="color:#475569">'.$help.'</p></div>';
    exit;
}

if (PHP_VERSION_ID < 80200) {
    fourulk_fail('PHP 8.2 or newer is required',
        'This server runs PHP '.PHP_VERSION.'. In cPanel open <b>Select PHP Version</b> (or <b>MultiPHP Manager</b>), choose PHP 8.2 or 8.3 for this domain and reload the page.');
}

$appDir = null;
foreach ([__DIR__.'/4ulk', dirname(__DIR__).'/4ulk'] as $candidate) {
    if (is_file($candidate.'/vendor/autoload.php')) {
        $appDir = $candidate;
        break;
    }
}
if ($appDir === null) {
    fourulk_fail('Application folder not found',
        'The <b>4ulk</b> folder must be in the same folder as this index.php file. Extract the whole zip into your domain\'s folder (usually <b>public_html</b>) so that <b>public_html/index.php</b> and <b>public_html/4ulk/</b> both exist.');
}
// First install: the zip ships its settings as .env.new so updates never overwrite your live .env
if (! is_file($appDir.'/.env') && is_file($appDir.'/.env.new')) {
    @rename($appDir.'/.env.new', $appDir.'/.env');
}
if (! is_file($appDir.'/.env')) {
    fourulk_fail('Settings file missing',
        'The file <b>4ulk/.env</b> was not found. In File Manager turn on <b>Settings → Show Hidden Files</b>; if it is really missing, upload it again from the zip.');
}

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = $appDir.'/storage/framework/maintenance.php')) {
    require $maintenance;
}

require $appDir.'/vendor/autoload.php';

/** @var Application $app */
$app = require_once $appDir.'/bootstrap/app.php';

// The public files (this folder) are the web root
$app->usePublicPath(__DIR__);

$app->handleRequest(Request::capture());
