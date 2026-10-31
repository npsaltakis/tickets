/*
 * Service worker: offline page, cached static assets (ours + cdnjs) and an offline copy of My Tickets,
 * so a ticket QR code still opens at the entrance without signal.
 * Bump VERSION to drop the old static caches after changing this file.
 */
const VERSION = 'v1';
const STATIC_CACHE = 'static-' + VERSION;
const PAGES_CACHE = 'pages-' + VERSION;
// Not versioned: assets/js/pwa.js deletes it when nobody or another account is signed in.
const USER_CACHE = 'user-tickets';
const STATIC_LIMIT = 80;

const SCOPE = self.registration.scope;
const OFFLINE_URL = SCOPE + 'offline';
const TICKETS_URL = SCOPE + 'my-events';
const CDN_HOSTS = ['cdnjs.cloudflare.com'];

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(PAGES_CACHE)
            .then((cache) => cache.add(new Request(OFFLINE_URL, { cache: 'reload' })))
            .then(() => self.skipWaiting())
    );
});

self.addEventListener('activate', (event) => {
    const keep = [STATIC_CACHE, PAGES_CACHE, USER_CACHE];
    event.waitUntil(
        caches.keys()
            .then((keys) => Promise.all(keys.filter((key) => !keep.includes(key)).map((key) => caches.delete(key))))
            .then(() => self.clients.claim())
    );
});

self.addEventListener('fetch', (event) => {
    const request = event.request;
    if (request.method !== 'GET') {
        return;
    }

    const url = new URL(request.url);

    if (request.mode === 'navigate') {
        event.respondWith(handleNavigation(request, url));
        return;
    }

    const isOwnAsset = url.origin === self.location.origin && url.href.startsWith(SCOPE + 'assets/');
    if (isOwnAsset || CDN_HOSTS.includes(url.hostname)) {
        event.respondWith(staleWhileRevalidate(event, request));
    }
});

// Pages always come from the network; only My Tickets is kept, as the fallback when offline.
async function handleNavigation(request, url) {
    const isTickets = url.origin + url.pathname === TICKETS_URL;

    try {
        const response = await fetch(request);
        if (isTickets) {
            if (response.ok && !response.redirected) {
                const cache = await caches.open(USER_CACHE);
                await cache.put(TICKETS_URL, response.clone());
            } else if (response.redirected) {
                // Signed out (sent to the login page): the saved copy is no longer this visitor's.
                await caches.delete(USER_CACHE);
            }
        }

        return response;
    } catch (error) {
        if (isTickets) {
            const saved = await caches.match(TICKETS_URL, { cacheName: USER_CACHE });
            if (saved) {
                return saved;
            }
        }

        return (await caches.match(OFFLINE_URL, { cacheName: PAGES_CACHE })) || Response.error();
    }
}

async function staleWhileRevalidate(event, request) {
    const cache = await caches.open(STATIC_CACHE);
    const cached = await cache.match(request);

    const network = fetch(request)
        .then(async (response) => {
            // Cross-origin scripts come back opaque (status 0); they are still usable from the cache.
            if (response.ok || response.type === 'opaque') {
                await cache.put(request, response.clone());
                await trimCache(cache);
            }
            return response;
        })
        .catch(() => cached || Response.error());

    if (cached) {
        event.waitUntil(network);
        return cached;
    }

    return network;
}

// Versioned asset URLs (?v=mtime) pile up over time; keep the newest entries only.
async function trimCache(cache) {
    const keys = await cache.keys();
    for (let i = 0; i < keys.length - STATIC_LIMIT; i++) {
        await cache.delete(keys[i]);
    }
}
