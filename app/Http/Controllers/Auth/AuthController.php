<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
// use App\Models\CustomerGroup;

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log; // Make sure this is here!
use Carbon\Carbon; // Import Carbon for date/time manipulation
use Illuminate\Support\Facades\Cookie;

class AuthController extends Controller
{
    // Shorter-term, cache-based rate limiting (e.g., 5 attempts in 1 minute)
    protected $maxLoginAttemptsRateLimiter = 5;
    protected $lockoutDurationInMinutesRateLimiter = 1;

    // Longer-term, database-based lockout (e.g., 10 total failed attempts for 24 hours)
    protected $maxFailedLoginAttemptsDb = 10;
    protected $lockoutDurationInHoursDb = 24;

    // Password Reset Rate Limiting
    protected $maxPasswordResetAttempts = 3;
    protected $passwordResetDecayMinutes = 1;

    protected function username()
    {
        $loginType = filter_var(request()->input('login'), FILTER_VALIDATE_EMAIL) ? 'email' : 'phone';
        request()->merge([$loginType => request()->input('login')]);
        return $loginType;
    }

    protected function limiterKey(Request $request)
    {
        return Str::lower($request->input('login')) . '|' . $request->ip();
    }

    // RateLimiter (cache-based) specific methods
    protected function hasTooManyLoginAttempts(Request $request)
    {
        $key = $this->limiterKey($request);
        $attempts = RateLimiter::attempts($key);
        $tooMany = RateLimiter::tooManyAttempts($key, $this->maxLoginAttemptsRateLimiter);
        Log::info("RateLimiter Check: Key={$key}, Attempts={$attempts}, TooMany={$tooMany}");
        return $tooMany;
    }

    protected function incrementLoginAttempts(Request $request)
    {
        $key = $this->limiterKey($request);
        RateLimiter::hit($key, now()->addMinutes($this->lockoutDurationInMinutesRateLimiter));
        Log::info("RateLimiter Attempts Incremented: Key={$key}, Current Attempts={$this->getCurrentAttempts($key)}");
    }

    protected function clearLoginAttempts(Request $request)
    {
        $key = $this->limiterKey($request);
        RateLimiter::clear($key);
        Log::info("RateLimiter Attempts Cleared: Key={$key}");
    }

    protected function getCurrentAttempts(string $key)
    {
        return RateLimiter::attempts($key);
    }

    // DB-based Lockout Specific Methods
    protected function checkDatabaseLockout(?User $user) // Added ? to make user nullable
    {
        if (!$user) {
            return false; // Cannot be DB locked if user doesn't exist
        }
        if ($user->locked_until && $user->locked_until->isFuture()) {
            return true;
        }
        return false;
    }

    protected function incrementDbFailedLoginAttempts(User $user)
    {
        $user->increment('failed_login_attempts'); // Increment the column
        // Log::info("DB Failed Login Attempts Incremented for user ID: {$user->id}. New count: {$user->failed_login_attempts}");

        if ($user->failed_login_attempts >= $this->maxFailedLoginAttemptsDb) {
            $user->locked_until = now()->addHours($this->lockoutDurationInHoursDb);
            $user->save();
            // Log::warning("User ID: {$user->id} locked for {$this->lockoutDurationInHoursDb} hours until: {$user->locked_until}");
        } else {
            $user->save(); // Save the incremented value
        }
    }

    protected function resetDbFailedLoginAttempts(User $user)
    {
        $user->failed_login_attempts = 0;
        $user->locked_until = null;
        $user->save();
        // Log::info("DB Failed Login Attempts Reset for user ID: {$user->id}");
    }

