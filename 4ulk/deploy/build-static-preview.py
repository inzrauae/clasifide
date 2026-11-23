"""Export the running site as a static HTML preview (no PHP, no database).

Usage:  php artisan serve --port=8000   (with demo data)
        python deploy/build-static-preview.py
Output: ../4ulk-static-preview.zip  – extract into public_html (or any folder) and open index.html

Three visits are recorded:
  guest  – public pages (home, ads, ad pages, stores, pricing …)
  seller – the demo business account (dashboard, leads, messages, post ad)
  admin  – the admin area
Forms and buttons that need a server are handled by a small preview script (see SHIM below).
"""
import hashlib, html, json, os, posixpath, re, shutil, sys, zipfile
from collections import deque
from urllib.parse import urlsplit, parse_qsl, urlencode, unquote
import requests

ORIGIN = "http://127.0.0.1:8000"
SRC = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
OUT_DIR = os.path.join(os.path.dirname(SRC), "_static_preview")
OUT_ZIP = os.path.join(os.path.dirname(SRC), "4ulk-static-preview.zip")

SELLER = ("negombo-auto-traders@demo.4u.lk", "password")
ADMIN = ("admin@4u.lk", os.environ.get("ADMIN_PASSWORD", "admin12345"))

SELLER_SCOPE = re.compile(r"^/(dashboard|messages|post)(/|$)|^/ad/\d+/(promote|edit)$")
ADMIN_SCOPE = re.compile(r"^/admin(/|$)")
NEVER = re.compile(r"^/(logout|install|payhere|checkout|b/|reset-password|subscribe|setup-check)|/(phone|report|favorite|chat|sold|renew|review|pay-fee)$|^/ad/\d+/whatsapp$")
ASSET = re.compile(r"^/(build|assets|uploads)/|^/(favicon\.ico|manifest\.webmanifest|robots\.txt|offline\.html|sw\.js)$")
MAX_PAGES = 700
MAX_QUERY_VARIANTS = 30


def key_of(path, query):
    q = [(k, v) for k, v in parse_qsl(query, keep_blank_values=False) if k not in ("_token",)]
    return path.rstrip("/") or "/", urlencode(sorted(q))


def file_of(path, query):
    parts = [p for p in path.strip("/").split("/") if p]
    if query:
        slug = re.sub(r"[^a-z0-9]+", "-", unquote(query).lower()).strip("-")[:50]
        parts.append(f"q-{slug}-{hashlib.md5(query.encode()).hexdigest()[:6]}")
    return "/".join(parts + ["index.html"])


def login(session, creds):
    r = session.get(ORIGIN + "/login")
    token = re.search(r'name="_token" value="([^"]+)"', r.text).group(1)
    r = session.post(ORIGIN + "/login", data={"_token": token, "login": creds[0], "password": creds[1]}, allow_redirects=False)
    assert r.status_code == 302 and "/login" not in r.headers.get("Location", ""), f"login failed for {creds[0]}"


pages = {}        # (path, query) -> {"file":…, "html":…}
whatsapp = {}     # path -> wa.me url
phones = {}       # listing id -> {"phone","tel"}
variants = {}


def crawl(session, starts, scope):
    queue = deque(starts)
    seen = set()
    while queue and len(pages) < MAX_PAGES:
        url = queue.popleft()
        u = urlsplit(url)
        path, query = key_of(u.path, u.query)
        k = (path, query)
        if k in seen or k in pages:
            continue
        seen.add(k)
        if NEVER.search(path) or ASSET.search(path) or not scope(path):
            continue
        if query:
            variants[path] = variants.get(path, 0) + 1
            if variants[path] > MAX_QUERY_VARIANTS:
                continue
        r = session.get(ORIGIN + path + ("?" + query if query else ""), allow_redirects=False)
        if r.status_code != 200 or "text/html" not in r.headers.get("Content-Type", ""):
            continue
        pages[k] = {"file": file_of(path, query), "html": r.text}
        for m in re.finditer(r'href="(http://127\.0\.0\.1:8000[^"#]*)"', r.text):
            queue.append(html.unescape(m.group(1))[len(ORIGIN):] or "/")
        print(f"  {len(pages):4d} {path}{'?' + query if query else ''}")


