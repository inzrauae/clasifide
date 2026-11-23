"""Build the cPanel zip.

Layout (extract straight into the domain's folder, e.g. public_html):
    index.php, .htaccess, .user.ini, setup-check.php, build/, assets/, uploads/, sw.js ...
    4ulk/   <- the Laravel app (blocked from the web by .htaccess rules)
"""
import os, shutil, subprocess, sys, secrets, base64, zipfile, re

SRC = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
STAGE = sys.argv[1] if len(sys.argv) > 1 else os.path.join(os.path.dirname(SRC), "_cpanel_stage")
OUT = sys.argv[2] if len(sys.argv) > 2 else os.path.join(os.path.dirname(SRC), "4ulk-cpanel.zip")

if os.path.exists(STAGE):
    shutil.rmtree(STAGE)
os.makedirs(STAGE)
APP = os.path.join(STAGE, "4ulk")

SKIP_TOP = {"node_modules", ".git", "tests", ".env", ".phpunit.result.cache", "phpunit.xml", ".claude", ".vscode", ".idea", "deploy"}
RUNTIME = ("storage/logs", "storage/framework/sessions", "storage/framework/views", "storage/framework/cache", "storage/app/private", "storage/app/public", "public/uploads")

def ignore(d, names):
    rel = os.path.relpath(d, SRC).replace("\\", "/")
    out = set()
    for n in names:
        full = os.path.join(d, n)
        if rel == "." and n in SKIP_TOP: out.add(n)
        elif n.endswith((".zip", ".sqlite", ".sqlite-journal")): out.add(n)
        elif n == "hot" and rel == "public": out.add(n)
        elif rel.startswith(RUNTIME) and os.path.isfile(full) and n != ".gitignore": out.add(n)
        elif rel.startswith(("storage/framework/cache/data", "public/uploads")) and os.path.isdir(full): out.add(n)
        elif rel == "bootstrap/cache" and n.endswith(".php"): out.add(n)
    return out

print("copying...")
shutil.copytree(SRC, APP, ignore=ignore)
for d in ["storage/framework/cache/data", "storage/framework/sessions", "storage/framework/views", "storage/logs", "storage/app/public", "bootstrap/cache"]:
    os.makedirs(os.path.join(APP, d), exist_ok=True)

print("composer install --no-dev ...")
r = subprocess.run("composer install --no-dev --optimize-autoloader --no-interaction --no-progress", cwd=APP, shell=True, capture_output=True, text=True)
if r.returncode != 0:
    print(r.stdout[-2000:], r.stderr[-3000:]); sys.exit("composer failed")

# web root = zip root
for name in os.listdir(os.path.join(APP, "public")):
    shutil.move(os.path.join(APP, "public", name), os.path.join(STAGE, name))
os.rmdir(os.path.join(APP, "public"))
os.makedirs(os.path.join(STAGE, "uploads"), exist_ok=True)
for f in ["index.php", "setup-check.php", ".user.ini"]:
    shutil.copy(os.path.join(SRC, "deploy", "cpanel", f), os.path.join(STAGE, f))

# second lock on the app folder (in addition to the RewriteRule in the root .htaccess)
open(os.path.join(APP, ".htaccess"), "w", newline="\n").write(
    "<IfModule mod_authz_core.c>\n    Require all denied\n</IfModule>\n<IfModule !mod_authz_core.c>\n    Order deny,allow\n    Deny from all\n</IfModule>\n")

# production .env with fresh keys
env = open(os.path.join(SRC, ".env.example"), encoding="utf-8").read()
env = re.sub(r"^APP_KEY=.*$", "APP_KEY=base64:" + base64.b64encode(secrets.token_bytes(32)).decode(), env, flags=re.M)
env = re.sub(r"^INSTALL_KEY=.*$", "INSTALL_KEY=" + secrets.token_hex(16), env, flags=re.M)
open(os.path.join(APP, ".env.new"), "w", encoding="utf-8", newline="\n").write(env)
shutil.copy(os.path.join(SRC, "DEPLOY.md"), os.path.join(APP, "DEPLOY.md"))

# sanity: no Windows paths baked into PHP files
for root, _, files in os.walk(APP):
    if "node_modules" in root: continue
    for f in files:
        if f.endswith(".php") and ("bootstrap" in root or os.path.join("vendor", "composer") in root):
            s = open(os.path.join(root, f), encoding="utf-8", errors="ignore").read()
            if re.search(r"[A-Za-z]:\\\\Users|scratchpad", s):
                sys.exit("Windows path found in " + os.path.join(root, f))

# zip: forward slashes, explicit 0755 folders and 0644 files
if os.path.exists(OUT): os.remove(OUT)
n = 0
with zipfile.ZipFile(OUT, "w", zipfile.ZIP_DEFLATED, compresslevel=9) as z:
    for root, dirs, files in os.walk(STAGE):
        dirs.sort(); files.sort()
        rel = os.path.relpath(root, STAGE).replace("\\", "/")
        if rel != ".":
            info = zipfile.ZipInfo(rel + "/")
            info.external_attr = (0o40755 << 16) | 0x10
            z.writestr(info, "")
        for f in files:
            full = os.path.join(root, f)
            arc = f if rel == "." else f"{rel}/{f}"
            info = zipfile.ZipInfo.from_file(full, arc)
            info.external_attr = (0o100755 if f == "artisan" else 0o100644) << 16
            info.compress_type = zipfile.ZIP_DEFLATED
            with open(full, "rb") as fh:
                z.writestr(info, fh.read())
            n += 1
print("files:", n, "size MB:", round(os.path.getsize(OUT) / 1048576, 1))

# Usage: npm run build && python deploy/build-cpanel-zip.py
#   -> writes ../4ulk-cpanel.zip (delete ../_cpanel_stage afterwards)