    // Consolidated Lockout Response (for both types)
    protected function sendLockoutResponse(Request $request, string $reason = 'rate_limiter')
    {
        $message = '';
        if ($reason === 'rate_limiter') {
            $seconds = RateLimiter::availableIn($this->limiterKey($request));
            $message = "Too many login attempts. Please try again in {$seconds} seconds.";
            // Log::warning("Login Lockout Triggered (RateLimiter): User IP {$request->ip()}, attempts exceeded. Message: {$message}");
        } elseif ($reason === 'db_lockout') {
            // Fetch the user again to get the latest locked_until (or pass it in if available)
            $loginValue = $request->input('login');
            $field = filter_var($loginValue, FILTER_VALIDATE_EMAIL) ? 'email' : 'phone';
            $user = User::where($field, $loginValue)->first();

            if ($user && $user->locked_until) {
                // Calculate time remaining in a more user-friendly way (days, hours, minutes)
                $diff = Carbon::now()->diff($user->locked_until);
                $timeRemainingParts = [];
                if ($diff->days > 0) {
                    $timeRemainingParts[] = "{$diff->days} day(s)";
                }
                if ($diff->h > 0) {
                    $timeRemainingParts[] = "{$diff->h} hour(s)";
                }
                if ($diff->i > 0) {
                    $timeRemainingParts[] = "{$diff->i} minute(s)";
                }
                if ($diff->s > 0 && empty($timeRemainingParts)) { // Only show seconds if no larger unit is present
                    $timeRemainingParts[] = "{$diff->s} second(s)";
                }
                $timeRemainingStr = implode(', ', $timeRemainingParts);
                if (empty($timeRemainingStr)) {
                    $timeRemainingStr = "a moment"; // Handle cases where time is very short
                }

                $message = "Your account is locked due to too many failed attempts. Please try again in approximately {$timeRemainingStr}.";
                Log::warning("Login Lockout Triggered (DB): User ID {$user->id}, locked until {$user->locked_until}. Message: {$message}");
            } else {
                 $message = "Your account is temporarily locked due to failed attempts. Please try again later.";
                 Log::warning("Login Lockout Triggered (DB): User not found or locked_until not set unexpectedly.");
            }
        }

        throw ValidationException::withMessages([
            'login' => [$message],
        ])->redirectTo(route('login.view'));
    }

    public function ShowRegister()
    {
        return view('auth.register');
    }

    public function register(Request $request)
    {
        $loginType = filter_var($request->input('login_field'), FILTER_VALIDATE_EMAIL) ? 'email' : 'phone';

        $rules = [
            'name' => 'required|string|max:255',
            'password' => 'required|min:6',
            'repassword' => 'required|same:password',
        ];

        if ($loginType === 'phone') {
            $rules['login_field'] = 'required|digits:10|unique:users,phone';
        } else {
            $rules['login_field'] = 'required|email|unique:users,email';
        }

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $user = new User();
        $user->name = $request->name;
        $user->password = Hash::make($request->password);
        $user->is_active = true;

        if ($loginType === 'phone') {
            $user->phone = $request->input('login_field');
        } else {
            $user->email = $request->input('login_field');
        }
        $user->save();

        auth()->login($user);

        Session::flash('success', 'Registration successful! You are now logged in.');
        return redirect()->route('dashboard.index');
    }

    public function login(Request $request)
    {
        $request->validate([
            'login' => 'required|string',
            'password' => 'required|string',
        ]);

        // Log::info("Login attempt for: " . $request->input('login') . " from IP: " . $request->ip());

        // First, attempt to find the user based on the login input.
        // This is crucial for checking DB-based lockouts specific to a user.
        $loginField = $this->username();
        $user = User::where($loginField, $request->input('login'))->first();

        // If a user is found AND their account is locked in the database (24-hour lockout)
        if ($user && $this->checkDatabaseLockout($user)) {
            // Log::warning("User ID: {$user->id} is currently locked by DB lockout. Prioritizing this message.");
            $this->sendLockoutResponse($request, 'db_lockout');
            // Execution stops here due to ValidationException
        }

        // Now, check the cache-based Rate Limiter (e.g., 5 attempts in 1 minute)
        // This acts as a general flood protection for any login attempts,
        // whether against existing or non-existent users from a given IP.
        if ($this->hasTooManyLoginAttempts($request)) {
            // Log::warning("IP/Login combination hit RateLimiter limits. Displaying RateLimiter lockout message.");
            $this->sendLockoutResponse($request, 'rate_limiter');
            // Execution stops here due to ValidationException
        }

        // Attempt authentication using the dynamically determined field
        $credentials = [
            $loginField => $request->input('login'),
            'password' => $request->input('password'),
        ];
        // Log::info("Attempting authentication with credentials: " . json_encode($credentials));

        if (Auth::attempt($credentials)) {
            // Log::info("Auth::attempt successful for user ID: " . Auth::id());
            // Check if the user is active
            if (!Auth::user()->is_active) {
                // Log::warning("User is inactive: " . Auth::id() . ". Logging out.");
                Auth::logout();
                Session::flash('error', 'Your account is currently inactive. Please contact support.');
                // For an inactive user trying to log in, we should still increment their DB failed attempts
                // as it's still a "failed" access attempt from their perspective.
                if ($user) { // Only if user was found
                    $this->incrementDbFailedLoginAttempts($user);
                }
                $this->incrementLoginAttempts($request); // Also increment rate limiter for this IP
                return redirect()->route('login.view');
            }

            // Authentication successful
            $request->session()->regenerate();
            // Clear both rate limiter and DB failed attempts on success
            $this->clearLoginAttempts($request);
            if ($user) { // Ensure user exists before resetting DB attempts
                $this->resetDbFailedLoginAttempts($user);
            }
            Session::flash('success', 'Logged in successfully!');
            // Log::info("Login success for user: " . Auth::user()->email ?? Auth::user()->phone);
            $cookie = Cookie::make('session_id', 'abc123', 60, '/', null, true, true, false, 'Strict');

            return redirect()->route('dashboard.index');
        }

        // If authentication failed
        // Log::info("Auth::attempt failed for: " . $request->input('login'));
        // Always increment the RateLimiter attempts for this IP/login combination
        $this->incrementLoginAttempts($request);

        // Only increment DB failed attempts if a user with that login was actually found
        if ($user) {
            $this->incrementDbFailedLoginAttempts($user);
        }

        Session::flash('error', 'Invalid login credentials.');
        return redirect()->route('login.view');
    }

    public function resetpassword()
    {
        return view('auth.reset-password');
    }

    public function resetpasswordpost(Request $request)
    {
        $request->validate([
            'login' => 'required|string',
            'password' => 'required|min:6',
            'repassword' => 'required|same:password',
        ]);

        $loginValue = $request->input('login');
        $field = filter_var($loginValue, FILTER_VALIDATE_EMAIL) ? 'email' : 'phone';

        $key = Str::lower($loginValue) . '|' . $request->ip() . '|reset';
        if (RateLimiter::tooManyAttempts($key, $this->maxPasswordResetAttempts)) {
            $seconds = RateLimiter::availableIn($key);
            Session::flash('error', "Too many password reset requests. Please try again in {$seconds} seconds.");
            return redirect()->back()->withInput();
        }

        RateLimiter::hit($key, now()->addMinutes($this->passwordResetDecayMinutes));

        $user = User::where($field, $loginValue)->first();

        if (!$user) {
            Session::flash('error', 'User not found with the provided email or phone number.');
            return redirect()->back()->withInput();
        }

        // Reset user's password
        $user->password = Hash::make($request->input('password'));
        // Also reset DB failed login attempts and lockout on successful password reset
        $this->resetDbFailedLoginAttempts($user);
        $user->save();

        RateLimiter::clear($key); // Clear rate limiter for password reset

        Session::flash('success', 'Password successfully updated. You can now log in.');
        return redirect()->route('login.view');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        Session::flush();

        return redirect('/')->with('status', 'You have been logged out successfully.');
    }
}