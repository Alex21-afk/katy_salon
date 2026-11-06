const CACHE_NAME = 'isabella-studio-v9';
const APP_SHELL = ['./assets/css/app.css', './assets/js/app.js', './assets/js/admin.js'];

self.addEventListener('install', (event) => {
  event.waitUntil(caches.open(CACHE_NAME).then((cache) => cache.addAll(APP_SHELL)));
  self.skipWaiting();
});

self.addEventListener('activate', (event) => {
  event.waitUntil(caches.keys().then((keys) => Promise.all(keys.filter((key) => key !== CACHE_NAME).map((key) => caches.delete(key)))));
  self.clients.claim();
});

const offlineResponse = () => new Response('Sin conexión. Revisa tu internet e inténtalo nuevamente.', {
  status: 503,
  headers: { 'Content-Type': 'text/plain; charset=utf-8' },
});

self.addEventListener('fetch', (event) => {
  const request = event.request;
  if (request.method !== 'GET' || new URL(request.url).origin !== self.location.origin) return;
  event.respondWith(fetch(request).catch(() => caches.match(request).then((cached) => cached || offlineResponse())));
});
