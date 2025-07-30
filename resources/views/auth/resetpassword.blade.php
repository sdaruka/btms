@extends('layouts.main')
@section('title', 'Reset Password')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-5 col-lg-4">
            <div class="card shadow-lg">
                <!-- Card Header -->
                <div class="card-header text-center">
                    <div class="d-flex align-items-center justify-content-center mb-3">
                        <img src="{{ asset('images/logo.png') }}" alt="Bhumis Logo" class="img-fluid" style="max-width: 5vw;">
                    </div>
                    <h3 class="fw-bold text-dark">Reset Password</h3>
                </div>

                <!-- Card Body -->
                <div class="card-body p-4" x-data="{ activeTab: '{{ session('active_tab', 'phone') }}' }">
                    @if (Session::has('error'))
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            {{ Session::get('error') }}
                            <button type="button" class="btn-close" @click="$el.remove()" aria-label="Close"></button>
                        </div>
                    @elseif (Session::has('status'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            {{ Session::get('status') }}
                            <button type="button" class="btn-close" @click="$el.remove()" aria-label="Close"></button>
                        </div>
                    @endif

                    <!-- Tab Navigation -->
                    <div class="nav nav-tabs justify-content-around mb-2" role="tablist">
                        <button type="button" class="tab-button nav-link"
                            :class="activeTab === 'phone' ? 'active' : ''"
                            @click="activeTab = 'phone'" role="tab" aria-selected="true">
                            Reset with Phone
                        </button>
                        <button type="button" class="tab-button nav-link"
                            :class="activeTab === 'email' ? 'active' : ''"
                            @click="activeTab = 'email'" role="tab" aria-selected="false">
                            Reset with Email
                        </button>
                    </div>

                    <!-- Tab Content -->
                    <div>
                        <!-- Phone Reset Tab -->
                        <div x-show="activeTab === 'phone'" x-transition role="tabpanel">
                            <form method="POST" action="{{ url('/resetpasswordpost') }}">
                                @csrf
                                <div class="mb-3">
                                    <input type="tel" class="form-control" name="login" placeholder="Phone Number"
                                        maxlength="10" pattern="[0-9]{10}" required value="{{ old('login') }}" autofocus>
                                    @error('login')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>
                                <div class="mb-4">
                                    <input type="password" class="form-control" name="password" placeholder="New Password" required>
                                    @error('password')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>
                                <div class="mb-4">
                                    <input type="password" class="form-control" name="repassword" placeholder="Re-enter Password" required>
                                    @error('repassword')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>
                                <button type="submit" class="btn btn-danger w-100 py-2">Reset Password</button>
                            </form>
                        </div>

                        <!-- Email Reset Tab -->
                        <div x-show="activeTab === 'email'" x-transition role="tabpanel">
                            <form method="POST" action="{{ url('/resetpasswordpost') }}">
                                @csrf
                                <div class="mb-3">
                                    <input type="email" class="form-control" name="login" placeholder="Email Address"
                                        required value="{{ old('login') }}">
                                    @error('login')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>
                                <div class="mb-4">
                                    <input type="password" class="form-control" name="password" placeholder="New Password" required>
                                    @error('password')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>
                                <div class="mb-4">
                                    <input type="password" class="form-control" name="repassword" placeholder="Re-enter Password" required>
                                    @error('repassword')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>
                                <button type="submit" class="btn btn-danger w-100 py-2">Reset Password</button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Card Footer -->
                <div class="card-footer text-end">
                    <a href="{{ url('/') }}" class="ms-2 text-decoration-none text-primary fw-semibold">Login</a>
                    <span class="text-muted ms-2 fw-semibold">|</span>
                    <a href="{{ url('/showregister') }}" class="ms-2 text-decoration-none text-primary fw-semibold">Create New Account</a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection