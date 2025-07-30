@extends('layouts.main')

@section('title', 'Login')

@section('content')
<div class="container d-flex justify-content-center align-items-center" style="min-height: 90vh;">
    <div class="card shadow-lg border-0" style="max-width: 420px; width: 100%;">
        <div
            class="card-body p-4"
            x-data="{
                activeTab: '{{ session('active_tab', 'phone') }}',
                lockoutSeconds: 0,
                intervalId: null,

                init() {
                    // Initialize countdown if a lockout message is present on load
                    this.extractLockoutSeconds();

                    // Watch for changes in activeTab to re-evaluate lockout state
                    this.$watch('activeTab', () => {
                        this.extractLockoutSeconds();
                    });
                },

                extractLockoutSeconds() {
                    // Find the error message element based on the currently active tab
                    let errorMessageElement;
                    if (this.activeTab === 'phone' && this.$refs.phoneLoginErrorMessage) {
                        errorMessageElement = this.$refs.phoneLoginErrorMessage;
                    } else if (this.activeTab === 'email' && this.$refs.emailLoginErrorMessage) {
                        errorMessageElement = this.$refs.emailLoginErrorMessage;
                    }

                    if (errorMessageElement && errorMessageElement.innerText.includes('Too many login attempts')) {
                        const message = errorMessageElement.innerText;
                        const match = message.match(/try again in (\d+) seconds/);
                        if (match) {
                            this.lockoutSeconds = parseInt(match[1]);
                            this.startCountdown();
                        } else {
                            // If it's an error message but not the lockout message, display it normally
                            this.stopCountdown();
                            this.lockoutSeconds = 0;
                        }
                    } else {
                        this.stopCountdown();
                        this.lockoutSeconds = 0; // Reset if no lockout message
                    }
                },

                startCountdown() {
                    this.stopCountdown(); // Clear any existing interval
                    if (this.lockoutSeconds > 0) {
                        this.intervalId = setInterval(() => {
                            if (this.lockoutSeconds > 0) {
                                this.lockoutSeconds--;
                            } else {
                                this.stopCountdown();
                                // After countdown, explicitly remove the message or trigger a re-render
                                // For now, just hide it
                            }
                        }, 1000);
                    }
                },

                stopCountdown() {
                    if (this.intervalId) {
                        clearInterval(this.intervalId);
                        this.intervalId = null;
                    }
                }
            }"
        >
            <div class="text-center mb-4">
                <img src="{{ asset('images/logo.png') }}" alt="Logo" style="height: 50px;">
                <h4 class="mt-3 fw-bold text-dark">Login to Bhumis</h4>
            </div>

            @if (Session::has('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    {{ Session::get('error') }}
                    <button type="button" class="btn-close" @click="$el.remove()"></button>
                </div>
            @endif
            @if ($errors->has('form'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    {{ $errors->first('form') }}
                    <button type="button" class="btn-close" @click="$el.remove()"></button>
                </div>
            @endif

            <div class="nav nav-tabs nav-justified mb-3" role="tablist">
                <button
                    class="nav-link"
                    :class="activeTab === 'phone' ? 'active' : ''"
                    @click="activeTab = 'phone'"
                    type="button"
                    role="tab"
                >Phone</button>
                <button
                    class="nav-link"
                    :class="activeTab === 'email' ? 'active' : ''"
                    @click="activeTab = 'email'"
                    type="button"
                    role="tab"
                >Email</button>
            </div>

            <div>
                <div x-show="activeTab === 'phone'" x-transition role="tabpanel">
                    <form method="POST" action="{{ url('/login') }}">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">Phone Number</label>
                            <input type="tel" name="login" autofocus autocomplete="off"
                                class="form-control @error('login') is-invalid @enderror"
                                placeholder="10-digit number" maxlength="10" required
                                value="{{ old('login') }}">
                            @error('login')
                                {{-- KEY CHANGE HERE: x-show to control visibility --}}
                                <div class="invalid-feedback" x-ref="phoneLoginErrorMessage" x-show="lockoutSeconds > 0 || !($el.innerText.includes('Too many login attempts') && lockoutSeconds === 0)">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Password</label>
                            <input type="password" name="password" autocomplete="off"
                                class="form-control @error('password') is-invalid @enderror" required>
                            @error('password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <template x-if="activeTab === 'phone' && lockoutSeconds > 0">
                            <p class="text-danger text-center mt-2">
                                Please wait <span x-text="lockoutSeconds"></span> seconds.
                            </p>
                        </template>

                        <button class="btn btn-danger w-100" :disabled="lockoutSeconds > 0">Login with Phone</button>
                    </form>
                </div>

                <div x-show="activeTab === 'email'" x-transition role="tabpanel">
                    <form method="POST" action="{{ url('/login') }}">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">Email Address</label>
                            <input type="email" autocomplete="off" name="login"
                                class="form-control @error('login') is-invalid @enderror" required
                                value="{{ old('login') }}">
                            @error('login')
                                {{-- KEY CHANGE HERE: x-show to control visibility --}}
                                <div class="invalid-feedback" x-ref="emailLoginErrorMessage" x-show="lockoutSeconds > 0 || !($el.innerText.includes('Too many login attempts') && lockoutSeconds === 0)">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Password</label>
                            <input type="password" name="password" autocomplete="off"
                                class="form-control @error('password') is-invalid @enderror" required>
                            @error('password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <template x-if="activeTab === 'email' && lockoutSeconds > 0">
                            <p class="text-danger text-center mt-2">
                                Please wait <span x-text="lockoutSeconds"></span> seconds.
                            </p>
                        </template>

                        <button class="btn btn-danger w-100" :disabled="lockoutSeconds > 0">Login with Email</button>
                    </form>
                </div>
            </div>

            <div class="d-flex justify-content-between mt-3">
                <a href="{{ url('/showregister') }}" class="text-decoration-none">Register</a>
                <a href="{{ url('/resetpassword') }}" class="text-decoration-none">Forgot Password?</a>
            </div>
        </div>
    </div>
</div>
@endsection