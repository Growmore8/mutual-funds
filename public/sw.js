// GrowthCapital Funds — minimal service worker (enables PWA install).
// Cache-first for immutable hashed build assets; network-first for navigations.
const CACHE = 'gc-funds-v10';

// The page asks us to activate the new version when the user taps "Update".
self.addEventListener('message', (event) => {
    if (event.data === 'SKIP_WAITING') self.skipWaiting();
});

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
    // Do NOT skipWaiting here — we wait so the app can show an "Update available" prompt.
    event.waitUntil(caches.open(CACHE).then((c) => c.addAll(OFFLINE_URLS)));
});

self.addEventListener('activate', (event) => {
    event.waitUntil((async () => {
        // Navigation Preload: fetch the page in parallel with SW startup — kills the
        // "tap → pause → page" lag on mobile PWAs (SW cold-start no longer blocks nav).
        if (self.registration.navigationPreload) {
            try { await self.registration.navigationPreload.enable(); } catch (e) {}
        }
        const keys = await caches.keys();
        await Promise.all(keys.filter((k) => k !== CACHE).map((k) => caches.delete(k)));
        await self.clients.claim();
    })());
});

self.addEventListener('fetch', (event) => {
    const req = event.request;
    // Only handle GET navigations; never cache auth/POST or cross-origin.
    if (req.method !== 'GET') return;

    if (req.mode === 'navigate') {
        event.respondWith((async () => {
            try {
                const pre = await event.preloadResponse;   // started before the SW woke up
                if (pre) return pre;
                return await fetch(req);
            } catch (e) {
                return caches.match('/offline.html');
            }
        })());
        return;
    }

    const url = new URL(req.url);
    if (url.origin !== self.location.origin) return;

    // Immutable hashed build assets (/build/...): CACHE-FIRST — instant, no network wait.
    if (url.pathname.startsWith('/build/')) {
        event.respondWith(
            caches.match(req).then((hit) => hit || fetch(req).then((res) => {
                const copy = res.clone();
                caches.open(CACHE).then((c) => c.put(req, copy));
                return res;
            }))
        );
        return;
    }

    // Other static assets (logo, fonts, icons): stale-while-revalidate — serve cache fast, refresh in bg.
    if (/\.(svg|png|ico|woff2?|jpg|jpeg|webp)$/.test(url.pathname)) {
        event.respondWith(
            caches.match(req).then((hit) => {
                const net = fetch(req).then((res) => {
                    const copy = res.clone();
                    caches.open(CACHE).then((c) => c.put(req, copy));
                    return res;
                }).catch(() => hit);
                return hit || net;
            })
        );
    }
});
