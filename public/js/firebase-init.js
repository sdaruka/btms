// public/js/firebase-init.js

// This file will now primarily define the Firebase initialization logic and functions.
// The call to requestPermissionAndGetToken() will be triggered conditionally from main.blade.php.

if (window.firebaseClientConfig) {
    try {
       
        document.addEventListener('DOMContentLoaded', function () {
            if ('serviceWorker' in navigator) {
                const serviceWorkerPath = window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1' ? '/sw.js' : '/btms/sw.js';

                navigator.serviceWorker.register(serviceWorkerPath)
                    .then((registration) => {
                        console.log('Service Worker registered with scope:', registration.scope);
                        // IMPORTANT: Removed the direct call to requestPermissionAndGetToken() from here.
                        // It is now called conditionally from main.blade.php's @auth block.
                    })
                    .catch((error) => {
                        console.error('Service Worker registration failed:', error);
                    });
            } else {
                console.warn('Service Workers are not supported in this browser.');
            }
        });

    } catch (e) {
        console.error("Firebase initialization error in firebase-init.js:", e);
    }
} else {
    console.error("Firebase client configuration not found.");
}
