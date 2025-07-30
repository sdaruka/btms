<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    @auth
        <meta name="user-id" content="{{ Auth::user()->id }}">
    @endauth
    <meta name="description" content="Tailor order and customer management made easy.">
    <meta name="author" content="Bhumis Tailor System">
    <meta property="og:title" content="Bhumis Tailor Management System">
    <meta property="og:description" content="Streamline your tailoring business with smart order workflows.">
    <meta property="og:type" content="website">
    
    <title>@yield('title')</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('images/logo.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('images/logo.png') }}">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" />
    
    <link rel="manifest" href="{{ asset('/manifest.json') }}">
    <meta name="theme-color" content="#ffc107">
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">

</head>

<body>
    <div id="installPopup" style="display:none; position:fixed; bottom:2rem; left:2rem; background:#ffc107; padding:1rem; border-radius:8px; box-shadow:0 4px 12px rgba(0,0,0,0.2); z-index:9999;">
        <p>Install BTMS for fast access and offline support.</p>
        <button id="installButton" class="btn btn-dark btn-sm me-2">Install</button>
        <button id="cancelInstallButton" class="btn btn-outline-dark btn-sm">Cancel</button>
    </div>

    <div class="overlay" id="overlay" onclick="toggleSidebar()"></div>
    
    <div class="d-flex">
        @unless (Request::is('/') || Request::is('showregister'))
            {{-- Sidebar is included only if not on root or showregister --}}
            @include('layouts.sidebar')
        @endunless

        {{-- The flex-grow-1 div always wraps the main content area,
             and conditionally includes the top-nav --}}
        <div class="flex-grow-1">
            @unless (Request::is('/') || Request::is('showregister'))
                {{-- Top-nav is included only if not on root or showregister --}}
                @include('layouts.top-nav')
            @endunless

            <main class="container-fluid p-3 bg-light">
                @yield('content')
            </main>
        </div>
    </div>
    {{-- Firebase SDK --}}
<script src="https://www.gstatic.com/firebasejs/9.22.2/firebase-app-compat.js"></script>
<script src="https://www.gstatic.com/firebasejs/9.22.2/firebase-messaging-compat.js"></script>

{{-- Modular Scripts --}}
@include('layouts.partials.firebase-bootstrap')
@include('layouts.partials.fcm-token-handler')
@include('layouts.partials.service-worker-register')
@include('layouts.partials.echo-listeners')

{{-- Rest of your scripts --}}
<script defer>
    document.addEventListener('alpine:init', () => {
        Alpine.data('sidebarToggle', () => ({
            sidebarOpen: false,
            toggle() {
                this.sidebarOpen = !this.sidebarOpen;
            }
        }));
    });
</script>
    <script src="{{ asset('js/firebase-init.js') }}"></script>
    <script src="{{ asset('js/app.js') }}"></script>
    @vite(['resources/js/app.js'])
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
