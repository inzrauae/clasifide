<?php
/*
 | 4U.lk setup check – open https://your-domain/setup-check.php?key=YOUR_INSTALL_KEY
 | Shows what is wrong when the site does not load. It works only while INSTALL_KEY is set in 4ulk/.env.
 | Delete this file (or empty INSTALL_KEY) once the site is running.
 */
error_reporting(0);
header('Content-Type: text/html; charset=UTF-8');
header('X-Robots-Tag: noindex');

$appDir = null;
foreach (array(__DIR__ . '/4ulk', dirname(__DIR__) . '/4ulk') as $c) {
    if (is_dir($c)) { $appDir = $c; break; }
}

function env_file($path) {
    $out = array();
    if (!is_file($path)) return $out;
    foreach (file($path, FILE_IGNORE_NEW_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#' || strpos($line, '=') === false) continue;
        list($k, $v) = explode('=', $line, 2);
        $v = trim($v);
        if (strlen($v) > 1 && ($v[0] === '"' || $v[0] === "'") && substr($v, -1) === $v[0]) $v = substr($v, 1, -1);
        $out[trim($k)] = $v;
    }
    return $out;
}
if ($appDir && !is_file($appDir . '/.env') && is_file($appDir . '/.env.new')) @rename($appDir . '/.env.new', $appDir . '/.env');
$env = $appDir ? env_file($appDir . '/.env') : array();
$key = isset($env['INSTALL_KEY']) ? $env['INSTALL_KEY'] : '';
$given = isset($_GET['key']) ? (string) $_GET['key'] : '';
if ($appDir && ($key === '' || !hash_equals($key, $given))) {
    http_response_code(404);
    echo 'Not found. (Add ?key=YOUR_INSTALL_KEY from 4ulk/.env – the check only works while INSTALL_KEY is set.)';
    exit;
}

$rows = array();
function row(&$rows, $ok, $label, $detail = '') { $rows[] = array($ok, $label, $detail); }

// PHP
row($rows, PHP_VERSION_ID >= 80200, 'PHP version ' . PHP_VERSION, PHP_VERSION_ID >= 80200 ? '' : 'Needs 8.2 or newer: cPanel → Select PHP Version / MultiPHP Manager.');
foreach (array('pdo_mysql', 'mbstring', 'openssl', 'tokenizer', 'dom', 'ctype', 'json', 'fileinfo', 'gd', 'bcmath', 'curl', 'filter', 'session') as $ext) {
    $ok = extension_loaded($ext);
    if (!$ok) row($rows, false, 'PHP extension: ' . $ext, 'Tick it in cPanel → Select PHP Version → Extensions.');
}
row($rows, true, 'PHP extensions checked');

// Files
row($rows, (bool) $appDir, 'App folder 4ulk/', $appDir ? $appDir : 'Not found next to this file. Extract the whole zip into the domain folder.');
if ($appDir) {
    row($rows, is_file($appDir . '/vendor/autoload.php'), 'vendor/ libraries', 'Missing: the upload was incomplete. Extract the zip again.');
    row($rows, is_file($appDir . '/.env'), '.env settings file', 'Missing: turn on Show Hidden Files in File Manager, or upload it again.');
    $appKey = isset($env['APP_KEY']) ? $env['APP_KEY'] : '';
    row($rows, strpos($appKey, 'base64:') === 0 && strlen($appKey) > 40, 'APP_KEY', 'Must look like base64:… – do not empty it.');
    $debug = isset($env['APP_DEBUG']) ? strtolower($env['APP_DEBUG']) : 'false';
    row($rows, $debug !== 'true', 'APP_DEBUG is off', 'Set APP_DEBUG=false again when you finish testing.');

    foreach (array('storage', 'storage/framework/cache', 'storage/framework/sessions', 'storage/framework/views', 'storage/logs', 'bootstrap/cache') as $d) {
        $p = $appDir . '/' . $d;
        if (!is_dir($p)) @mkdir($p, 0755, true);
        row($rows, is_dir($p) && is_writable($p), 'Writable: 4ulk/' . $d, 'Set folder permission to 755 in File Manager.');
    }
}
$up = __DIR__ . '/uploads';
if (!is_dir($up)) @mkdir($up, 0755, true);
row($rows, is_dir($up) && is_writable($up), 'Writable: uploads/', 'Set folder permission to 755.');
row($rows, is_file(__DIR__ . '/build/manifest.json'), 'CSS/JS build files', 'The build/ folder is missing next to index.php. Extract the zip again.');

// URL
$https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
$here = ($https ? 'https://' : 'http://') . (isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '');
$appUrl = isset($env['APP_URL']) ? rtrim($env['APP_URL'], '/') : '';
row($rows, $appUrl !== '' && stripos($appUrl, $here) === 0 || $appUrl === $here, 'APP_URL = ' . $appUrl, 'You opened ' . $here . '. Set APP_URL to exactly this address (use http:// until SSL works).');

// Database
$dbOk = false; $dbMsg = ''; $tables = 0;
if (($env['DB_CONNECTION'] ?? 'mysql') === 'mysql' && class_exists('PDO')) {
    try {
        $dsn = 'mysql:host=' . ($env['DB_HOST'] ?? 'localhost') . ';port=' . ($env['DB_PORT'] ?? '3306') . ';dbname=' . ($env['DB_DATABASE'] ?? '') . ';charset=utf8mb4';
        $pdo = new PDO($dsn, $env['DB_USERNAME'] ?? '', $env['DB_PASSWORD'] ?? '', array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_TIMEOUT => 5));
        $dbOk = true;
        $tables = (int) $pdo->query('SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE()')->fetchColumn();
        $ver = $pdo->query('SELECT VERSION()')->fetchColumn();
        $dbMsg = 'Connected (' . $ver . '), ' . $tables . ' tables.';
    } catch (Exception $e) {
        $dbMsg = $e->getMessage() . ' → check DB_DATABASE, DB_USERNAME, DB_PASSWORD in 4ulk/.env and that the user is added to the database with ALL PRIVILEGES.';
    }
} elseif (!class_exists('PDO')) {
    $dbMsg = 'PDO is not available: enable pdo_mysql in Select PHP Version → Extensions.';
} else {
    $dbOk = true; $tables = 99;
    $dbMsg = 'Using ' . $env['DB_CONNECTION'] . ' (not checked here).';
}
row($rows, $dbOk, 'Database', $dbMsg);
if ($dbOk) row($rows, $tables > 5, 'Tables created', $tables > 5 ? '' : 'Not yet: run the installer (link below).');

