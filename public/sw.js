try {
    // console.log("Firebase object before init:", typeof firebase); // For debugging: check if 'firebase' is available
    firebase.initializeApp({
        apiKey: "AIzaSyBPssJPHm4knXylMEuAHFT73nBFv6RYakw",
        authDomain: "btms-76c04.firebaseapp.com",
        projectId: "btms-76c04",
        storageBucket: "btms-76c04.firebasestorage.app",
        messagingSenderId: "904869945123",
        appId: "1:904869945123:web:eb552fd8d16d552c38f696"
    });

    // Retrieve Firebase Messaging object.
    const messaging = firebase.messaging();

    // Handle background messages (when the app is not in focus)
    messaging.onBackgroundMessage((payload) => {
        console.log('[public/sw.js] Received background message ', payload); // Updated path for console log

        const notificationTitle = payload.notification.title || 'New Message';
        const notificationOptions = {
            body: payload.notification.body || 'You have a new notification.',
            icon: '/firebase-logo.png', // Optional: Path to an icon for the notification
            data: payload.data // Pass custom data to the notification
        };

        self.registration.showNotification(notificationTitle, notificationOptions);

        self.addEventListener('notificationclick', (event) => {
            event.notification.close();
            const clickActionUrl = event.notification.data ? event.notification.data.click_action : null;
            if (clickActionUrl) {
                event.waitUntil(
                    clients.openWindow(clickActionUrl)
                );
            }
        });
    });
} catch (e) {
    console.error("Firebase initialization error in service worker:", e);
    // You might want to log this error to a server-side logging service
    // as service worker errors are harder to catch.
}

// Define cache names at the top of the file for clarity and consistency
const CACHE_NAME = 'btms-app-shell-v1'; // For precached assets (app shell)
const RUNTIME_CACHE_NAME = 'btms-runtime-cache-v1'; // For assets cached during runtime (e.g., pages, dynamic images)
const urlsToCache = [
    '/', // Main entry point for your PWA (e.g., dashboard or login page)
    '/offline.html', // Your custom offline fallback page
    '/css/style.css', // Your custom style.css
    '/js/script.js', // Your main script.js (if it exists and is crucial)
    '/images/logo.png', // Your application's logo
    '/manifest.json', // Your web app manifest

    '/images/icons/icon-192x192.png',

    '/images/icons/icon-512x512.png',
    '/images/screenshots/dashboard.png', // Example screenshot

    'https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css',
    'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css',
    'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap',
    'https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js',
    'https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js',
];

// Service Worker 'install' event listener
// This event is fired when the service worker is installed.
// It's typically used to precache essential assets (the app shell).
self.addEventListener('install', event => {
    // console.log('[Service Worker] Installing...');
    event.waitUntil(
        caches.open(CACHE_NAME) // Open the app shell cache
            .then(cache => {
                // console.log('[Service Worker] Precaching app shell assets.');
                return cache.addAll(urlsToCache); // Add all defined URLs to the cache
            })
            .then(() => self.skipWaiting()) // Force the new service worker to activate immediately
            .catch(error => console.error('[Service Worker] Precaching failed:', error))
    );
});

// Service Worker 'activate' event listener
// This event is fired when the service worker is activated.
// It's typically used to clean up old caches.
self.addEventListener('activate', event => {
    console.log('[Service Worker] Activating...');
    // Define a whitelist of cache names to keep. All other caches will be deleted.
    const cacheWhitelist = [CACHE_NAME, RUNTIME_CACHE_NAME];

    event.waitUntil(
        caches.keys().then((cacheNames) => {
            return Promise.all(
                cacheNames.map((cacheName) => {
                    // Check if the current cacheName is NOT in our whitelist
                    // and if it starts with our app's prefix (for safety, to avoid deleting unrelated caches)
                    if (cacheWhitelist.indexOf(cacheName) === -1 && cacheName.startsWith('btms-')) {
                        console.log('[Service Worker] Deleting old cache:', cacheName);
                        return caches.delete(cacheName);
                    }
                })
            );
        }).then(() => self.clients.claim()) // Take control of existing clients immediately
    );
});

