// Handles two unrelated jobs in one worker (rather than a separate
// firebase-messaging-sw.js, which Firebase's docs suggest as the default
// pattern): PWA static-asset caching, and FCM push. They have to share this
// one file — a service worker registration is keyed by (origin, scope), so
// two scripts registered at the same default scope ('/') don't coexist;
// whichever activates last wins and silently becomes the sole controller,
// which is exactly the bug this merge fixes (push messages were arriving
// at this file with no listener for them, since it used to only handle
// install/activate/fetch).
// Bump the version whenever a file under /images/ is replaced in place under
// the same name (e.g. the app icons): images are served cache-first, so
// otherwise installed apps keep the old copy forever. activate() below
// deletes every cache that isn't this one.
const CACHE_NAME = 'srru-check-static-v2';
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

// --- FCM web push -----------------------------------------------------
//
// Config values below are NOT secret (Firebase's web config is meant to be
// public/client-visible — access control is enforced server-side via
// Firebase Security Rules, not by hiding these), so hardcoding them here is
// the standard/documented pattern. They can't be read from Vite's
// import.meta.env like resources/js/push-notifications.js does, because
// this file is served as-is from public/ rather than bundled.
//
// Same four values as VITE_FIREBASE_* in .env — Web app registered in
// Firebase console 2026-07-31 (keep both copies in sync if this ever
// rotates).
importScripts('https://www.gstatic.com/firebasejs/12.0.0/firebase-app-compat.js');
importScripts('https://www.gstatic.com/firebasejs/12.0.0/firebase-messaging-compat.js');

firebase.initializeApp({
    apiKey: 'AIzaSyDF38dx0LB96zi7eh48gCFpOsZVTiU4Sh8',
    projectId: 'srru-student-activity',
    messagingSenderId: '117414332519',
    appId: '1:117414332519:web:af2c1fe8b445820e80ecc2',
});
console.log('[sw.js] firebase initialized, messaging ready');

const messaging = firebase.messaging();

// Diagnostic only — logs the raw push event regardless of whether
// Firebase's own internal listener (registered by onBackgroundMessage
// below) manages to parse it. Both listeners fire independently; this one
// existing doesn't change what the other does.
self.addEventListener('push', (event) => {
    console.log('[sw.js] raw push event received, data:', event.data?.text());
});

// Registering this handler ourselves (rather than relying on FCM's
// auto-display) means we control exactly what ends up in
// event.notification.data below — payload.data is the {icon, url} object
// App\Notifications\Channels\FcmChannel sent, so `.url` is reliably there
// in the click handler instead of guessed at from FCM's internal payload
// shape.
messaging.onBackgroundMessage((payload) => {
    console.log('[sw.js] onBackgroundMessage fired:', payload);
    self.registration.showNotification(payload.notification?.title ?? '', {
        body: payload.notification?.body,
        icon: '/images/icons/icon-192.png',
        data: payload.data,
    });
});

// Routes a click to the right in-app URL, same as the mobile app's
// _routeToUrl in push_notifications.dart.
self.addEventListener('notificationclick', (event) => {
    event.notification.close();

    const url = event.notification.data?.url;
    if (!url) return;

    event.waitUntil(
        self.clients.matchAll({ type: 'window', includeUncontrolled: true }).then((clients) => {
            for (const client of clients) {
                if ('focus' in client) {
                    client.postMessage({ type: 'push-notification-click', url });
                    return client.focus();
                }
            }
            return self.clients.openWindow(url);
        })
    );
});
