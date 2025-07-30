<script>
    window.requestPermissionAndGetToken = function () {
        if (!messaging) {
            console.error("Firebase Messaging not initialized.");
            return;
        }
        messaging.getToken().then(token => {
    console.log("FCM Token:", token); // 👈 Must appear
});
        Notification.requestPermission().then(permission => {
            if (permission !== 'granted') throw new Error('Notification permission denied.');
            return messaging.getToken();
        }).then(token => {
            if (token) sendTokenToServer(token);
        }).catch(err => {
            console.error('FCM token error:', err);
        });
    };

    function sendTokenToServer(token) {
        const csrf = document.querySelector('meta[name="csrf-token"]').content;
        const controller = new AbortController();
        const timeout = setTimeout(() => controller.abort(), 8000);

        fetch('/api/fcm-token', {
            method: 'POST',
            credentials: 'include',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrf
            },
            body: JSON.stringify({ fcm_token: token }),
            signal: controller.signal
        })
        .finally(() => clearTimeout(timeout))
        .then(response => {
            if (!response.ok) throw new Error(`HTTP ${response.status}`);
            return response.json();
        })
        .then(data => console.log('FCM token sent:', data))
        .catch(error => console.error('Send token error:', error));
    }
</script>