@extends('layouts.main')

@section('content')
    <div class="container">
        <h2 class="fw-bold mb-4">Create User</h2>

        @if (session('status'))
            <div class="alert alert-success">{{ session('status') }}</div>
        @elseif (session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        <form method="POST" action="{{ route('users.store') }}" enctype="multipart/form-data" class="card shadow-sm p-4">
            @csrf

            <div class="mb-3">

                <input type="text" name="name" placeholder="Your Name" class="form-control" value="{{ old('name') }}"
                    required>
                @error('name')
                    <small class="text-danger">{{ $message }}</small>
                @enderror
            </div>

            <div class="mb-3">

                <input type="email" name="email" class="form-control" value="{{ old('email') }}" placeholder="email"
                    required>
                @error('email')
                    <small class="text-danger">{{ $message }}</small>
                @enderror
            </div>

            <div class="mb-3">

                <input type="text" name="phone" placeholder="Your Phone Number(10 digits)" class="form-control"
                    value="{{ old('phone') }}">
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
                    @foreach ($roles as $roleOption)
                        <option value="{{ $roleOption }}" {{ old('role') === $roleOption ? 'selected' : '' }}>
                            {{ ucfirst($roleOption) }}
                        </option>
                    @endforeach
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
                <input type="radio" name="is_active" value="1" {{ old('is_active') == '1' ? 'checked' : '' }}>
                <label for="is_active">Active</label>
                <input type="radio" name="is_active" value="0" {{ old('is_active') == '0' ? 'checked' : '' }}>
                <label for="is_inactive">Inactive</label>
            </div>
            
            <div class="mb-3">

                <input type="text" name="address" placeholder="Address" class="form-control"
                    value="{{ old('address') }}">
                @error('address')
                    <small class="text-danger">{{ $message }}</small>
                @enderror
            </div>
            <div class="mb-3">

                <input type="text" value="India" name="country" class="form-control" value="{{ old('country') }}">
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

                {{-- @if ($user->profile_picture)
                    <div class="mt-2">
                        <img src="{{ asset($user->profile_picture) }}" class="rounded" width="80" height="80"
                            alt="Current photo">
                    </div>
                @endif --}}
            </div>
            <hr>
            <h5 class="mb-3">Password (optional)</h5>

            <div class="mb-3">

                <input type="password" value="123456" name="password" class="form-control"
                    placeholder="Leave blank to keep current">
                @error('password')
                    <small class="text-danger">{{ $message }}</small>
                @enderror
            </div>

            <div class="mb-3">

                <input type="password" name="password_confirmation" value="123456" class="form-control"
                    placeholder="Confirm new password">
            </div>


            <button type="submit" class="btn btn-danger">Save</button>
        </form>
    </div>
@endsection
