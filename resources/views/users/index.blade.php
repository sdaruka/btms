@extends('layouts.main')

@section('content')
    <div class="container">
        <div class="row mb-4 g-2 align-items-center">
            <div class="col-12 col-md-4">
                <h2 class="fw-bold mb-0">Manage Users</h2>
            </div>
            @if (Session::has('error'))
                <div class="col-12 mt-2"> {{-- Added col-12 and mt-2 for better spacing --}}
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        {{ Session::get('error') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                </div>
            @endif
            @if (Session::has('success')) {{-- Add success message display --}}
                <div class="col-12 mt-2">
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        {{ Session::get('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                </div>
            @endif

            <div class="col-12 col-md-8"> {{-- Adjusted column width for filters --}}
                <form action="{{ route('users.index') }}" method="GET" class="row g-2 align-items-center">
                    <div class="col-12 col-md-5"> {{-- Search input --}}
                        <input type="text" name="search" value="{{ request('search') }}" class="form-control"
                            placeholder="Search by name, phone or email">
                    </div>
                    <div class="col-12 col-md-3"> {{-- Role Filter --}}
                        <select name="role" class="form-select">
                            <option value="all">All Roles</option>
                            <option value="admin" {{ request('role') == 'admin' ? 'selected' : '' }}>Admin</option>
                            <option value="staff" {{ request('role') == 'staff' ? 'selected' : '' }}>Staff</option>
                            <option value="customer" {{ request('role') == 'customer' ? 'selected' : '' }}>Customer</option>
                            <option value="tailor" {{ request('role') == 'tailor' ? 'selected' : '' }}>Tailor</option>
                            <option value="artisan" {{ request('role') == 'artisan' ? 'selected' : '' }}>Artisan</option>
                        </select>
                    </div>
                    <div class="col-12 col-md-3"> {{-- Status Filter --}}
                        <select name="status" class="form-select">
                            <option value="all">All Statuses</option>
                            <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
                            <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                            <option value="deleted" {{ request('status') == 'deleted' ? 'selected' : '' }}>Deleted (Soft)</option>
                        </select>
                    </div>
                    <div class="col-12 col-md-1"> {{-- Submit button --}}
                        <button type="submit" class="btn btn-success w-100">
                            <i class="fa fa-filter"></i> {{-- Changed to filter icon --}}
                        </button>
                    </div>
                </form>
            </div>
            <div class="col-12 col-md-2 text-md-end">
                @can('create', App\Models\User::class) {{-- Authorization check for Add User button --}}
                    <a href="{{ route('users.create') }}" class="btn btn-warning w-100 w-md-auto">
                        <i class="fa fa-user-plus me-1"></i> Add User
                    </a>
                @endcan
            </div>
        </div>

        @if ($users->count())
            <div class="table-responsive-sm">
                <table class="table table-striped table-bordered align-middle shadow-sm w-100">
                    <thead>
                        <tr class="bg-dark text-white">
                            <th>#</th>
                            <th>Profile</th>
                            <th>Name & Phone</th> {{-- Combined for better display --}}
                            <th>Email</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($users as $user)
                            <tr>
                                <td>{{ $loop->iteration + $users->firstItem() - 1 }}</td> {{-- Correct iteration for pagination --}}
                                <td>
                                    <img src="{{ $user->profile_picture ? asset( $user->profile_picture) : asset('images/default-user.svg') }}"
                                        class="rounded-circle shadow" width="40" height="40" alt="avatar">
                                </td>
                                <td>
                                    {{ $user->name }} <br>
                                    {{ $user->phone }}
                                </td>
                                <td>{{ $user->email }}</td> {{-- Show email now --}}
                                <td>
                                    <span class="badge bg-secondary">{{ ucfirst($user->role) }}</span>
                                </td>
                                <td>
                                    @if ($user->deleted_at)
                                        <span class="badge bg-danger">Soft Deleted</span>
                                    @elseif ($user->is_active)
                                        <span class="badge bg-success">Active</span>
                                    @else
                                        <span class="badge bg-danger">Inactive</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <div class="dropdown">
                                        <button class="btn btn-light border-0" type="button" data-bs-toggle="dropdown"
                                            aria-expanded="false">
                                            <i class="fa-solid fa-ellipsis-vertical"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            {{-- View Profile --}}
                                            @can('view', $user)
                                                <li>
                                                    <a class="dropdown-item"
                                                        href="{{ route('profile.show', $user) }}"> {{-- Corrected route name and parameter --}}
                                                        <i class="fa fa-eye me-2 text-info"></i> View
                                                    </a>
                                                </li>
                                            @endcan

                                            {{-- Edit User (General User Management) --}}
                                            @can('update', $user)
                                                <li>
                                                    <a class="dropdown-item" href="{{ route('profile.edit', $user) }}"> {{-- Corrected route name and parameter --}}
                                                        <i class="fa fa-edit me-2 text-warning"></i> Edit
                                                    </a>
                                                </li>
                                            @endcan

                                            {{-- Soft Delete User --}}
                                            @can('delete', $user)
                                                @if (!$user->deleted_at) {{-- Only show delete if not already soft deleted --}}
                                                    <li>
                                                        <form action="{{ route('users.destroy', $user) }}" method="POST"
                                                            onsubmit="return confirm('Are you sure you want to delete {{ $user->name }}? This can be undone later.')">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="dropdown-item text-danger">
                                                                <i class="fa fa-trash me-2"></i> Soft Delete
                                                            </button>
                                                        </form>
                                                    </li>
                                                @endif
                                            @endcan

                                            {{-- Restore User --}}
                                            @can('restore', $user)
                                                @if ($user->deleted_at) {{-- Only show restore if soft deleted --}}
                                                    <li>
                                                        <form action="{{ route('users.restore', $user) }}" method="POST"
                                                            onsubmit="return confirm('Are you sure you want to restore {{ $user->name }}?')">
                                                            @csrf
                                                            <button type="submit" class="dropdown-item text-success">
                                                                <i class="fa fa-undo me-2"></i> Restore
                                                            </button>
                                                        </form>
                                                    </li>
                                                @endif
                                            @endcan

                                            {{-- Force Delete User --}}
                                            @can('forceDelete', $user)
                                                @if ($user->deleted_at) {{-- Typically only allow force delete on soft deleted --}}
                                                    <li>
                                                        <form action="{{ route('users.forceDelete', $user) }}" method="POST"
                                                            onsubmit="return confirm('WARNING: Are you sure you want to PERMANENTLY delete {{ $user->name }}? This cannot be undone!')">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="dropdown-item text-danger">
                                                                <i class="fa fa-times-circle me-2"></i> Force Delete
                                                            </button>
                                                        </form>
                                                    </li>
                                                @endif
                                            @endcan

                                        </ul>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                {{ $users->links('pagination::bootstrap-5') }}
            </div>
        @else
            <div class="alert alert-info">No users found.</div>
        @endif
    </div>
@endsection