guest_scope = lambda p: not SELLER_SCOPE.search(p) and not ADMIN_SCOPE.search(p)

print("guest pages…")
guest = requests.Session()
crawl(guest, ["/", "/ads", "/pricing", "/advertise", "/safety", "/about", "/terms", "/privacy", "/login", "/register", "/forgot-password"], guest_scope)

print("seller pages…")
seller = requests.Session(); login(seller, SELLER)
crawl(seller, ["/dashboard", "/dashboard/ads", "/dashboard/saved", "/dashboard/leads", "/dashboard/billing", "/dashboard/profile", "/dashboard/store", "/messages", "/post"],
      lambda p: bool(SELLER_SCOPE.search(p)))

print("admin pages…")
admin = requests.Session(); login(admin, ADMIN)
crawl(admin, ["/admin"], lambda p: bool(ADMIN_SCOPE.search(p)))

# real phone numbers + WhatsApp targets for every ad page we saved
token_page = guest.get(ORIGIN + "/login").text
csrf = re.search(r'name="csrf-token" content="([^"]+)"', token_page).group(1)
for (path, _q) in list(pages):
    m = re.match(r"^/ad/(\d+)/", path)
    if m and m.group(1) not in phones:
        r = guest.post(f"{ORIGIN}/ad/{m.group(1)}/phone", headers={"X-CSRF-TOKEN": csrf, "Accept": "application/json"})
        if r.ok:
            phones[m.group(1)] = r.json()
        r = guest.get(f"{ORIGIN}/ad/{m.group(1)}/whatsapp", allow_redirects=False)
        if r.status_code in (301, 302):
            whatsapp[f"/ad/{m.group(1)}/whatsapp"] = r.headers["Location"]

SHIM = """
<script>
/* 4U.lk static preview: stands in for the server */
(function () {
  var ROOT = %ROOT%, PHONES = %PHONES%;
  function toast(msg) {
    try { window.Alpine.store('toast').show(msg); } catch (e) { alert(msg); }
  }
  var realFetch = window.fetch;
  window.fetch = function (url, opts) {
    url = String(url);
    if (url.indexOf('demo:') !== -1 || (opts && opts.method && opts.method.toUpperCase() !== 'GET')) {
      var m = url.match(/ad\\/(\\d+)\\/phone/);
      var body = m && PHONES[m[1]] ? PHONES[m[1]] : { saved: true, ok: true };
      return Promise.resolve(new Response(JSON.stringify(body), { headers: { 'Content-Type': 'application/json' } }));
    }
    return realFetch.apply(this, arguments);
  };
  document.addEventListener('submit', function (e) {
    var f = e.target, a = f.getAttribute('data-demo-action') || '';
    e.preventDefault();
    if (/^\\/(login|register)$/.test(a)) { location.href = ROOT + 'dashboard/index.html'; return; }
    if (/^\\/ads$/.test(a)) { location.href = ROOT + 'ads/index.html'; return; }
    if (/^\\/post$/.test(a)) { location.href = ROOT + 'dashboard/ads/index.html'; return; }
    if (/^\\/logout$/.test(a)) { location.href = ROOT + 'index.html'; return; }
    toast('Preview only: this works on the live site.');
  }, true);
  document.addEventListener('click', function (e) {
    var l = e.target.closest && e.target.closest('a[href^="#demo:"]');
    if (l) { e.preventDefault(); toast('Preview only: this works on the live site.'); }
  }, true);
  HTMLFormElement.prototype.submit = function () { this.dispatchEvent(new Event('submit', { cancelable: true, bubbles: true })); };
})();
</script>
"""

# matches http://127.0.0.1:8000/path?query#frag and the JSON-escaped form http:\/\/127.0.0.1:8000\/path
URL_RE = re.compile(r"http:(?:\\?/){2}127\.0\.0\.1:8000((?:\\?/[^\"'\s<>)\\?#&\x01]*)*)(\?[^\"'\s<>#\x01]*)?(#[^\"'\s<>\x01]*)?")


