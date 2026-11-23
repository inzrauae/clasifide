// 4U.lk service worker: installable app + works on a weak connection.
// Pages are always fetched fresh (they contain sign-in state and CSRF tokens);
// only static assets are cached.
const VERSION = '4ulk-v6';
const OFFLINE = '/offline.html';

self.addEventListener('install', (event) => {
    event.waitUntil(caches.open(VERSION).then((c) => c.addAll([OFFLINE, '/assets/brand/favicon.svg'])).then(() => self.skipWaiting()));
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys()
            .then((keys) => Promise.all(keys.filter((k) => k !== VERSION).map((k) => caches.delete(k))))
            .then(() => self.clients.claim())
    );
});

self.addEventListener('fetch', (event) => {
    const req = event.request;
    if (req.method !== 'GET') return;
    const url = new URL(req.url);

    if (req.mode === 'navigate') {
        event.respondWith(fetch(req).catch(() => caches.match(OFFLINE)));
        return;
    }

    const isAsset =
        (url.origin === self.location.origin && /^\/(build|assets|uploads)\//.test(url.pathname)) ||
        /(^|\.)(fonts\.googleapis\.com|fonts\.gstatic\.com|images\.unsplash\.com)$/.test(url.hostname);
    if (!isAsset) return;

    event.respondWith(
        caches.open(VERSION).then((cache) =>
            cache.match(req).then((hit) => {
                const net = fetch(req)
                    .then((res) => {
                        if (res && (res.ok || res.type === 'opaque')) cache.put(req, res.clone());
                        return res;
                    })
                    .catch(() => hit);
                return hit || net;
            })
        )
    );
});
