var CACHE_NAME = 'cards-v3';
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

  // Cache-first for static assets
  if (url.pathname.startsWith('/assets/') || url.pathname === '/manifest.json') {
    e.respondWith(
      caches.match(e.request).then(function (cached) {
        if (cached) {
          // Update cache in background
          fetch(e.request).then(function (resp) {
            if (resp.ok) caches.open(CACHE_NAME).then(function (c) { c.put(e.request, resp); });
          }).catch(function () {});
          return cached;
        }
        return fetch(e.request).then(function (resp) {
          var clone = resp.clone();
          caches.open(CACHE_NAME).then(function (c) { c.put(e.request, clone); });
          return resp;
        });
      })
    );
    return;
  }

  // HTML pages: cache-first, reload page if content changed
  e.respondWith(
    caches.match(e.request).then(function (cached) {
      if (cached) {
        fetch(e.request).then(function (resp) {
          if (!resp.ok || resp.redirected) return;
          resp.clone().text().then(function (newBody) {
            cached.clone().text().then(function (oldBody) {
              caches.open(CACHE_NAME).then(function (c) { c.put(e.request, resp); });
              if (newBody !== oldBody) {
                self.clients.matchAll().then(function (clients) {
                  clients.forEach(function (client) { client.postMessage({ type: 'CONTENT_UPDATED' }); });
                });
              }
            });
          });
        }).catch(function () {});
        return cached;
      }
      return fetch(e.request).then(function (resp) {
        if (resp.redirected || !resp.ok) return resp;
        var clone = resp.clone();
        caches.open(CACHE_NAME).then(function (c) { c.put(e.request, clone); });
        return resp;
      }).catch(function () {
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
