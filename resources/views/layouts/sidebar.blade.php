<nav x-data="sidebarToggle()" x-show="sidebarOpen" x-transition x-cloak
     class="sidebar p-3 d-flex flex-column justify-content-between" id="sidebar">
    <div class="side-nav">
        <div class="text-center mb-2">
            <img src="{{ asset('images/logo.png') }}" alt="BTMS Logo" class="img-fluid" style="max-width: 7vh;">
        </div>

        @php
            $user = Auth::user();
            $imagePath =
                !empty($user->profile_picture) && file_exists(public_path($user->profile_picture))
                    ? asset($user->profile_picture)
                    : asset('images/default-user.svg');
        @endphp

        <div class="d-flex align-items-center mb-3 profile-section">
            <a href="{{ route('profile.show', ['user' => $user->id]) }}"
                class="d-flex align-items-center text-warning gap-3 text-decoration-none" data-bs-toggle="tooltip"
                data-bs-placement="top" title="Update profile">

                <img src="{{ $imagePath }}" class="rounded-circle shadow-lg" width="50" height="50"
                    alt="Profile Picture" style="border: 1px solid #d5d6d3;">

                <div class="d-flex flex-column">
                    <span class="text-white fw-semibold">{{ $user->name }}</span>
                </div>

                <i class="fa-solid fa-square-pen fs-5 ms-2"></i>
            </a>
        </div>

        @php
            $current = Request::segment(1);
        @endphp
        <ul class="nav flex-column">
            <li class="nav-item">
                <a href="{{ url('/dashboard') }}"
                    class="nav-link {{ $current === 'dashboard' ? 'active fw-bold text-warning' : 'text-white' }}">
                    <i class="fa-solid fa-gauge me-2"></i> Dashboard
                </a>
            </li>
            @if (auth()->user()->role == 'admin' || auth()->user()->role == 'staff')
                <li class="nav-item">
                    <a href="{{ url('/products') }}"
                        class="nav-link  {{ $current === 'products' ? 'active fw-bold text-warning' : 'text-white' }}">
                        <i class="fa-solid fa-shirt me-2"></i> Products
                    </a>
                </li>
            @endif
            <li class="nav-item">
                <a href="{{ url('/orders') }}"
                    class="nav-link {{ $current === 'orders' ? 'active fw-bold text-warning' : 'text-white' }}">
                    <i class="fa-solid fa-boxes-stacked me-2"></i> Orders
                </a>
            </li>
            {{-- <li class="nav-item">
                <a href="{{ url('#') }}"
                    class="nav-link text-white {{ $current === 'jobs' ? 'active fw-bold' : '' }}">
                    <i class="fa-solid fa-briefcase me-2"></i> Jobs
                </a>
            </li> --}}
            @if (auth()->user()->role == 'admin' || auth()->user()->role == 'staff')
            <li class="nav-item">
                    <a class="nav-link {{request()->routeIs('customer_groups.*') ? 'active fw-bold text-warning' : 'text-white' }}"
                        href="{{ route('customer_groups.index') }}">
                        <i class="fa fa-users me-2"></i> {{-- Or a more fitting icon --}}
                        Customer Groups
                    </a>
                </li>    
            <li class="nav-item">
                    <a href="{{ url('/users') }}"
                        class="nav-link {{ $current === 'users' ? 'active fw-bold text-warning' : 'text-white' }}">
                        <i class="fa-solid fa-user-group me-2"></i> Users
                    </a>
                </li>
                
            @endif
        </ul>
    </div>
    <div style="position: sticky; bottom: 1rem; left: 1rem; right: 1rem;">
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="btn btn-outline-warning w-100 shadow-sm">
                Logout
            </button>
        </form>
    </div>


</nav>
