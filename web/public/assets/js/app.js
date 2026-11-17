// Register service worker
if ('serviceWorker' in navigator) {
  navigator.serviceWorker.addEventListener('message', function (e) {
    if (e.data && e.data.type === 'CONTENT_UPDATED') window.location.reload();
  });
  navigator.serviceWorker.register('/sw.js').then(function (reg) {
    // Pre-cache all card show pages when on homepage
    if (window.location.pathname === '/' || window.location.pathname === '') {
      var links = document.querySelectorAll('a[href*="/cards/"][href*="/use"]');
      var urls = [];
      links.forEach(function (a) { urls.push(a.getAttribute('href')); });
      if (urls.length && reg.active) {
        reg.active.postMessage({ type: 'PRECACHE_URLS', urls: urls });
      } else if (urls.length) {
        navigator.serviceWorker.ready.then(function (r) {
          r.active.postMessage({ type: 'PRECACHE_URLS', urls: urls });
        });
      }
    }
  });
}