// Service Worker 'fetch' event listener
// This event is fired for every network request made by the controlled pages.
// It allows the service worker to intercept requests and respond with cached assets or fetch from the network.
self.addEventListener('fetch', event => {
    const requestUrl = new URL(event.request.url);
    const requestPath = requestUrl.pathname;
    const requestMethod = event.request.method;

    // Define routes that should ALWAYS go to the network (Network Only strategy).
    // These typically include sensitive data (login, logout, register), API calls,
    // and any non-GET requests (POST, PUT, DELETE, PATCH) to ensure data integrity and freshness.
    const networkOnlyRoutes = [
        '/login',
        '/register',
        '/password/', // Matches /password/reset etc.
        '/logout',
        '/api/', // All API calls should typically be network-only for freshness
        '/users', // Assuming user-related pages/data are dynamic and require network
        '/orders', // Assuming order-related pages/data are dynamic and require network
        '/dashboard', // Corrected typo: 'dashboards' -> 'dashboard'
        '/products', // Assuming product-related pages/data are dynamic and require network
    ];

    // Determine if the current request should be handled by the Network Only strategy
    const isNetworkOnly = networkOnlyRoutes.some(route => requestPath.startsWith(route)) || requestMethod !== 'GET';

    if (isNetworkOnly) {
        console.log(`[Service Worker] Network Only Strategy: ${event.request.url}`);
        event.respondWith(
            fetch(event.request).catch(error => {
                console.error(`[Service Worker] Network-only fetch failed for ${event.request.url}:`, error);
                // For network-only routes, if offline, we generally let the error propagate
                // or show a generic message if it's a critical navigation.
                // No offline fallback for these by design, as they need live data.
                throw error;
            })
        );
        return; // Stop processing this fetch event, it's handled.
    }

    // Handle navigation requests (requests for HTML documents) - Network First strategy with offline fallback.
    // This strategy tries to fetch from the network first. If successful, it caches the response.
    // If the network fails, it falls back to the cached version. Ideal for dynamic content that needs offline access.
    if (event.request.mode === 'navigate') {
        console.log(`[Service Worker] Network First Strategy (Navigation): ${event.request.url}`);
        event.respondWith(
            fetch(event.request)
                .then(response => {
                    // If the network response is valid (HTTP 200 OK), clone it and put it in the runtime cache.
                    if (response.ok) {
                        const responseToCache = response.clone();
                        caches.open(RUNTIME_CACHE_NAME).then(cache => { // Use RUNTIME_CACHE_NAME for runtime pages
                            cache.put(event.request, responseToCache);
                            console.log(`[Service Worker] Cached navigation response: ${event.request.url}`);
                        });
                    }
                    return response; // Return the network response
                })
                .catch(() => {
                    // If the network request fails (e.g., offline), try to serve the offline page.
                    console.log('[Service Worker] Network failed for navigation, serving offline page fallback.');
                    return caches.match('/offline.html').then(cachedResponse => {
                        if (cachedResponse) {
                            return cachedResponse; // Serve the precached offline page
                        }
                        // Fallback if offline.html itself isn't cached (shouldn't happen if precached correctly)
                        console.error('Offline page /offline.html not found in cache! Serving generic fallback.');
                        return new Response('<h1>Offline</h1><p>You are currently offline and the requested page is not available.</p>', {
                            headers: { 'Content-Type': 'text/html' },
                            status: 503, // Service Unavailable
                            statusText: 'Service Unavailable'
                        });
                    });
                })
        );
        return; // Stop processing this fetch event, it's handled.
    }

    // Default caching strategy for other GET requests (e.g., images, CSS, JS not in app shell).
    // Cache First strategy: Tries to serve from cache immediately. If not found, fetches from the network.
    // This is good for static assets that don't change often.
    event.respondWith(
        caches.match(event.request)
            .then((response) => {
                // Return cached response if found
                if (response) {
                    console.log(`[Service Worker] Cache First Strategy: Serving from cache: ${event.request.url}`);
                    return response;
                }

                // If not in cache, fetch from network
                console.log(`[Service Worker] Cache First Strategy: Fetching from network: ${event.request.url}`);
                return fetch(event.request).then((networkResponse) => {
                    // Check if we received a valid response (not null, status 200, basic type for caching)
                    if (!networkResponse || networkResponse.status !== 200 || networkResponse.type !== 'basic') {
                        return networkResponse; // Return the network response as is (e.g., error, redirect)
                    }

                    // Clone the response to put in cache (response streams can only be read once)
                    const responseToCache = networkResponse.clone();
                    caches.open(RUNTIME_CACHE_NAME).then((cache) => { // Use RUNTIME_CACHE_NAME for runtime assets
                        cache.put(event.request, responseToCache);
                        console.log(`[Service Worker] Fetched and cached: ${event.request.url}`);
                    });
                    return networkResponse; // Return the network response
                }).catch(error => {
                    console.error(`[Service Worker] Fetch failed for ${event.request.url}:`, error);
                    // For other assets that fail to load, you might return a generic fallback
                    // (e.g., an offline image for failed image fetches) or just let the network error propagate.
                    throw error;
                });
            })
    );
});
