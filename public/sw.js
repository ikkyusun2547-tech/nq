// Minimal service worker: only exists to satisfy the browser's PWA
// installability requirement and speed up repeat loads of static,
// content-hashed assets. Everything else (pages, check-in, QR tokens,
// GPS submission) goes straight to the network — this app is too dynamic
// for a cached response to ever be safe to serve.
const CACHE_NAME = 'srru-check-static-v1';
const STATIC_PATH_PREFIXES = ['/build/', '/images/'];

self.addEventListener('install', (event) => {
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((keys) => Promise.all(
            keys.filter((key) => key !== CACHE_NAME).map((key) => caches.delete(key))
        )).then(() => self.clients.claim())
    );
});

self.addEventListener('fetch', (event) => {
    const { request } = event;

    if (request.method !== 'GET') {
        return;
    }

    const url = new URL(request.url);

    if (url.origin !== self.location.origin) {
        return;
    }

    const isStaticAsset = STATIC_PATH_PREFIXES.some((prefix) => url.pathname.startsWith(prefix));
    if (! isStaticAsset) {
        return;
    }

    event.respondWith(
        caches.open(CACHE_NAME).then(async (cache) => {
            const cached = await cache.match(request);
            if (cached) {
                return cached;
            }

            const response = await fetch(request);
            if (response.ok) {
                cache.put(request, response.clone());
            }

            return response;
        })
    );
});
