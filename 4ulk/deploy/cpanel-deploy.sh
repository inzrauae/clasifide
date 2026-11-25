#!/usr/bin/env bash
# Deploy / update 4U.lk on cPanel from a git clone. Run it in cPanel → Terminal:
#
#   First time:
#     cd ~ && git clone https://github.com/inzrauae/clasifide.git
#     APP_URL=https://yourdomain.lk DB_DATABASE=... DB_USERNAME=... DB_PASSWORD='...' \
#     ADMIN_EMAIL=you@example.com ADMIN_PASSWORD='...' bash ~/clasifide/4ulk/deploy/cpanel-deploy.sh
#
#   Every update after that:
#     cd ~/clasifide && git pull && bash 4ulk/deploy/cpanel-deploy.sh
#
# Layout it creates:
#   ~/clasifide/4ulk    the app (from git; .env lives here and is never committed)
#   ~/4ulk  ->          symlink to it, where public_html/index.php looks for the app
#   ~/public_html       web files copied from 4ulk/public (uploads/ stay here between updates)
#
# Optional: WEB_ROOT=~/public_html/addon-domain for an addon domain, PHP_BIN=/path/to/php82
set -euo pipefail

APP="$(cd "$(dirname "$0")/.." && pwd)"
WEB="${WEB_ROOT:-$HOME/public_html}"
WEB="${WEB%/}"
say() { printf '\n\033[1;35m==> %s\033[0m\n' "$*"; }
die() { printf '\n\033[1;31mERROR: %s\033[0m\n' "$*" >&2; exit 1; }

# ---------------------------------------------------------------- PHP 8.2+
say "Finding PHP 8.2+"
PHP=""
for c in "${PHP_BIN:-}" php /opt/cpanel/ea-php83/root/usr/bin/php /opt/cpanel/ea-php82/root/usr/bin/php /usr/local/bin/php; do
    [ -n "$c" ] && command -v "$c" >/dev/null 2>&1 && "$c" -r 'exit(PHP_VERSION_ID >= 80200 ? 0 : 1);' 2>/dev/null && { PHP="$c"; break; }
done
[ -n "$PHP" ] || die "No PHP 8.2+ found. Set it in cPanel → MultiPHP Manager, or run with PHP_BIN=/opt/cpanel/ea-php82/root/usr/bin/php"
echo "Using $PHP ($("$PHP" -r 'echo PHP_VERSION;'))"
MISSING="$("$PHP" -r 'echo implode(" ", array_filter(["pdo_mysql","mbstring","openssl","tokenizer","dom","xml","ctype","fileinfo","gd","bcmath","curl","filter","session"], fn ($e) => ! extension_loaded($e)));')"
[ -z "$MISSING" ] || die "PHP extensions turned off: $MISSING
Turn them on in cPanel → Select PHP Version → Extensions (tick each one; it saves automatically), then run this again."

# ---------------------------------------------------------------- Composer
say "Installing PHP libraries (composer)"
# Run composer with the PHP found above (the host's default `php` may be older)
COMPOSER="$HOME/bin/composer.phar"
if [ ! -f "$COMPOSER" ]; then
    COMPOSER=""
    for c in /opt/cpanel/composer/bin/composer "$(command -v composer 2>/dev/null || true)"; do
        # Only use it if it is a PHP file we can run with $PHP, not a shell wrapper
        [ -n "$c" ] && [ -f "$c" ] && head -c 300 "$c" | grep -q -e '<?php' -e 'env php' -e 'bin/php' && { COMPOSER="$c"; break; }
    done
fi
if [ -z "$COMPOSER" ]; then
    mkdir -p "$HOME/bin"
    curl -sS https://getcomposer.org/installer | "$PHP" -- --install-dir="$HOME/bin" --filename=composer.phar
    COMPOSER="$HOME/bin/composer.phar"
fi
RUN_COMPOSER=("$PHP" "$COMPOSER")
(cd "$APP" && COMPOSER_ALLOW_SUPERUSER=1 "${RUN_COMPOSER[@]}" install --no-dev --optimize-autoloader --no-interaction --no-progress)

# ---------------------------------------------------------------- .env
set_env() { # set_env KEY VALUE  (safe for any characters in VALUE)
    K="$1" V="$2" F="$APP/.env" "$PHP" -r '
        $f = getenv("F"); $k = getenv("K"); $v = getenv("V");
        $q = preg_match("/^[A-Za-z0-9_.\/:\-]*$/", $v) ? $v : "\"".addcslashes($v, "\"\\\$")."\"";
        $s = file_get_contents($f);
        $line = $k."=".$q;
        $s = preg_match("/^".preg_quote($k, "/")."=.*$/m", $s)
            ? preg_replace_callback("/^".preg_quote($k, "/")."=.*$/m", fn () => $line, $s, 1)
            : rtrim($s)."\n".$line."\n";
        file_put_contents($f, $s);'
}

