const CACHE_NAME = 'asaan-v1';

const SHELL = [
    '/',
    '/vendor/shop/themes/default/app.css',
    '/vendor/shop/themes/default/app.rtl.css',
    '/vendor/shop/themes/default/aimeos.css',
    '/vendor/shop/themes/default/app.js',
    '/vendor/shop/themes/default/aimeos.js',
    '/vendor/shop/themes/default/assets/roboto-condensed-v19-latin-regular.woff2',
    '/vendor/shop/themes/default/assets/roboto-condensed-v19-latin-700.woff2',
    '/vendor/shop/themes/default/assets/bootstrap-icons.woff2',
];

self.addEventListener('install', function(event) {
    event.waitUntil(
        caches.open(CACHE_NAME).then(function(cache) {
            return cache.addAll(SHELL);
        }).then(function() {
            return self.skipWaiting();
        })
    );
});

self.addEventListener('activate', function(event) {
    event.waitUntil(
        caches.keys().then(function(names) {
            return Promise.all(
                names.filter(function(name) {
                    return name !== CACHE_NAME;
                }).map(function(name) {
                    return caches.delete(name);
                })
            );
        }).then(function() {
            return self.clients.claim();
        })
    );
});

self.addEventListener('fetch', function(event) {
    var request = event.request;

    if (request.method !== 'GET') {
        return;
    }

    if (request.mode === 'navigate') {
        event.respondWith(
            fetch(request).catch(function() {
                return caches.match(request).then(function(cached) {
                    return cached || caches.match('/');
                });
            })
        );
        return;
    }

    event.respondWith(
        caches.match(request).then(function(cached) {
            if (cached) {
                fetch(request).then(function(response) {
                    if (response && response.status === 200) {
                        var clone = response.clone();
                        caches.open(CACHE_NAME).then(function(cache) {
                            cache.put(request, clone);
                        });
                    }
                }).catch(function() {});
                return cached;
            }

            return fetch(request).then(function(response) {
                if (!response || response.status !== 200 || response.type !== 'basic') {
                    return response;
                }
                var clone = response.clone();
                caches.open(CACHE_NAME).then(function(cache) {
                    cache.put(request, clone);
                });
                return response;
            });
        })
    );
});
