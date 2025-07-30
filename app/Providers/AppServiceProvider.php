<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;

use App\Models\Notification;
use App\Models\User; // Import your User model
use App\Policies\UserPolicy; // Import your UserPolicy
use Illuminate\Support\Facades\Gate; // Import the Gate facade
// use App\Providers\AuthServiceProvider;
use Illuminate\Support\Facades\Auth;
class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
   public function boot(): void
{
    Schema::defaultStringLength(191);

     view()->composer('layouts.top-nav', function ($view) {
            if (Auth::check()) {
                $user = Auth::user();
                $unreadNotifications = $user->notifications()->where('is_read', false)->latest()->get();
                
                $view->with([
                    'notificationCount' => $unreadNotifications->count(),
                    'notifications' => $unreadNotifications,
                ]);
            } else {
                $view->with([
                    'notificationCount' => 0,
                    'notifications' => collect(),
                ]);
            }
        });

}
}
