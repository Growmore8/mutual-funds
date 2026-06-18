// GrowthCapital Funds — minimal service worker (enables PWA install).
// Network-first; falls back to cache only for the offline shell.
const CACHE = 'gc-funds-v6';

// Web push: show the notification when one arrives.
self.addEventListener('push', (event) => {
    let data = {};
    try { data = event.data ? event.data.json() : {}; } catch (e) { data = { body: event.data && event.data.text() }; }
    const title = data.title || 'GrowthCapital';
    event.waitUntil(self.registration.showNotification(title, {
        body: data.body || '',
        icon: '/logo.png',
        badge: '/logo.png',
        data: { url: data.url || '/app' },
    }));
});

// Tapping a notification focuses/opens the app at the right page.
self.addEventListener('notificationclick', (event) => {
    event.notification.close();
    const url = (event.notification.data && event.notification.data.url) || '/app';
    event.waitUntil(clients.matchAll({ type: 'window', includeUncontrolled: true }).then((list) => {
        for (const c of list) { if ('focus' in c) { c.navigate(url); return c.focus(); } }
        if (clients.openWindow) return clients.openWindow(url);
    }));
});
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

    // Static same-origin assets: network-first so new builds always win,
    // falling back to cache only when offline. (Cache-first served stale CSS/JS.)
    const url = new URL(req.url);
    if (url.origin === self.location.origin && /\.(css|js|svg|png|ico|woff2?)$/.test(url.pathname)) {
        event.respondWith(
            fetch(req).then((res) => {
                const copy = res.clone();
                caches.open(CACHE).then((c) => c.put(req, copy));
                return res;
            }).catch(() => caches.match(req))
        );
    }
});
