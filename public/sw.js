// public/sw.js
self.addEventListener('install', (e) => {
  console.log('Service Worker instalado!');
});

self.addEventListener('fetch', (e) => {
  // Deixa as requisições passarem normalmente
  e.respondWith(fetch(e.request));
});