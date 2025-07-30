<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\Response; // Make sure this is imported

class UserPolicy
{
    /**
     * Determine whether the user can view any models.
     * Only admins and staff can view the list of users.
     */
    public function viewAny(User $user): Response // Changed from bool to Response
    {
        return in_array($user->role, ['admin', 'staff'])
                ? Response::allow()
                : Response::deny('You do not have permission to view users.');
    }

    /**
     * Determine whether the user can view the model.
     * An admin/staff can view any user. A user can view their own profile.
     */
    public function view(User $loggedInUser, User $model): Response // Changed from bool to Response
    {
        if ($loggedInUser->role === 'admin' || $loggedInUser->role === 'staff') {
            return Response::allow(); // Admin/staff can view any user
        }

        return $loggedInUser->id === $model->id
                ? Response::allow()
                : Response::deny('You do not have permission to view this profile.');
    }

    /**
     * Determine whether the user can create models.
     * Only admins and staff can create new users.
     */
    public function create(User $user): Response // Changed from bool to Response
    {
        return in_array($user->role, ['admin', 'staff'])
                ? Response::allow()
                : Response::deny('You do not have permission to create users.');
    }

    /**
     * Determine whether the user can update the model.
     * An admin/staff can update any user. A user can update their own profile.
     */
    public function update(User $loggedInUser, User $model): Response // Changed from bool to Response
    {
        if ($loggedInUser->role === 'admin') {
            return Response::allow(); // Admin can update any user
        }

        // Staff can update any user except admin. They can also update their own profile.
        if ($loggedInUser->role === 'staff') {
            return ($model->role !== 'admin' || $loggedInUser->id === $model->id)
                    ? Response::allow()
                    : Response::deny('Staff cannot update admin profiles or others if not themselves.');
        }

        // A regular user (customer, tailor, artisan) can only update their own profile
        return $loggedInUser->id === $model->id
                ? Response::allow()
                : Response::deny('You do not have permission to update this user.');
    }

    /**
     * Determine whether the user can delete the model (soft delete).
     * Only admins can delete users. Prevent self-deletion.
     */
    public function delete(User $loggedInUser, User $model): Response // Changed from bool to Response
    {
        if ($loggedInUser->role === 'admin' && $loggedInUser->id !== $model->id) {
            return Response::allow(); // Admin can delete any user except themselves
        }

        return Response::deny('You do not have permission to delete this user.');
    }

    /**
     * Determine whether the user can restore the model (from soft delete).
     * Only admins can restore users.
     */
    public function restore(User $loggedInUser, User $model): Response // Changed from bool to Response
    {
        return $loggedInUser->role === 'admin'
                ? Response::allow()
                : Response::deny('You do not have permission to restore users.');
    }

    /**
     * Determine whether the user can permanently delete the model (force delete).
     * Only admins can force delete users.
     */
    public function forceDelete(User $loggedInUser, User $model): Response // Changed from bool to Response
    {
        return $loggedInUser->role === 'admin'
                ? Response::allow()
        
                : Response::deny('You do not have permission to permanently delete users.');
    }
    public function createCustomer(User $user): Response {
    return Response::allow(); // or apply lighter rules
    }
}