// Latest log
$log = '';
if ($appDir && is_dir($appDir . '/storage/logs')) {
    $files = glob($appDir . '/storage/logs/*.log');
    if ($files) {
        usort($files, function ($a, $b) { return filemtime($b) - filemtime($a); });
        $lines = @file($files[0]);
        $log = basename($files[0]) . "\n\n" . implode('', array_slice($lines ?: array(), -60));
    }
}
$fails = count(array_filter($rows, function ($r) { return !$r[0]; }));
?>
<!doctype html>
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>4U.lk setup check</title>
<style>
 body{font:15px/1.55 system-ui,-apple-system,Segoe UI,sans-serif;background:#F4F2F9;color:#1E1B4B;margin:0;padding:24px}
 .box{max-width:820px;margin:0 auto;background:#fff;border:1px solid #E9E5F5;border-radius:16px;padding:24px}
 h1{margin:0 0 4px;font-size:22px} .sum{margin:0 0 18px;color:#64748B}
 table{width:100%;border-collapse:collapse} td{padding:9px 8px;border-top:1px solid #F1EEF9;vertical-align:top}
 .ok{color:#059669;font-weight:700} .no{color:#DC2626;font-weight:700} .d{color:#64748B;font-size:13.5px}
 pre{background:#0E0820;color:#E9E5F5;padding:14px;border-radius:12px;overflow:auto;font-size:12px;max-height:420px;white-space:pre-wrap}
 a.btn{display:inline-block;margin-top:16px;background:#7C3AED;color:#fff;padding:10px 16px;border-radius:12px;text-decoration:none;font-weight:700}
</style>
<div class="box">
<h1>4U.lk setup check</h1>
<p class="sum"><?php echo $fails ? "<b class='no'>$fails problem(s) found.</b> Fix the red items, then reload this page." : "<b class='ok'>Everything looks good.</b>"; ?></p>
<table>
<?php foreach ($rows as $r): ?>
<tr><td class="<?php echo $r[0] ? 'ok' : 'no'; ?>"><?php echo $r[0] ? '✓' : '✗'; ?></td><td><b><?php echo htmlspecialchars($r[1]); ?></b><?php if (!$r[0] && $r[2] !== '' || $r[0] && $r[2] !== '' && $r[1] === 'Database'): ?><div class="d"><?php echo htmlspecialchars($r[2]); ?></div><?php endif; ?></td></tr>
<?php endforeach; ?>
</table>
<?php if ($dbOk): ?>
<a class="btn" href="install/<?php echo htmlspecialchars(rawurlencode($key)); ?>">Run installer (create tables + admin)</a>
<?php endif; ?>
<?php if ($log !== ''): ?>
<h2 style="font-size:17px;margin-top:26px">Latest error log</h2>
<pre><?php echo htmlspecialchars($log); ?></pre>
<?php endif; ?>
<p class="d" style="margin-top:20px">When the site works: delete the value after <b>INSTALL_KEY=</b> in 4ulk/.env. This page and the installer are then switched off.</p>
</div>
