<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\UserController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\CustomerGroupController; // Import the controller
use App\Http\Controllers\Api\CustomerCommunicationController; // Import the API controller
use App\Http\Controllers\Api\CustomerNoteController; // Will be used later for notes


// 🟢 Guest Routes (unauthenticated)
Route::middleware('guest')->group(function () {
    Route::get('/', fn() => view('auth.login'))->name('login.view');
    Route::get('/showregister', [AuthController::class, 'showRegister']);
    Route::post('/register', [AuthController::class, 'register'])->name('register');
    Route::post('/login', [AuthController::class, 'login'])->name('login');
    Route::get('/resetpassword', [AuthController::class, 'resetPassword']);
    Route::post('/resetpasswordpost', [AuthController::class, 'resetPasswordPost'])->name('resetpassword.post');
});

// 🔐 Authenticated Routes
Route::middleware(['check.auth'])->group(function () {
    // Logout (must be POST for CSRF safety)
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard.index');
   // Route::post('/customer', [UserController::class, 'storeCustomer']);
Route::post('/customer', [UserController::class, 'storeCustomer'])->name('customers.store.simple');

    
    Route::prefix('my-profile')->group(function () { // Changed from 'profile' to 'my-profile' to distinguish general users from their own profiles
        Route::get('/{user}', [UserController::class, 'showProfile'])->name('profile.show'); // Pass the user model
        Route::get('/{user}/edit', [UserController::class, 'edit'])->name('profile.edit'); // Use {user} instead of {id}
        Route::put('/{user}', [UserController::class, 'update'])->name('profile.update'); // Use PUT/PATCH for updates, and {user}
        // Note: HTML forms only support GET/POST. For PUT/DELETE, you need to use @method('PUT') or @method('DELETE') in your Blade form.
    });

    Route::prefix('users')->group(function () {
        Route::get('/', [UserController::class, 'index'])->name('users.index');
        Route::get('/create', [UserController::class, 'create'])->name('users.create');
        Route::post('/', [UserController::class, 'store'])->name('users.store'); // POST to root of resource for store
        Route::get('/{user}/edit', [UserController::class, 'edit'])->name('users.edit'); // Use {user} for route model binding
        Route::put('/{user}', [UserController::class, 'update'])->name('users.update'); // Use PUT/PATCH for updates, and {user}
        Route::delete('/{user}', [UserController::class, 'destroy'])->name('users.destroy'); // Use DELETE for destroy, and {user}

        // New routes for soft deletes and force deletion
        Route::post('/{user}/restore', [UserController::class, 'restore'])->name('users.restore'); // Use {user}
        Route::delete('/{user}/force-delete', [UserController::class, 'forceDelete'])->name('users.forceDelete'); // Use {user}
    });
    // Products
    Route::prefix('products')->group(function () {
        Route::get('/', [ProductController::class, 'index'])->name('products.index');
        Route::get('/create', [ProductController::class, 'create'])->name('products.create');
        Route::post('/store', [ProductController::class, 'store'])->name('products.store');
        Route::get('/{product}', [ProductController::class, 'show'])->name('products.show');
        Route::get('/{product}/edit', [ProductController::class, 'edit'])->name('products.edit');
        Route::post('/{product}/update', [ProductController::class, 'update'])->name('products.update');
        Route::delete('/{product}', [ProductController::class, 'destroy'])->name('products.destroy');
    });

    // Orders
    Route::prefix('orders')->group(function () {
        Route::get('/', [OrderController::class, 'index'])->name('orders.index');
        Route::get('/create', [OrderController::class, 'create'])->name('orders.create');
        Route::post('/store', [OrderController::class, 'store'])->name('orders.store');
        Route::get('/{order}', [OrderController::class, 'show'])->name('orders.show');
        Route::get('/{order}/print', [OrderController::class, 'print'])->name('orders.print');
        Route::get('/{order}/edit', [OrderController::class, 'edit'])->name('orders.edit');
        Route::post('/{order}/update', [OrderController::class, 'update'])->name('orders.update');
        Route::delete('/{order}', [OrderController::class, 'destroy'])->name('orders.destroy');
        Route::patch('/{order}/status', [OrderController::class, 'updateStatus'])->name('orders.updateStatus');
        Route::post('/{order}/assign', [OrderController::class, 'assignOrder'])->name('orders.assign');

        Route::get('/measurements/{user}', [OrderController::class, 'loadPreviousMeasurements'])
            ->missing(fn() => response()->json(['error' => 'User not found'], 404));
    });

    // Notifications
    Route::prefix('notifications')->group(function () {
        Route::get('/', [NotificationController::class, 'index'])->name('notifications');
        Route::get('/read/{id}', [NotificationController::class, 'markAsRead'])->name('notifications.read');
        Route::post('/read-all', [NotificationController::class, 'markAllAsRead'])->name('notifications.read_all');
    });

    // Customer Groups
   
    Route::resource('customer_groups', CustomerGroupController::class);
    Route::apiResource('customers.communications', CustomerCommunicationController::class)->except(['show']);

});
