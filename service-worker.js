/* Network only: never store account pages, messages, or API responses. */
self.addEventListener('install', function () { self.skipWaiting(); });
self.addEventListener('activate', function (event) { event.waitUntil(self.clients.claim()); });
self.addEventListener('fetch', function (event) {
    if (event.request.method !== 'GET' || event.request.mode !== 'navigate') return;
    event.respondWith(fetch(event.request).catch(function () {
        return new Response('<!doctype html><html lang="vi"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Saigon Cupid</title><body style="font-family:system-ui;padding:32px;line-height:1.6"><h1>Bạn đang mất kết nối</h1><p>Kết nối Internet rồi tải lại trang để tiếp tục sử dụng Saigon Cupid.</p><button onclick="location.reload()">Thử lại</button></body></html>', {status:503, headers:{'Content-Type':'text/html; charset=utf-8', 'Cache-Control':'no-store'}});
    }));
});
