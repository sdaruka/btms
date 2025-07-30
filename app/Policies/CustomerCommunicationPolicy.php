<?php

namespace App\Policies;

use App\Models\User;
use App\Models\CustomerCommunication;
use Illuminate\Auth\Access\Response;

class CustomerCommunicationPolicy
{
    /**
     * Determine whether the user can view any models.
     * This applies to viewing the list of communications for a customer.
     */
    public function viewAny(User $user, User $customer): bool
    {
        // Only admins or staff can view communications for any customer
        // Or, staff can only view communications for customers they manage
        return $user->isAdmin() || $user->isStaff(); // Assuming isAdmin() / isStaff() methods on User model
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user, User $customer): bool
    {
        // Only admins or staff can create communications for a customer
        return $user->isAdmin() || $user->isStaff();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, CustomerCommunication $customerCommunication): bool
    {
        // Only admins can update any communication
        // Staff can update only communications they logged themselves
        return $user->isAdmin() || ($user->isStaff() && $user->id === $customerCommunication->logged_by_user_id);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, CustomerCommunication $customerCommunication): bool
    {
        // Only admins can delete any communication
        // Staff can delete only communications they logged themselves
        return $user->isAdmin() || ($user->isStaff() && $user->id === $customerCommunication->logged_by_user_id);
    }

    // The 'view' method for a single communication is less common, 'viewAny' covers seeing the list.
    // public function view(User $user, CustomerCommunication $customerCommunication): bool
    // {
    //     return $user->isAdmin() || $user->isStaff();
    // }
}