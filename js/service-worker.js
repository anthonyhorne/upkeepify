/**
 * Upkeepify Service Worker
 *
 * Provides basic offline support by caching plugin assets using a
 * cache-first strategy. The plugin URL is injected at serve time by
 * upkeepify_pwa_serve_service_worker() in includes/pwa.php — do not
 * replace the __UPKEEPIFY_PLUGIN_URL__ placeholder manually.
 *
 * Push notification support will be added in a later release.
 *
 * @package Upkeepify
 * @since   1.1
 */

/* global self, caches, fetch */

var CACHE_NAME   = 'upkeepify-v1';
var PLUGIN_URL   = '__UPKEEPIFY_PLUGIN_URL__'; // injected by PHP at serve time

var STATIC_ASSETS = [
    PLUGIN_URL + 'upkeepify-styles.css',
    PLUGIN_URL + 'js/form-validation.min.js',
    PLUGIN_URL + 'js/upload-handler.min.js',
    PLUGIN_URL + 'js/utils.min.js',
    PLUGIN_URL + 'js/notifications.min.js',
    PLUGIN_URL + 'js/task-filters.min.js',
];

// ─── Install ─────────────────────────────────────────────────────────────────
// Pre-cache static plugin assets. A failed fetch on any asset is caught so a
// single missing file does not prevent installation.

self.addEventListener('install', function (event) {
    event.waitUntil(
        caches.open(CACHE_NAME).then(function (cache) {
            var fetches = STATIC_ASSETS.map(function (url) {
                return cache.add(url).catch(function (err) {
                    if (typeof console !== 'undefined') {
                        console.warn('Upkeepify SW: pre-cache miss for ' + url, err);
                    }
                });
            });
            return Promise.all(fetches);
        })
    );
    self.skipWaiting();
});

// ─── Activate ────────────────────────────────────────────────────────────────
// Remove caches from previous SW versions so stale assets are not served.

self.addEventListener('activate', function (event) {
    event.waitUntil(
        caches.keys().then(function (keys) {
            return Promise.all(
                keys
                    .filter(function (key) { return key !== CACHE_NAME; })
                    .map(function (key) { return caches.delete(key); })
            );
        })
    );
    self.clients.claim();
});

// ─── Fetch ───────────────────────────────────────────────────────────────────
// Cache-first for static plugin assets; network-first for everything else.
// Non-GET and cross-origin requests are not intercepted.

self.addEventListener('fetch', function (event) {
    var request = event.request;
    var url;

    try {
        url = new URL(request.url);
    } catch (e) {
        return;
    }

    // Only handle same-origin GET requests.
    if (request.method !== 'GET' || url.origin !== self.location.origin) {
        return;
    }

    // Static plugin assets: cache-first, update cache on network hit.
    if (url.pathname.indexOf('/wp-content/plugins/upkeepify/') === 0) {
        event.respondWith(
            caches.match(request).then(function (cached) {
                if (cached) {
                    return cached;
                }
                return fetch(request).then(function (response) {
                    if (response.ok) {
                        var clone = response.clone();
                        caches.open(CACHE_NAME).then(function (cache) {
                            cache.put(request, clone);
                        });
                    }
                    return response;
                });
            })
        );
        return;
    }

    // All other requests: network-first, silent offline fallback.
    event.respondWith(
        fetch(request).catch(function () {
            return caches.match(request);
        })
    );
});