if [ ! -f "$APP/.env" ]; then
    say "Creating 4ulk/.env"
    cp "$APP/.env.example" "$APP/.env"
    chmod 600 "$APP/.env"
    "$PHP" "$APP/artisan" key:generate --force
fi
for k in APP_URL DB_DATABASE DB_USERNAME DB_PASSWORD ADMIN_EMAIL ADMIN_PASSWORD; do
    [ -n "${!k:-}" ] && set_env "$k" "${!k}" && echo "set $k"
done
set_env PUBLIC_PATH "$WEB"
set_env INSTALL_KEY ""   # the web installer is not needed with terminal access

grep -qE '^DB_PASSWORD=.+' "$APP/.env" || die "DB_PASSWORD is empty in $APP/.env. Re-run with DB_DATABASE=... DB_USERNAME=... DB_PASSWORD='...'"

# ---------------------------------------------------------------- app link + web files
say "Linking the app and publishing web files to $WEB"
LINK="$(dirname "$WEB")/4ulk"
if [ -L "$LINK" ]; then
    ln -sfn "$APP" "$LINK"
elif [ -e "$LINK" ]; then
    die "$LINK already exists and is not a symlink (an old zip install?). Rename it, e.g. mv $LINK ${LINK}-old, then re-run."
else
    ln -s "$APP" "$LINK"
fi

mkdir -p "$WEB/uploads"
for f in index.html index.htm default.html; do
    [ -f "$WEB/$f" ] && mv "$WEB/$f" "$WEB/$f.bak" && echo "moved host placeholder $f to $f.bak"
done
# Keep the host's original .htaccess / .user.ini once, and carry over any
# cPanel/CloudLinux-generated blocks (PHP version handler, ini directives)
for f in .htaccess .user.ini; do
    [ -f "$WEB/$f" ] && [ ! -f "$WEB/$f.host-original" ] && ! grep -q '4U.lk' "$WEB/$f" && cp "$WEB/$f" "$WEB/$f.host-original"
done
HOST_BLOCKS=""
[ -f "$WEB/.htaccess" ] && HOST_BLOCKS="$(awk '/^# .*BEGIN .*(cPanel|CloudLinux|LiteSpeed|php)/{keep=1} keep{print} /^# .*END .*(cPanel|CloudLinux|LiteSpeed|php)/{keep=0}' "$WEB/.htaccess")"
rm -rf "$WEB/build"
# Everything in public/ except its index.php and uploads/ (user photos live only on the server)
(cd "$APP/public" && for item in $(ls -A); do
    case "$item" in index.php|uploads|storage) continue ;; esac
    cp -R "$item" "$WEB/"
done)
if [ -n "$HOST_BLOCKS" ]; then
    printf '\n%s\n' "$HOST_BLOCKS" >> "$WEB/.htaccess"
    echo "kept the host's generated .htaccess settings"
fi
cp "$APP/deploy/cpanel/index.php" "$WEB/index.php"
cp "$APP/deploy/cpanel/.user.ini" "$WEB/.user.ini"
rm -f "$WEB/setup-check.php"

# ---------------------------------------------------------------- permissions
chmod -R u+rwX,go+rX,go-w "$APP/storage" "$APP/bootstrap/cache" "$WEB/uploads"
chmod 600 "$APP/.env"

# ---------------------------------------------------------------- database
say "Migrating the database"
"$PHP" "$APP/artisan" config:clear >/dev/null
"$PHP" "$APP/artisan" down --retry=30 >/dev/null 2>&1 || true
trap '"$PHP" "$APP/artisan" up >/dev/null 2>&1 || true' EXIT
"$PHP" "$APP/artisan" migrate --force

if [ "$("$PHP" "$APP/artisan" tinker --execute='echo \App\Models\User::count();' 2>/dev/null | tail -n1)" = "0" ]; then
    say "Fresh database: adding categories, packages and the admin account"
    "$PHP" "$APP/artisan" db:seed --force
fi

# ---------------------------------------------------------------- caches
say "Caching config, routes and views"
"$PHP" "$APP/artisan" optimize:clear >/dev/null
"$PHP" "$APP/artisan" config:cache
"$PHP" "$APP/artisan" route:cache
"$PHP" "$APP/artisan" view:cache
"$PHP" "$APP/artisan" up

say "Done"
echo "Site:  $(grep -E '^APP_URL=' "$APP/.env" | cut -d= -f2-)"
echo "Admin: $(grep -E '^APP_URL=' "$APP/.env" | cut -d= -f2- | tr -d '"')/admin"
echo
echo "If not done yet, add this in cPanel → Cron Jobs (Once Per Hour):"
echo "  $PHP $APP/artisan schedule:run >> /dev/null 2>&1"
