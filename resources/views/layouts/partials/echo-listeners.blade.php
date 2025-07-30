@auth
<script>
    document.addEventListener('DOMContentLoaded', () => {
        if (window.Echo) {
            window.Echo.private('App.Models.User.{{ Auth::user()->id }}')
                .listen('NewNotification', (e) => {
                    console.log('Echo notification:', e.notification);
                });
        } else {
            console.warn('Laravel Echo not available.');
        }

        requestPermissionAndGetToken();
    });
</script>
@endauth