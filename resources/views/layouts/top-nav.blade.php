<nav role="navigation" aria-label="Main topbar" class="navbar navbar-dark bg-dark text-white px-3">
    <div class="d-flex align-items-center w-100 justify-content-between">
        {{-- Sidebar + Logo + Branding --}}
        <div class="d-flex align-items-center gap-2">
            <button class="btn btn-outline-light d-md-none me-2" onclick="toggleSidebar()">☰</button>
            <img src="{{ asset('images/logo.png') }}" alt="Logo" style="height: 32px;" class="me-2">
            <span class="fw-semibold d-none d-md-inline">Bhumis Tailor Management System</span>
            <span class="fw-semibold d-md-none">BTMS</span>
        </div>
@php
// $notificationCount = 2; // Example notification count
// $notifications = [
//     (object)[
//         'title' => 'New Order Received',
//         'message' => 'You have a new order from John Doe.',
//         'link' => '#'
//     ],
//     (object)[
//         'title' => 'Profile Update',
//         'message' => 'Your profile has been updated successfully.',
//         'link' => '#'
//     ]
// ];
@endphp
        {{-- Notification Bell with Dropdown --}}
        <div class="dropdown px-3">
            <a class="btn btn-link text-white position-relative" href="#" role="button" id="notificationDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="fas fa-bell fs-5"></i>
                @if(!empty($notificationCount) && $notificationCount > 0)
                    <span class="position-absolute top-0 mt-2 start-80 translate-middle badge rounded-pill bg-danger">
                        {{ $notificationCount }}
                        
                        <span class="visually-hidden">unread notifications</span>
                    </span>
                    @else
                    <span class="position-absolute top-0 mt-2 start-80 translate-middle badge rounded-pill bg-danger">
                        {{ $notificationCount ?? 0 }}
                        
                        <span class="visually-hidden">unread notifications</span>
                    </span>
                @endif
            </a>

            <ul class="dropdown-menu dropdown-menu-end shadow-sm" aria-labelledby="notificationDropdown" style="min-width: 280px;">
                @forelse($notifications->take(5) as $notification)
                    <li>
                        <a href="{{ route('orders.show', $notification->order_id) }}" class="dropdown-item">
                            <div class="fw-semibold">{{ $notification->title }}</div>
                            <div class="text-muted small">{{ $notification->message }}</div>
                        </a>
                    </li>
                    <li><hr class="dropdown-divider"></li>
                @empty
                    <li>
                        <div class="dropdown-item text-muted text-center">No new notifications</div>
                    </li>
                @endforelse
                <li>
                    <a href="{{ route('notifications') }}" class="dropdown-item text-center text-primary">View all</a>
                    {{-- <a href="#" class="dropdown-item text-center text-primary">View all</a> --}}
                </li>
            </ul>
        </div>
    </div>
</nav>