def rewrite(page_file, text):
    here = posixpath.dirname(page_file)
    root = posixpath.relpath(".", here) + "/" if here else ""
    if root == "./":
        root = ""

    def rel(target):
        return posixpath.relpath(target, here or ".")

    # forms: keep the original path for the preview script, never submit anywhere
    def form(m):
        action = html.unescape(m.group(2))[len(ORIGIN):] or "/"
        return f'{m.group(1)}action="#" data-demo-action="{html.escape(urlsplit(action).path)}"'
    text = re.sub(r'(<form\b[^>]*?)action="(http://127\.0\.0\.1:8000[^"]*)"', form, text)

    def url(m):
        escaped = "\\/" in m.group(0)
        path = (m.group(1) or "/").replace("\\/", "/") or "/"
        query = html.unescape((m.group(2) or "")[1:])
        frag = m.group(3) or ""
        if path in whatsapp:
            out = whatsapp[path]
        elif ASSET.search(path):
            out = rel(path.lstrip("/")) + (("?" + query) if query else "")
        else:
            k = key_of(path, query)
            if k not in pages and (path, "") in pages:
                k = (path, "")  # an unsaved filter combination: fall back to the plain list
            out = (rel(pages[k]["file"]) + frag) if k in pages else "#demo:" + path
        if escaped:
            out = out.replace("/", "\\/")
        return out.replace("&", "&amp;") if not escaped else out
    # Blade escapes ' as &#039; – keep it out of the URL matcher, then restore it
    text = URL_RE.sub(url, text.replace("&#039;", "\x01")).replace("\x01", "&#039;")
    shim = SHIM.replace("%ROOT%", json.dumps(root)).replace("%PHONES%", json.dumps(phones))
    return text.replace("</body>", shim + "</body>", 1)


print("writing files…")
os.makedirs(OUT_DIR, exist_ok=True)
for name in os.listdir(OUT_DIR):  # empty it (the folder itself may be open in Explorer/terminal)
    full = os.path.join(OUT_DIR, name)
    shutil.rmtree(full) if os.path.isdir(full) else os.remove(full)
for (path, q), p in pages.items():
    dest = os.path.join(OUT_DIR, p["file"])
    os.makedirs(os.path.dirname(dest), exist_ok=True)
    with open(dest, "w", encoding="utf-8", newline="\n") as fh:
        fh.write(rewrite(p["file"], p["html"]))

pub = os.path.join(SRC, "public")
for d in ["build", "assets"]:
    shutil.copytree(os.path.join(pub, d), os.path.join(OUT_DIR, d))
for f in ["favicon.ico", "manifest.webmanifest", "robots.txt", "offline.html"]:
    shutil.copy(os.path.join(pub, f), os.path.join(OUT_DIR, f))
# switch off any service worker from an earlier visit
open(os.path.join(OUT_DIR, "sw.js"), "w").write("self.addEventListener('install',()=>self.skipWaiting());self.addEventListener('activate',()=>self.registration.unregister());\n")
open(os.path.join(OUT_DIR, "404.html"), "w", encoding="utf-8").write(rewrite("404.html", pages[("/", "")]["html"]))
open(os.path.join(OUT_DIR, ".htaccess"), "w").write("DirectoryIndex index.html\nErrorDocument 404 /404.html\nOptions -Indexes\n")

if os.path.exists(OUT_ZIP):
    os.remove(OUT_ZIP)
with zipfile.ZipFile(OUT_ZIP, "w", zipfile.ZIP_DEFLATED, compresslevel=9) as z:
    for root_dir, dirs, files in os.walk(OUT_DIR):
        rel_dir = os.path.relpath(root_dir, OUT_DIR).replace("\\", "/")
        for f in sorted(files):
            arc = f if rel_dir == "." else f"{rel_dir}/{f}"
            info = zipfile.ZipInfo.from_file(os.path.join(root_dir, f), arc)
            info.external_attr = 0o100644 << 16
            info.compress_type = zipfile.ZIP_DEFLATED
            with open(os.path.join(root_dir, f), "rb") as fh:
                z.writestr(info, fh.read())
print(f"done: {len(pages)} pages -> {OUT_ZIP} ({round(os.path.getsize(OUT_ZIP) / 1048576, 1)} MB)")
