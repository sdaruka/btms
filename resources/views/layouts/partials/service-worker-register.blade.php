{{-- <script>
    document.addEventListener('DOMContentLoaded', () => {
        if (!('serviceWorker' in navigator)) {
            console.warn('Service Workers not supported.');
            return;
        }

        const swPath = location.hostname === 'localhost' ? '/sw.js' : '/btms/sw.js';
        navigator.serviceWorker.register(swPath)
            .then(reg => console.log('SW registered:', reg.scope))
            .catch(err => console.error('SW registration failed:', err));
    });
</script> --}}
<script>
document.addEventListener('DOMContentLoaded', function () {
            if ('serviceWorker' in navigator) {
                const serviceWorkerPath = window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1' ? '/sw.js' : '/btms/sw.js';

                navigator.serviceWorker.register(serviceWorkerPath)
                    .then((registration) => {
                        console.log('Service Worker registered with scope:', registration.scope);
                    })
                    .catch((error) => {
                        console.error('Service Worker registration failed:', error);
                    });
            } else {
                console.warn('Service Workers are not supported in this browser.');
            }
        });
        
        // Initialize Firebase Messaging when DOM is ready and user is potentially authenticated
        document.addEventListener('DOMContentLoaded', initializeFirebaseAndMessaging);
    </script>