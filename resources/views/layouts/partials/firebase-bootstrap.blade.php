<script>
    window.firebaseClientConfig = {
        apiKey: "{{ config('firebase.client.apiKey') }}",
        authDomain: "{{ config('firebase.client.authDomain') }}",
        projectId: "{{ config('firebase.client.projectId') }}",
        storageBucket: "{{ config('firebase.client.storageBucket') }}",
        messagingSenderId: "{{ config('firebase.client.messagingSenderId') }}",
        appId: "{{ config('firebase.client.appId') }}"
    };

    let firebaseApp, messaging;

    window.initializeFirebaseAndMessaging = function () {
        if (window.firebaseClientConfig && !firebaseApp) {
            try {
                firebaseApp = firebase.initializeApp(window.firebaseClientConfig);
                messaging = firebase.messaging();

                messaging.onMessage(payload => {
                    const title = payload.notification?.title || 'New Notification';
                    const options = {
                        body: payload.notification?.body || 'You have a new message.',
                        icon: '{{ asset('images/logo.png') }}'
                    };
                    new Notification(title, options);
                });

                console.log('Firebase initialized.');
            } catch (error) {
                console.error("Error initializing Firebase:", error);
            }
        }
    };
</script>