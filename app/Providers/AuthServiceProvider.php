<?php

namespace App\Providers;

// Make sure to import the classes
use App\Models\CustomerCommunication;
use App\Policies\CustomerCommunicationPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate; // If you plan to use Gates directly

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        // 'App\Models\Model' => 'App\Policies\ModelPolicy', // Example or other existing policies
        CustomerCommunication::class => CustomerCommunicationPolicy::class, // <-- Add this line
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        Gate::policy(User::class, UserPolicy::class);
    Gate::policy(CustomerCommunication::class, CustomerCommunicationPolicy::class);

    // Your global Gate::before hook (with the corrected condition):
    Gate::before(function ($user, $ability) {
        // Remove the dd() statements you added for debugging!
        if ($user && $user->role === 'admin') { // <-- FIXED LINE
            return true; // Admins bypass all policies/gates
        }
        return null;
        });
    }
}