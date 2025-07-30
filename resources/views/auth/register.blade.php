@extends('layouts.main')
@section('title', 'Register')

@section('content')
<div class="d-flex align-items-center justify-content-center max-vh-100">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-5 col-lg-4">
                <div class="card shadow-lg">
                    <!-- Card Header -->
                    <div class="card-header text-center">
                        <div class="d-flex align-items-center justify-content-center mb-3">
                            <img src="{{ asset('images/logo.png') }}" alt="Bhumis Logo" class="img-fluid" style="max-width: 4vw;">
                        </div>
                        <h6 class="fw-bold text-dark">New User Register</h6>
                    </div>

                    <!-- Card Body -->
                    <div class="card-body p-4" x-data="{ activeTab: 'phone' }">
                        @if (Session::has('error'))
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                {{ Session::get('error') }}
                                <button type="button" class="btn-close" @click="$el.remove()"></button>
                            </div>
                        @endif

                        <!-- Tab Navigation -->
                        <div class="nav nav-tabs justify-content-around mb-2" role="tablist">
                            <button type="button"
                                class="tab-button nav-link"
                                :class="activeTab === 'phone' ? 'active' : ''"
                                @click="activeTab = 'phone'"
                                role="tab" aria-selected="true">
                                Register with Phone
                            </button>
                            <button type="button"
                                class="tab-button nav-link"
                                :class="activeTab === 'email' ? 'active' : ''"
                                @click="activeTab = 'email'"
                                role="tab" aria-selected="false">
                                Register with Email
                            </button>
                        </div>

                        <!-- Tab Content -->
                        <div>
                            <!-- Phone Registration Tab -->
                            <div x-show="activeTab === 'phone'" x-transition role="tabpanel">
                                <form method="POST" action="{{ route('register') }}">
                                    @csrf
                                    <div class="mb-3">
                                        <input type="text" class="form-control" name="name" placeholder="Your Name"
                                               required autofocus value="{{ old('name') }}">
                                        @error('name')
                                            <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div class="mb-3">
                                        <input type="tel" class="form-control" name="phone" placeholder="e.g., 1234567890"
                                               pattern="[0-9]{10}" maxlength="10" required value="{{ old('phone') }}">
                                        @error('phone')
                                            <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div class="mb-3">
                                        <input type="password" class="form-control" name="password" placeholder="Password" required>
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
                                    <button type="submit" class="btn btn-danger w-100 py-2">Register with Phone</button>
                                </form>
                            </div>

                            <!-- Email Registration Tab -->
                            <div x-show="activeTab === 'email'" x-transition role="tabpanel">
                                <form method="POST" action="{{ route('register') }}">
                                    @csrf
                                    <div class="mb-3">
                                        <input type="text" class="form-control" name="name" placeholder="Your Name"
                                               required autofocus value="{{ old('name') }}">
                                        @error('name')
                                            <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div class="mb-3">
                                        <input type="email" class="form-control" name="email" placeholder="you@example.com"
                                               required value="{{ old('email') }}">
                                        @error('email')
                                            <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div class="mb-3">
                                        <input type="password" class="form-control" name="password" placeholder="Password" required>
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
                                    <button type="submit" class="btn btn-danger w-100 py-2">Register with Email</button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Card Footer -->
                    <div class="card-footer text-center py-3">
                        <span class="text-muted">Already have an account?</span>
                        <a href="{{ url('/') }}" class="ms-2 text-decoration-none text-primary fw-semibold">Login here</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection