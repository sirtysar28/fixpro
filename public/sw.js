/* ============================================================
   FIXPRO — Service Worker (PWA)
   Strategi aman untuk aplikasi Laravel dengan auth:
   1. Halaman (navigasi)  : NETWORK-FIRST → cache → halaman offline
   2. Aset statis (css/js/font/gambar, termasuk CDN) : CACHE-FIRST
   3. Request non-GET (POST/PUT/DELETE) & data XHR/JSON : TIDAK di-cache
   Versi cache diganti (fixpro-v1 → v2 dst) untuk force update.
   ============================================================ */
const CACHE_VERSION = 'fixpro-v1';
const PRECACHE_URLS = [
    '/manifest.json',
    '/icons/icon-192.png',
    '/icons/icon-512.png',
    '/icons/icon-maskable-192.png',
    '/icons/icon-maskable-512.png'
];
const CACHEABLE_DESTINATIONS = ['style', 'script', 'font', 'image', 'manifest'];

const OFFLINE_HTML = `<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>FIXPRO — Offline</title>
<style>
    body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #f1f5f9; color: #1e293b; min-height: 100vh; display: flex; align-items: center; justify-content: center; margin: 0; padding: 20px; }
    .box { max-width: 340px; text-align: center; background: #fff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 36px 24px; box-shadow: 0 8px 30px rgba(0,0,0,.06); }
    .ico { width: 64px; height: 64px; border-radius: 16px; background: #ccfbf1; color: #0d9488; display: flex; align-items: center; justify-content: center; font-size: 1.6rem; margin: 0 auto 16px; }
    h1 { font-size: 1.15rem; margin: 0 0 8px; }
    p { font-size: .85rem; color: #64748b; margin: 0 0 20px; line-height: 1.6; }
    button { background: #0d9488; color: #fff; border: none; border-radius: 10px; padding: 11px 22px; font-size: .85rem; font-weight: 700; cursor: pointer; }
</style>
</head>
<body>
<div class="box">
    <div class="ico">📡</div>
    <h1>Tidak Ada Koneksi</h1>
    <p>FIXPRO butuh koneksi internet untuk menampilkan data terbaru.<br>Silakan cek koneksi Anda lalu coba lagi.</p>
    <button onclick="location.reload()">Coba Lagi</button>
</div>
</body>
</html>`;

/* INSTALL: precache aset dasar */
self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_VERSION)
            .then((cache) => cache.addAll(PRECACHE_URLS))
            .then(() => self.skipWaiting())
            .catch(() => self.skipWaiting())
    );
});

/* ACTIVATE: hapus cache versi lama */
self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys()
            .then((keys) => Promise.all(keys.filter((k) => k !== CACHE_VERSION).map((k) => caches.delete(k))))
            .then(() => self.clients.claim())
    );
});

/* FETCH */
self.addEventListener('fetch', (event) => {
    const req = event.request;

    /* Jangan pernah sentuh request non-GET (form submit, login, pembayaran) */
    if (req.method !== 'GET') return;

    /* Navigasi halaman: network-first, fallback cache, fallback offline */
    if (req.mode === 'navigate') {
        event.respondWith(
            fetch(req)
                .then((res) => {
                    const copy = res.clone();
                    caches.open(CACHE_VERSION).then((cache) => cache.put(req, copy)).catch(() => {});
                    return res;
                })
                .catch(() =>
                    caches.match(req).then((hit) => hit || new Response(OFFLINE_HTML, { headers: { 'Content-Type': 'text/html; charset=utf-8' } }))
                )
        );
        return;
    }

    /* Aset statis (css/js/font/gambar termasuk dari CDN): cache-first */
    if (CACHEABLE_DESTINATIONS.indexOf(req.destination) !== -1) {
        event.respondWith(
            caches.match(req).then((hit) => {
                if (hit) return hit;
                return fetch(req)
                    .then((res) => {
                        /* res.ok = same-origin sukses; opaque = respons CDN tanpa CORS */
                        if (res && (res.ok || res.type === 'opaque')) {
                            const copy = res.clone();
                            caches.open(CACHE_VERSION).then((cache) => cache.put(req, copy)).catch(() => {});
                        }
                        return res;
                    })
                    .catch(() => hit);
            })
        );
    }
    /* Selain itu (XHR/JSON data, dsb): biarkan lewat network saja */
});
