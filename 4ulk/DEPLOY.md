# Deploying 4U.lk on cPanel

You need **PHP 8.2 or newer** and a **MySQL or MariaDB** database. Everything goes in **one folder**: your domain's document root.

- Main domain: the folder is usually `public_html`.
- Addon domain or subdomain: cPanel → **Domains** shows its document root, for example `public_html/4u.lk`.

After extracting, the folder looks like this:

```
public_html/
├── index.php          ← site entry
├── setup-check.php    ← diagnostics page (delete when live)
├── .htaccess  .user.ini
├── build/  assets/  uploads/  sw.js  manifest.webmanifest …
└── 4ulk/              ← the application + .env (locked: visitors get "403 Forbidden")
```

---

## 1. Set the PHP version
cPanel → **Select PHP Version** (or **MultiPHP Manager**) → choose **PHP 8.2** or **8.3** for the domain.
In **Extensions**, make sure these are ticked: `pdo_mysql`, `mbstring`, `openssl`, `tokenizer`, `dom`/`xml`, `ctype`, `fileinfo`, `gd`, `bcmath`, `curl`.

## 2. Create the database
cPanel → **MySQL® Databases**:
1. **Create New Database**, for example `4ulk`. cPanel names it `cpaneluser_4ulk`.
2. **Add New User** with a strong password, for example `cpaneluser_4ulk`.
3. **Add User To Database** → tick **ALL PRIVILEGES** → **Make Changes**.

Note the full database name, user name and password.

## 3. Upload and extract
cPanel → **File Manager** → open the domain's folder (`public_html`):
1. Delete any `index.html` or `default.html` the host placed there.
2. Click **Upload** and upload `4ulk-cpanel.zip`.
3. Right-click the zip → **Extract** → extract **into the same folder** (`/public_html`).
4. Check that `public_html/index.php` and `public_html/4ulk/` now both exist.
   If you see `public_html/4ulk-cpanel/…` or another extra folder level, move its contents up one level.
5. Delete the zip file.
6. Click **Settings** (top right) → tick **Show Hidden Files (dotfiles)**.

## 4. Edit the settings
In the `4ulk` folder, the settings file is called **`.env.new`** in a fresh upload. Right-click it → **Rename** → `.env` (it is also renamed automatically the first time you open the site). Then right-click `4ulk/.env` → **Edit**:

| Setting | Value |
|---|---|
| `APP_URL` | `http://yourdomain.lk` for now. Change it to `https://` after step 7. |
| `DB_DATABASE` / `DB_USERNAME` / `DB_PASSWORD` | the values from step 2 (with the `cpaneluser_` prefix) |
| `ADMIN_EMAIL` / `ADMIN_PASSWORD` | your admin login |
| `MAIL_*` | a cPanel email account, used for password-reset emails |
| `BANK_*`, `PAYHERE_*`, `SUPPORT_*` | your business details |

Do **not** change `APP_KEY`. Note your `INSTALL_KEY`; you need it in the next two steps.

## 5. Run the setup check
Open:

```
http://yourdomain.lk/setup-check.php?key=YOUR_INSTALL_KEY
```

It checks:
- the PHP version and extensions
- that the folders are writable
- the database login
- that `APP_URL` matches your domain

It also shows the latest error log. **Fix anything red**, then reload the page.

## 6. Create the tables and admin
On the setup-check page, click **Run installer**, or open `http://yourdomain.lk/install/YOUR_INSTALL_KEY`.
It creates all tables, categories and packages, and your admin account.

Then open the site. Sign in at `/login` and go to `/admin`.

**When everything works:**
- Edit `4ulk/.env` and delete the value after `INSTALL_KEY=`. This turns off both the installer and the check page.
- Delete `setup-check.php`.

## 7. Turn on HTTPS
1. cPanel → **SSL/TLS Status** → **Run AutoSSL**.
2. When the padlock works at `https://yourdomain.lk`, change `APP_URL` in `.env` to `https://yourdomain.lk`.
   Visitors on `http://` are then redirected to `https://` automatically.

## 8. Cron job (hourly housekeeping)
cPanel → **Cron Jobs** → **Once Per Hour**:

```
/usr/local/bin/php /home/CPANELUSER/public_html/4ulk/artisan schedule:run >> /dev/null 2>&1
```

Replace `CPANELUSER` with your cPanel user name, and adjust the folder for an addon domain.
If that PHP path fails, ask your host for the PHP 8.2 CLI path. It is often `/opt/cpanel/ea-php82/root/usr/bin/php`.

## 9. PayHere
In your PayHere account, set the **Notify URL** to `https://yourdomain.lk/payhere/notify`.

---

## Troubleshooting

| You see | Fix |
|---|---|
| "PHP 8.2 or newer is required" | Step 1 |
| "Application folder not found" | The `4ulk` folder isn't next to `index.php`. Redo step 3. |
| **500 error** / blank page | Open `setup-check.php?key=…`. The red items and the error log at the bottom show the cause. |
| **419 Page Expired** when signing in | `APP_URL` doesn't match the address in the browser (`http` vs `https`, `www` or not). |
| Site loads without colours or styling | `APP_URL` is wrong, or the `build/` folder is missing |
| "Too many redirects" | `APP_URL` is `https://` but SSL isn't active yet. Use `http://` until step 7. |
| Photos won't upload | Set `uploads/` to permission 755 |
| Database error 1045 "Access denied" | Wrong DB user or password, or the user wasn't added to the database (step 2.3) |

To see the full error once, set `APP_DEBUG=true` in `.env`, reload the page, then **set it back to `false`**.

## Updating later
Upload the new zip and extract it over the old files. Your `4ulk/.env` and `uploads/` are kept: the zip only contains `.env.new`, which is ignored once `.env` exists, so delete `.env.new` afterwards. Then set a new `INSTALL_KEY` and open `/install/THAT-KEY` to apply any database changes. Existing data is kept.
