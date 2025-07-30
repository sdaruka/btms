importScripts('https://storage.googleapis.com/workbox-cdn/releases/6.5.4/workbox-sw.js');

if (workbox) {
    console.log(`🎉 Workbox is loaded!`);

    // Set the prefix for cache names (useful for multi-app environments)
    workbox.core.setCacheNameDetails({
        prefix: 'btms-cache',
        suffix: 'v1' // You can update this version when you make significant changes to caching strategy
    });

    // 👷‍♂️ Precaching: App Shell Assets (versioned for updates)
    // Make sure these paths are correct relative to the service worker scope
    workbox.precaching.precacheAndRoute([
        // Static HTML pages for app shell / offline fallback
        { url: '/btms/', revision: '1' }, // Main index or dashboard
        { url: '/btms/offline.html', revision: '1' }, // Offline fallback page

        // Core CSS and JS (assuming these are local assets)
        { url: '/btms/css/style.css', revision: '1' }, // Your custom style.css
        { url: '/btms/js/script.js', revision: '1' },
        { url: '/btms/images/logo.png', revision: '1' },

        // Icons for PWA installation
        { url: '/btms/images/icons/icon-192x192.png', revision: '1' },
        { url: '/btms/images/icons/icon-512x512.png', revision: '1' },

        // Screenshots (if used for PWA install dialog)
        { url: '/btms/images/screenshots/dashboard.png', revision: '1' },

        // *** NEW: Add external CSS/Fonts to precache if you want them available immediately offline ***
        // It's generally better to runtime cache CDNs, but precaching ensures immediate offline availability.
        // Be mindful of the size if precaching many CDN assets.
        { url: 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css', revision: '1' },
        { url: 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css', revision: '1' },
        // For Google Fonts, you generally precache the CSS link, and runtime cache the font files themselves.
        { url: 'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap', revision: '1' },

        // Add any other critical assets that must be available offline immediately
        // For example, if you use Bootstrap JS locally:
        // { url: '/btms/js/bootstrap.bundle.min.js', revision: '1' },
        // { url: '/btms/js/alpinejs.cdn.min.js', revision: '1' },
    ]);

    // 📦 Runtime Caching Strategies

    // 1. Pages/Navigation: Network First (for fresh content), with offline fallback
    workbox.routing.registerRoute(
        ({ request }) => request.mode === 'navigate',
        new workbox.strategies.NetworkFirst({
            cacheName: 'html-pages',
            plugins: [
                new workbox.expiration.ExpirationPlugin({
                    maxEntries: 50,
                    maxAgeSeconds: 5 * 24 * 60 * 60, // 5 days
                }),
                new workbox.cacheableResponse.CacheableResponsePlugin({
                    statuses: [0, 200],
                }),
            ],
            handler: async (options) => {
                try {
                    return await new workbox.strategies.NetworkFirst().handle(options);
                } catch (error) {
                    console.warn('NetworkFirst failed, serving offline page:', error);
                    return caches.match('/btms/offline.html');
                }
            },
        })
    );

    // 2. Static Assets (Scripts, Styles): Stale While Revalidate (quick display, update in background)
    // This will now primarily handle your JS assets and any other CSS not precached.
    workbox.routing.registerRoute(
        ({ request }) => request.destination === 'script' || request.destination === 'style',
        new workbox.strategies.StaleWhileRevalidate({
            cacheName: 'static-assets',
            plugins: [
                new workbox.expiration.ExpirationPlugin({
                    maxEntries: 100,
                    maxAgeSeconds: 30 * 24 * 60 * 60, // 30 days
                }),
                new workbox.cacheableResponse.CacheableResponsePlugin({
                    statuses: [0, 200],
                }),
            ],
        })
    );

    // 3. Images: Cache First (for speed), with a fallback for network failure
    workbox.routing.registerRoute(
        ({ request }) => request.destination === 'image',
        new workbox.strategies.CacheFirst({
            cacheName: 'images',
            plugins: [
                new workbox.expiration.ExpirationPlugin({
                    maxEntries: 100,
                    maxAgeSeconds: 30 * 24 * 60 * 60, // 30 Days
                }),
                new workbox.cacheableResponse.CacheableResponsePlugin({
                    statuses: [0, 200],
                }),
            ],
        })
    );

    // 4. Fonts (e.g., Google Fonts, Font Awesome): Cache First (immutable)
    // This specifically targets the font files themselves, which are loaded by the CSS.
    workbox.routing.registerRoute(
        ({ url }) =>
            url.origin === 'https://fonts.googleapis.com' ||
            url.origin === 'https://fonts.gstatic.com' ||
            url.hostname === 'cdnjs.cloudflare.com', // Covers Font Awesome font files as well
        new workbox.strategies.CacheFirst({
            cacheName: 'external-fonts-and-css', // Renamed cache for clarity
            plugins: [
                new workbox.expiration.ExpirationPlugin({
                    maxEntries: 30,
                    maxAgeSeconds: 365 * 24 * 60 * 60, // 1 Year
                }),
                new workbox.cacheableResponse.CacheableResponsePlugin({
                    statuses: [0, 200],
                }),
            ],
        })
    );

    // 5. API Calls (example): Network First (for fresh data), fallback to cache
    workbox.routing.registerRoute(
        ({ url }) => url.pathname.startsWith('/btms/api/'), // Adjust to match your actual API routes
        new workbox.strategies.NetworkFirst({
            cacheName: 'api-data',
            plugins: [
                new workbox.expiration.ExpirationPlugin({
                    maxEntries: 20,
                    maxAgeSeconds: 60 * 60, // 1 hour (adjust for data freshness)
                }),
                new workbox.cacheableResponse.CacheableResponsePlugin({
                    statuses: [0, 200],
                }),
            ],
        })
    );

    // 🔐 Auth/Sensitive Routes & POST requests: Network Only (never cache)
    workbox.routing.registerRoute(
        ({ request, url }) => {
            const isAuthRoute = url.pathname.startsWith('/btms/login') ||
                url.pathname.startsWith('/btms/logout') ||
                url.pathname.startsWith('/btms/register') ||
                url.pathname.startsWith('/btms/admin'); // Example: Add admin routes if sensitive
            const isPostRequest = request.method === 'POST';
            const isPutRequest = request.method === 'PUT';
            const isDeleteRequest = request.method === 'DELETE';

            return isAuthRoute || isPostRequest || isPutRequest || isDeleteRequest;
        },
        new workbox.strategies.NetworkOnly()
    );

    // 🧹 Cache Management: Clear old caches on activate
    self.addEventListener('activate', (event) => {
        const expectedCacheNames = Object.keys(workbox.core.cacheNames).map(
            (key) => workbox.core.cacheNames[key]
        );

        event.waitUntil(
            caches.keys().then((cacheNames) => {
                return Promise.all(
                    cacheNames.map((cacheName) => {
                        if (!expectedCacheNames.includes(cacheName)) {
                            console.log('👷‍♀️ Deleting old cache:', cacheName);
                            return caches.delete(cacheName);
                        }
                        return Promise.resolve();
                    })
                );
            })
        );
        event.waitUntil(clients.claim());
    });

    // Optional: Add a message listener to trigger cache updates or skip waiting
    self.addEventListener('message', (event) => {
        if (event.data && event.data.type === 'SKIP_WAITING') {
            self.skipWaiting();
        }
    });

} else {
    console.log(`😭 Workbox didn't load`);
}