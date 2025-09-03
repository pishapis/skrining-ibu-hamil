/* global workbox */
importScripts('https://storage.googleapis.com/workbox-cdn/releases/6.6.0/workbox-sw.js');

// Segera aktifkan SW baru
self.addEventListener('install', () => self.skipWaiting());
self.addEventListener('activate', (e) => e.waitUntil(self.clients.claim()));

// HTML/pages → Network First (hindari cache agresif untuk Swup)
workbox.routing.registerRoute(
  ({request}) => request.destination === 'document',
  new workbox.strategies.NetworkFirst({
    cacheName: 'pages',
    networkTimeoutSeconds: 5,
    plugins: [new workbox.expiration.ExpirationPlugin({ maxEntries: 50, purgeOnQuotaError: true })]
  })
);

// Aset statis → Stale-While-Revalidate
workbox.routing.registerRoute(
  ({request}) => ['script','style','image','font'].includes(request.destination),
  new workbox.strategies.StaleWhileRevalidate({
    cacheName: 'assets',
    plugins: [new workbox.expiration.ExpirationPlugin({ maxEntries: 200 })]
  })
);

// Offline fallback
const OFFLINE_URL = '/offline';
workbox.precaching.precacheAndRoute([{url: OFFLINE_URL, revision: null}]);
workbox.routing.setCatchHandler(async ({event}) => {
  if (event.request.destination === 'document') return caches.match(OFFLINE_URL);
  return Response.error();
});