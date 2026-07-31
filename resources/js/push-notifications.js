import { initializeApp } from 'firebase/app';
import { getMessaging, getToken, onMessage } from 'firebase/messaging';

// Same four values as public/firebase-messaging-sw.js, sourced from Vite's
// env here since this file (unlike the service worker) goes through the
// bundler. All four are safe to ship client-side — see the comment on
// VITE_FIREBASE_API_KEY in .env.example for why.
const firebaseConfig = {
    apiKey: import.meta.env.VITE_FIREBASE_API_KEY,
    projectId: import.meta.env.VITE_FIREBASE_PROJECT_ID,
    messagingSenderId: import.meta.env.VITE_FIREBASE_MESSAGING_SENDER_ID,
    appId: import.meta.env.VITE_FIREBASE_APP_ID,
};

/** Whether the Firebase Web SDK has actually been configured yet. The VAPID
 * key specifically (rather than just apiKey/appId) is what getToken() below
 * needs to actually mint a token — as of 2026-07-31 the Web app is
 * registered but the VAPID key hasn't been generated yet (Project settings
 * > Cloud Messaging > Web Push certificates), so this still (correctly)
 * evaluates to false until that's done. Checked before doing anything else
 * so an unconfigured deploy fails as a clear early return instead of an
 * inscrutable Firebase SDK error. */
function isConfigured() {
    return Boolean(firebaseConfig.apiKey && firebaseConfig.appId && import.meta.env.VITE_FIREBASE_VAPID_KEY);
}

function isSupported() {
    return 'Notification' in window && 'serviceWorker' in navigator && 'PushManager' in window;
}

/** Read by the dismissible banner partial to decide whether to show itself
 * at all — no point offering a button that can only fail. */
function webPushStatus() {
    if (!isSupported()) return 'unsupported';
    if (!isConfigured()) return 'unconfigured';

    return Notification.permission; // 'default' | 'granted' | 'denied'
}

/** Registers this browser's FCM token and wires up the foreground message
 * listener. Split out from enableWebPush() so it can also run
 * unconditionally on every page load (see the bottom of this file) when
 * permission was already granted in an earlier visit — without this, the
 * listener registered by an *earlier* enableWebPush() call is gone the
 * moment the page reloads (it's in-memory JS state, not persisted), and
 * since the banner only ever shows when permission is still 'default', a
 * returning student would have a registered token but nothing locally
 * listening for messages until they somehow re-triggered enableWebPush(). */
async function setupMessaging() {
    // /sw.js, not a separate firebase-messaging-sw.js — a service worker
    // registration is keyed by (origin, scope), so two different scripts
    // registered at the same default scope don't coexist; the messaging
    // handler lives in sw.js itself now (see that file's top-of-file
    // comment). pwa-head.blade.php registers this same URL for asset
    // caching — re-registering it here is a no-op if it's already active.
    await navigator.serviceWorker.register('/sw.js');

    // register() resolves as soon as the registration object exists — the
    // worker itself can still be installing/waiting at that point, and
    // PushManager.subscribe() (inside getToken() below) throws AbortError
    // ("no active Service Worker") if called before it's actually active.
    // .ready resolves only once a worker is active and controlling this
    // page, which sw.js's self.skipWaiting() + self.clients.claim() make
    // happen quickly even on a first-ever registration.
    const registration = await navigator.serviceWorker.ready;
    const app = initializeApp(firebaseConfig);
    const messaging = getMessaging(app);

    const token = await getToken(messaging, {
        vapidKey: import.meta.env.VITE_FIREBASE_VAPID_KEY,
        serviceWorkerRegistration: registration,
    });
    if (!token) return { ok: false, reason: 'no-token' };

    await fetch('/device-token', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
        },
        body: JSON.stringify({ token, platform: 'web' }),
    });

    // FCM only auto-displays a system notification for background/
    // terminated pages (handled in firebase-messaging-sw.js); while this
    // tab is open it hands the message here instead, so we show it
    // ourselves — otherwise a message that arrives while the student has
    // this page open would be silently dropped.
    onMessage(messaging, (payload) => {
        window.dispatchEvent(new CustomEvent('push-notification-received'));

        // hasFocus() (not visibilityState) — visibilityState only tracks
        // whether the tab is hidden/minimized, so it stays 'visible' even
        // when the user has clicked into DevTools or another panel of the
        // same browser window without actually looking at the page.
        // hasFocus() asks the real question: does this document currently
        // have keyboard/window focus at all.
        if (document.hasFocus()) return;

        new Notification(payload.notification?.title ?? '', {
            body: payload.notification?.body,
            icon: '/images/icons/icon-192.png',
        });
    });

    return { ok: true };
}

/** Called by the dismissible banner's "เปิดใช้งาน" button — the
 * permission-request path, only reachable while permission is still
 * 'default'. */
export async function enableWebPush() {
    if (!isSupported()) return { ok: false, reason: 'unsupported' };
    if (!isConfigured()) return { ok: false, reason: 'unconfigured' };

    const permission = await Notification.requestPermission();
    if (permission !== 'granted') return { ok: false, reason: 'denied' };

    return setupMessaging();
}

window.enableWebPush = enableWebPush;
window.webPushStatus = webPushStatus;

// Re-establish the foreground listener (and refresh the token, in case it
// rotated) on every page load for a browser that already granted
// permission in an earlier visit — see setupMessaging()'s comment for why
// this can't just rely on enableWebPush() having run once.
if (isSupported() && isConfigured() && Notification.permission === 'granted') {
    setupMessaging();
}
