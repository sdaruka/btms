// document.addEventListener('alpine:init', () => {
//     Alpine.data('sidebarToggle', () => ({
//         sidebarOpen: false,
//         toggle() {
//             this.sidebarOpen = !this.sidebarOpen
//         }
//     }))
// });

function toggleSidebar() {
    document.getElementById('sidebar').classList.toggle('active');
    document.getElementById('overlay').classList.toggle('active');
}

document.addEventListener('DOMContentLoaded', function () {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('overlay');
    const installPopup = document.getElementById('installPopup');
    const installButton = document.getElementById('installButton');
    const cancelInstallButton = document.getElementById('cancelInstallButton');

    // Close sidebar when clicking outside
    if (overlay) {
        overlay.addEventListener('click', function () {
            if (sidebar) sidebar.classList.remove('active');
            overlay.classList.remove('active');
        });
    }

    // Close sidebar on escape key
    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            if (sidebar) sidebar.classList.remove('active');
            if (overlay) overlay.classList.remove('active');
        }
    });

// --- PWA Installation Logic ---
let deferredPrompt;
//  Service Worker Registration
// if ('serviceWorker' in navigator) {
//     navigator.serviceWorker.register('/sw.js')
//         .then(registration => {
//             console.log('Service Worker registered successfully:', registration);
//         })
//         .catch(error => {
//             console.error('Service Worker registration failed:', error);
//         });
// }

// Listen for the beforeinstallprompt event
window.addEventListener('beforeinstallprompt', (e) => {
    e.preventDefault(); // Prevent the default mini-infobar
    deferredPrompt = e; // Stash the event
    console.log('beforeinstallprompt event fired, deferred.');

// Show your custom install UI
    if (installPopup) {
        installPopup.style.display = 'block';
    }
});

// Function to handle custom "Install" button click
if (installButton) {
    installButton.addEventListener('click', async () => {
        if (deferredPrompt) {
            if (installPopup) {
                installPopup.style.display = 'none'; // Hide custom popup
            }

            deferredPrompt.prompt(); // Show native installation prompt

            const { outcome } = await deferredPrompt.userChoice;
            console.log(`User response to the A2HS prompt: ${outcome}`);

            deferredPrompt = null; // Clear the deferredPrompt

            if (outcome === 'accepted') {
                console.log('User accepted the A2HS prompt');
            } else {
                console.log('User dismissed the A2HS prompt');
            }
        } else {
            console.log('Deferred prompt not available. Maybe already installed or criteria not met.');
        }
    });
}

// Function to handle custom "Cancel" button click
if (cancelInstallButton) {
    cancelInstallButton.addEventListener('click', () => {
        if (installPopup) {
            installPopup.style.display = 'none'; // Hide custom popup
        }
        console.log('Install prompt popup closed by user.');
        // Optionally, store a flag in localStorage to avoid showing it too frequently
    });
}

// Listen for the appinstalled event
window.addEventListener('appinstalled', () => {
    console.log('PWA was installed!');
    if (installPopup) {
        installPopup.style.display = 'none'; // Ensure custom popup is hidden
    }
    deferredPrompt = null; // Just in case
});
});