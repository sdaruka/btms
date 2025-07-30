// You need to get this config from your Firebase project settings
// Go to Project Settings -> General -> Your apps -> Web app -> Firebase SDK snippet -> Config
importScripts('https://www.gstatic.com/firebasejs/9.22.2/firebase-app-compat.js');
importScripts('https://www.gstatic.com/firebasejs/9.22.2/firebase-messaging-compat.js');

const firebaseConfig = {
    apiKey: "{{ config('firebase.client.apiKey') }}",
    authDomain: "{{ config('firebase.client.authDomain') }}",
    projectId: "{{ config('firebase.client.projectId') }}",
    storageBucket: "{{ config('firebase.client.storageBucket') }}",
    messagingSenderId: "{{ config('firebase.client.messagingSenderId') }}",
    appId: "{{ config('firebase.client.appId') }}"
};

// Initialize Firebase
firebase.initializeApp(firebaseConfig);
const messaging = firebase.messaging();

// Function to request permission and get token
function requestPermissionAndGetToken() {
    console.log('Requesting permission for notifications...');
    Notification.requestPermission().then((permission) => {
        if (permission === 'granted') {
            console.log('Notification permission granted.');
            // Get the token
            return messaging.getToken();
        } else {
            console.log('Unable to get permission to notify.');
        }
    }).then(token => {
        if (token) {
            console.log('FCM Token:', token);
            // Send this token to your server
            sendTokenToServer(token);
        }
    }).catch((err) => {
        console.log('An error occurred while retrieving token. ', err);
    });
}

// Function to send the token to your Laravel backend
function sendTokenToServer(token) {
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    fetch('/fcm-token', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken
        },
        body: JSON.stringify({ token: token })
    })
        .then(response => response.json())
        .then(data => console.log('Token sent to server:', data))
        .catch(error => console.error('Error sending token to server:', error));
}

// Call the function to start the process
requestPermissionAndGetToken();

// Handle incoming messages when the app is in the foreground
messaging.onMessage((payload) => {
    console.log('Message received. ', payload);
    // You can show a custom notification here
    const notificationTitle = payload.notification.title;
    const notificationOptions = {
        body: payload.notification.body,
        icon: '/images/logo.png' // Optional: your app logo
    };
    new Notification(notificationTitle, notificationOptions);
});