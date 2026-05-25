importScripts('https://storage.googleapis.com/workbox-cdn/releases/6.5.4/workbox-sw.js');

if (workbox) {
  console.log('Workbox is loaded');
  workbox.core.skipWaiting();
  workbox.core.clientsClaim();

  // Precaching the offline page
  workbox.precaching.precacheAndRoute([
    { url: '/offline.html', revision: '1' }
  ]);

  // Cache static assets (CSS, JS, Fonts)
  workbox.routing.registerRoute(
    ({ request }) => ['style', 'script', 'font'].includes(request.destination),
    new workbox.strategies.CacheFirst({
      cacheName: 'timeflow-assets',
    })
  );

  // Cache images
  workbox.routing.registerRoute(
    ({ request }) => request.destination === 'image',
    new workbox.strategies.CacheFirst({
      cacheName: 'timeflow-images',
    })
  );

  // API Requests - Network First
  workbox.routing.registerRoute(
    ({ url }) => url.pathname.startsWith('/api/'),
    new workbox.strategies.NetworkFirst({
      cacheName: 'timeflow-api',
      networkTimeoutSeconds: 3,
    })
  );

  // Navigation Requests - Network First
  workbox.routing.registerRoute(
    ({ request }) => request.mode === 'navigate',
    new workbox.strategies.NetworkFirst({
      cacheName: 'timeflow-pages',
    })
  );

  // Background Sync for POST requests (Timer stop and Habit toggle)
  const bgSyncPlugin = new workbox.backgroundSync.BackgroundSyncPlugin('timeflow-bg-sync', {
    maxRetentionTime: 24 * 60 // Retry for max of 24 Hours
  });

  workbox.routing.registerRoute(
    ({ url, request }) => request.method === 'POST' && (url.pathname.includes('/sessions') || url.pathname.includes('/goals')),
    new workbox.strategies.NetworkOnly({
      plugins: [bgSyncPlugin]
    }),
    'POST'
  );

  // Custom offline fallback page (P4.6)
  workbox.routing.setCatchHandler(async ({ event }) => {
    if (event.request.mode === 'navigate') {
      return caches.match('/offline.html');
    }
    return Response.error();
  });
}

self.addEventListener('message', (event) => {
  const payload = event.data || {};
  if (payload.type !== 'show-notification') return;

  const title = payload.title || 'TimeFlow';
  const options = {
    body: payload.body || 'Time to check in.',
    tag: payload.tag || 'timeflow-reminder',
  };

  event.waitUntil(self.registration.showNotification(title, options));
});
