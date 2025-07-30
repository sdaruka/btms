<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;

class StoreUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; 
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        // Define common rules that apply to all user creation scenarios first
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            // Adjust 'phone' validation based on expected input (e.g., allow dashes if needed)
            // 'max:20' is good, 'unique:users,phone' is important.
            'phone' => ['required', 'string', 'max:20', Rule::unique('users', 'phone')],
            
            // These fields are always nullable when not explicitly provided
            'address' => ['nullable', 'string', 'max:500'],
            'country' => ['nullable', 'string', 'max:255'],
            'profile_picture' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,svg', 'max:2048'],
            'is_active' => ['nullable', 'boolean'], // Nullable if not provided, controller sets default
            
            // Defaulting email and password to nullable/prohibited unless specifically required by other routes
            'email' => ['nullable', 'string', 'email', 'max:255', Rule::unique('users', 'email')],
            'password' => ['nullable', 'string', 'min:6', 'confirmed'], // Confirmed is only needed if frontend sends password_confirmation
            'role' => ['nullable', 'string', Rule::in(['admin', 'staff', 'customer', 'tailor', 'artisan'])],
        ];

        // --- IMPORTANT: Adjust rules based on the specific route accessing this request ---
        // This makes sure that for the simple customer creation, password and role are not required.
        // It's generally better to have separate FormRequest classes for vastly different forms/APIs,
        // but if you must use one, checking the route is the way.

        // Scenario 1: Simple Customer Creation via the Modal (name and phone only)
        // You need to identify this specific route.
        // Option A: Check the route name (requires naming your route in web.php)
        // e.g., Route::post('/customer', [YourCustomerController::class, 'storeCustomer'])->name('customers.store.simple');
        if ($this->route()->getName() === 'customers.store.simple') { // <-- REPLACE WITH YOUR ACTUAL ROUTE NAME
            $rules['email'] = ['prohibited']; // Not expected from frontend for this modal
            $rules['password'] = ['prohibited']; // Not expected from frontend for this modal
            $rules['role'] = ['prohibited']; // Not expected from frontend, set in controller
            $rules['is_active'] = ['prohibited']; // Not expected from frontend, set in controller
            // $rules['customer_group_id'] = ['nullable', 'exists:customer_groups,id']; // Optional, but if provided, must exist
        } 
        // Option B: Check if only 'name' and 'phone' are present in the request (less robust if other fields might be empty but allowed)
        // else if ($this->has('name') && $this->has('phone') && !$this->has('email') && !$this->has('password') && !$this->has('role')) {
        //     $rules['email'] = ['prohibited'];
        //     $rules['password'] = ['prohibited'];
        //     $rules['role'] = ['prohibited'];
        //     $rules['is_active'] = ['prohibited'];
        // }
        
        // Scenario 2: Full User Creation (e.g., by an Admin where email, password, role are expected)
        // This 'else' block will catch other routes using this request, like an admin panel for users.
        else {
            $rules['email'] = ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')];
            $rules['password'] = ['required', 'string', 'min:6', 'confirmed'];
            $rules['role'] = ['required', 'string', Rule::in(['admin', 'staff', 'customer', 'tailor', 'artisan'])];
            // isActive might also be required or default to false in this scenario
            // $rules['is_active'] = ['boolean']; 
        }

        // If the authenticated user is not an admin, they cannot set the role or active status of the user being created.
        // This is a policy/authorization concern, but can be a redundant validation layer.
        $user = Auth::user();
        if ($user && !$user->isAdmin()) { // Assuming isAdmin() method on your User model
             // If a non-admin is making the request, ensure they aren't trying to set these fields
             unset($rules['role']);
             unset($rules['is_active']);
             // If the request for these fields exists, set them to prohibited
             if ($this->has('role')) {
                $rules['role'] = ['prohibited'];
             }
             if ($this->has('is_active')) {
                $rules['is_active'] = ['prohibited'];
             }
        }
        

        return $rules;
    }

    /**
     * Custom error messages (optional).
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'The customer name is required.',
            'phone.required' => 'The phone number is required.',
            'phone.max' => 'The phone number cannot be more than 20 characters.',
            'phone.unique' => 'This phone number is already registered.',
            // Add messages for prohibited rules if you want custom output
            'email.prohibited' => 'Email is not allowed for this type of customer creation.',
            'password.prohibited' => 'Password is not allowed for this type of customer creation.',
            'role.prohibited' => 'Role cannot be set via this method.',
            'is_active.prohibited' => 'Active status cannot be set via this method.',
        ];
    }
}