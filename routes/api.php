
<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\UserController; // Make sure to import your UserController

// Route::middleware(['web', 'check.auth'])->post('/api/fcm-token', function (Request $request) {
//      Route::post('/api/fcm-token', [UserController::class, 'updateFcmToken'])->name('api.fcm.token');

// });

// Route::post('/fcm-token', [UserController::class, 'updateFcmToken'])->name('api.fcm.token')->middleware('check.auth');

Route::post('/fcm-token', [UserController::class, 'updateFcmToken'])
    ->middleware(['web', 'check.auth'])
    ->name('api.fcm.token');