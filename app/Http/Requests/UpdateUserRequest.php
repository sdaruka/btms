<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;

class UpdateUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Authorization is handled by the Policy in the Controller
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $user = $this->route('user'); // Get the user model being updated from the route
        $loggedInUser = Auth::user();

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'phone' => [
                'required',
                'string',
                'max:20',
                Rule::unique('users', 'phone')->ignore($user->id), // Ignore current user's phone
            ],
            'email' => [
                'nullable',
                'string',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user->id), // Ignore current user's email
            ],
            'address' => ['nullable', 'string', 'max:500'],
            'country' => ['nullable', 'string', 'max:255'],
            'profile_picture' => ['nullable', 'image', 'max:2048'], // 2MB max
            'remove_profile_picture' => ['nullable', 'boolean'], // New field to explicitly remove picture
            'is_active' => ['boolean'],
            'password' => [
                'nullable', // Password is optional on update
                'string',
                'min:6',
                'confirmed',
            ],
            'role' => [
                'required',
                'string',
                Rule::in(['admin', 'staff', 'customer', 'tailor', 'artisan']),
            ],
            'customer_group_id' => ['nullable', 'exists:customer_groups,id'], // <--- ADD THIS RULE

        ];

        // If the authenticated user is not an admin, they cannot change role or active status of others.
        // Also, a user cannot change their own role or active status (unless they are admin).
        if ($loggedInUser && $loggedInUser->role !== 'admin' || ($loggedInUser->id === $user->id && $loggedInUser->role !== 'admin')) {
            unset($rules['role']);
            unset($rules['is_active']);
            // If the user tries to submit these fields, they will be ignored by the controller.
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
            'phone.unique' => 'This phone number is already registered by another user.',
            'email.unique' => 'This email is already registered by another user.',
            'password.min' => 'Password must be at least 6 characters.',
            'password.confirmed' => 'The password confirmation does not match.',
        ];
    }
}