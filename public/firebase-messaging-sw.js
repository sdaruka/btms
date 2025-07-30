// public/firebase-messaging-sw.js

// Import the Firebase SDKs
// Ensure these paths are correct and accessible.

// IMPORTANT DEBUGGING TIP FOR "firebase is not defined":
// 1. Go to Chrome DevTools -> Application -> Service Workers.
// 2. Find 'firebase-messaging-sw.js'. If it's not 'activated and running', or if it's 'redundant',
//    click 'Unregister' and then hard refresh your page. This forces the browser to re-register and re-download.
// 3. In DevTools -> Network tab, while the Service Worker section is open, refresh the page.
//    Ensure the 'importScripts' URLs (firebase-app-compat.js and firebase-messaging-compat.js) load successfully (Status 200 OK).
//    If they fail, there's a network/path issue.

// Initialize the Firebase app in the service worker by passing in
// your app's Firebase config object.
// IMPORTANT: This configuration must match the one used in your main app.
// Since service workers don't have direct access to Laravel's config(),
// you will need to hardcode these *public* values here.
// These values are not sensitive.

try {
    
    console.log("Firebase object before init:", typeof firebase); // For debugging: check if 'firebase' is available
    firebase.initializeApp({
            apiKey: "AIzaSyBPssJPHm4knXylMEuAHFT73nBFv6RYakw",
            authDomain: "btms-76c04.firebaseapp.com",
            projectId: "btms-76c04",
            storageBucket: "btms-76c04.firebasestorage.app",
            messagingSenderId: "904869945123",
            appId: "1:904869945123:web:78f2d83f28a42cec38f696",
            measurementId: "G-NNY3G47WN4"
        });

    // Retrieve Firebase Messaging object.
    const messaging = firebase.messaging();

    // Handle background messages (when the app is not in focus)
    messaging.onBackgroundMessage((payload) => {
        console.log('[sw.js] Received background message ', payload);

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
}
// Handle errors during Firebase initialization
if (typeof firebase === 'undefined') {
    console.error("Firebase not initialized");
}
    // You might want to log this error to a server-side logging service
    
// as service worker errors are harder to catch.
// If you want to handle errors in your service worker, you can use the following code
self.addEventListener('error', (event) => {
    console.error('Service worker error:', event);
        });
