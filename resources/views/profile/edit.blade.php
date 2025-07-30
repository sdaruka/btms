@extends('layouts.main')
@section('title', 'Edit Profile')

@section('content')
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="fw-bold">Edit Profile</h2>

            <a href="{{ route('users.index') }}" class="btn btn-warning fw-semibold">
                <i class="fa-solid fa-backward me-1"></i> Back
            </a>
        </div>

        @if (session('status'))
            <div class="alert alert-success">{{ session('status') }}</div>
        @endif

        <form method="POST" action="{{ route('profile.update', $user) }}" enctype="multipart/form-data"
            class="card shadow-sm p-4">
            @method('PUT') <!-- Use PUT method for updates -->
            @csrf

            <div class="mb-3">

                <input type="text" name="name" class="form-control" value="{{ old('name', $user->name) }}" required>
                @error('name')
                    <small class="text-danger">{{ $message }}</small>
                @enderror
            </div>

            <div class="mb-3">

                <input type="email" name="email" class="form-control" value="{{ old('email', $user->email) }}"
                    placeholder="email">
                @error('email')
                    <small class="text-danger">{{ $message }}</small>
                @enderror
            </div>

            <div class="mb-3">

                <input type="text" name="phone" class="form-control" value="{{ old('phone', $user->phone) }}">
                @error('phone')
                    <small class="text-danger">{{ $message }}</small>
                @enderror
            </div>
            @php
                $enumRoles = DB::select("SHOW COLUMNS FROM users WHERE Field = 'role'");
                preg_match('/enum\((.*)\)/', $enumRoles[0]->Type, $matches);
                $roles = collect(explode(',', str_replace(["'", '"'], '', $matches[1])));
            @endphp

            <div class="mb-3">
                <select name="role" class="form-select" id="role">
                    <option value="" disabled>Select role</option>
                    @if (auth()->user()->role == 'admin')
                        @foreach ($roles as $roleOption)
                            <option value="{{ $roleOption }}"
                                {{ old('role', $user->role) === $roleOption ? 'selected' : '' }}>
                                {{ ucfirst($roleOption) }}
                            </option>
                        @endforeach
                    @else
                        @foreach ($roles as $roleOption)
                            @if ($roleOption !== 'admin')
                                <option value="{{ $roleOption }}"
                                    {{ old('role', $user->role) === $roleOption ? 'selected' : '' }}>
                                    {{ ucfirst($roleOption) }}
                                </option>
                            @endif
                        @endforeach
                    @endif
                </select>
                @error('role')
                    <small class="text-danger">{{ $message }}</small>
                @enderror
            </div>
            <div class="mb-3">
                <label for="customer_group_id" class="form-label">Customer Group</label>
                <select class="form-select @error('customer_group_id') is-invalid @enderror" id="customer_group_id"
                    name="customer_group_id">
                    <option value="">-- No Group --</option>
                    @foreach ($customerGroups as $group)
                        <option value="{{ $group->id }}"
                            {{ old('customer_group_id', $user->customer_group_id ?? '') == $group->id ? 'selected' : '' }}>
                            {{ $group->name }}
                        </option>
                    @endforeach
                </select>
                @error('customer_group_id')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="mb-3">
                <input type="radio" name="is_active" value="1" {{ $user->is_active ? 'checked' : '' }}>
                <label for="is_active">Active</label>
                <input type="radio" name="is_active" value="0" {{ !$user->is_active ? 'checked' : '' }}>
                <label for="is_inactive">Inactive</label>
            </div>

            <div class="mb-3">

                <input type="text" name="address" class="form-control" value="{{ old('address', $user->address) }}">
                @error('address')
                    <small class="text-danger">{{ $message }}</small>
                @enderror
            </div>
            <div class="mb-3">

                <input type="text" name="country" class="form-control" value="{{ old('country', $user->country) }}">
                @error('country')
                    <small class="text-danger">{{ $message }}</small>
                @enderror
            </div>
            <div class="mb-3">
                <span class="text-warning">Max upload size: 2MB</span>
                <input type="file" name="profile_picture" class="form-control">
                @error('profile_picture')
                    <small class="text-danger">{{ $message }}</small>
                @enderror

                @if ($user->profile_picture)
                    <div class="mt-2">
                        <img src="{{ asset($user->profile_picture) }}" class="rounded" width="80" height="80"
                            alt="Current photo">
                    </div>
                @endif
            </div>
            <hr>
            <h5 class="mb-3">Change Password (optional)</h5>

            <div class="mb-3">

                <input type="password" name="password" class="form-control" placeholder="Leave blank to keep current">
                @error('password')
                    <small class="text-danger">{{ $message }}</small>
                @enderror
            </div>

            <div class="mb-3">

                <input type="password" name="password_confirmation" class="form-control" placeholder="Confirm new password">
            </div>


            <button type="submit" class="btn btn-danger">Update Profile</button>
        </form>
    </div>
@endsection
