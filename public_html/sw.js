var CACHE_NAME = 'cards-v2';
var STATIC_ASSETS = [
  '/',
  '/assets/css/style.css',
  '/assets/js/app.js',
  '/assets/js/scanner.js',
  '/manifest.json'
];

self.addEventListener('install', function (e) {
  e.waitUntil(
    caches.open(CACHE_NAME).then(function (cache) {
      return cache.addAll(STATIC_ASSETS);
    })
  );
  self.skipWaiting();
});

self.addEventListener('activate', function (e) {
  e.waitUntil(
    caches.keys().then(function (names) {
      return Promise.all(
        names.filter(function (n) { return n !== CACHE_NAME; })
             .map(function (n) { return caches.delete(n); })
      );
    })
  );
  self.clients.claim();
});

self.addEventListener('fetch', function (e) {
  var url = new URL(e.request.url);

  // Only handle same-origin GET requests
  if (e.request.method !== 'GET' || url.origin !== self.location.origin) return;

  // Skip API requests from cache-first — always network-first
  // Static assets: network-first, cache as fallback for offline
  if (url.pathname.startsWith('/assets/')) {
    e.respondWith(
      fetch(e.request).then(function (resp) {
        var clone = resp.clone();
        caches.open(CACHE_NAME).then(function (c) { c.put(e.request, clone); });
        return resp;
      }).catch(function () {
        return caches.match(e.request);
      })
    );
    return;
  }

  // HTML pages & API: network-first, fallback to cache
  e.respondWith(
    fetch(e.request).then(function (resp) {
      // Don't cache redirects (auth redirects to /login)
      if (resp.redirected || !resp.ok) return resp;
      var clone = resp.clone();
      caches.open(CACHE_NAME).then(function (c) { c.put(e.request, clone); });
      return resp;
    }).catch(function () {
      return caches.match(e.request).then(function (cached) {
        if (cached) return cached;
        // If navigating and no cache, show offline fallback
        if (e.request.headers.get('accept') && e.request.headers.get('accept').includes('text/html')) {
          return caches.match('/');
        }
      });
    })
  );
});

// Listen for pre-cache messages from app.js
self.addEventListener('message', function (e) {
  if (e.data && e.data.type === 'PRECACHE_URLS') {
    var urls = e.data.urls || [];
    caches.open(CACHE_NAME).then(function (cache) {
      urls.forEach(function (url) {
        cache.match(url).then(function (existing) {
          if (!existing) {
            fetch(url).then(function (resp) {
              if (resp.ok && !resp.redirected) cache.put(url, resp);
            }).catch(function () {});
          }
        });
      });
    });
  }
});
