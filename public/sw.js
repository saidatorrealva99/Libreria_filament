/* Service worker de la tienda (PWA): permite instalarla como app
   y muestra una página amigable cuando no hay internet. */
const CACHE = 'libreria-v1';
const OFFLINE = '/offline.html';

self.addEventListener('install', (e) => {
    e.waitUntil(caches.open(CACHE).then((c) => c.addAll([OFFLINE, '/img/pwa/icono-192.png'])));
    self.skipWaiting();
});

self.addEventListener('activate', (e) => {
    e.waitUntil(caches.keys().then((keys) => Promise.all(keys.filter((k) => k !== CACHE).map((k) => caches.delete(k)))));
    self.clients.claim();
});

self.addEventListener('fetch', (e) => {
    // Solo páginas de la tienda: primero la red; si falla, la página sin conexión
    if (e.request.mode === 'navigate' && ! e.request.url.includes('/admin')) {
        e.respondWith(fetch(e.request).catch(() => caches.match(OFFLINE)));
    }
});
