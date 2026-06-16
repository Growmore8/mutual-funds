// GrowthCapital Funds — minimal service worker (enables PWA install).
// Network-first; falls back to cache only for the offline shell.
const CACHE = 'gc-funds-v2';
const OFFLINE_URLS = ['/offline.html', '/logo.png'];

self.addEventListener('install', (event) => {
    event.waitUntil(caches.open(CACHE).then((c) => c.addAll(OFFLINE_URLS)));
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((keys) => Promise.all(keys.filter((k) => k !== CACHE).map((k) => caches.delete(k))))
    );
    self.clients.claim();
});

self.addEventListener('fetch', (event) => {
    const req = event.request;
    // Only handle GET navigations; never cache auth/POST or cross-origin.
    if (req.method !== 'GET') return;

    if (req.mode === 'navigate') {
        event.respondWith(
            fetch(req).catch(() => caches.match('/offline.html'))
        );
        return;
    }

    // Static same-origin assets: cache-first with network fallback.
    const url = new URL(req.url);
    if (url.origin === self.location.origin && /\.(css|js|svg|png|ico|woff2?)$/.test(url.pathname)) {
        event.respondWith(
            caches.match(req).then((hit) => hit || fetch(req).then((res) => {
                const copy = res.clone();
                caches.open(CACHE).then((c) => c.put(req, copy));
                return res;
            }))
        );
    }
});
