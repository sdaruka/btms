<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage; // Import the Storage facade
use Illuminate\Validation\Rule;
use App\Http\Requests\StoreUserRequest; // Import the new Form Request
use App\Http\Requests\UpdateUserRequest; // Import the new Form Request
use Illuminate\Support\Facades\File; // Import File facade
use Illuminate\Support\Facades\Log; // Import Log facade
use App\Models\CustomerGroup; // Import CustomerGroup model


class UserController extends Controller
{
    /**
     * Apply authorization middleware.
     * Note: You can apply these in web.php routes as well or use more granular policies.
     */
    public function __construct()
    {
        $this->middleware('check.auth'); // Ensure user is authenticated for all user actions
        // Policies will handle the specific authorizations within methods
    }

    /**
     * Display a listing of the users.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        // Authorize that only admins or staff can view the user list
        // Customers should not see this list.
        $this->authorize('viewAny', User::class);

        $search = $request->input('search');
        $role = $request->input('role'); // Filter by role, e.g., 'customer', 'staff', 'admin'
        $status = $request->input('status'); // Filter by active status

        $query = User::query();

        // Apply search filter
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('address', 'like', "%{$search}%");
            });
        }

        // Apply role filter
        if ($role && $role !== 'all') { // Assuming 'all' means no role filter
            $query->where('role', $role);
        }

        // Apply status filter
        if ($status === 'active') {
            $query->where('is_active', true);
        } elseif ($status === 'inactive') {
            $query->where('is_active', false);
        } elseif ($status === 'deleted') { // For soft deleted users
            $query->onlyTrashed();
        }

        // Ensure staff cannot see admins, and customers only see themselves (handled by policy for showProfile)
        $viewer = auth()->user();
        if ($viewer->role === 'staff') {
            $query->where('role', '!=', 'admin');
        }

        // For performance, ensure 'name', 'email', 'phone', 'address' columns are indexed in your 'users' table.
        $users = $query->latest()->paginate(10); // Paginate the results

        return view('users.index', compact('users', 'search', 'role', 'status'));
    }

    /**
     * Show the form for creating a new user.
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        $this->authorize('create', User::class); // Only admin/staff can create users
        $customerGroups = CustomerGroup::all(); 
        return view('users.create', compact('customerGroups'));
    }

    /**
     * Store a newly created user in storage.
     *
     * @param  \App\Http\Requests\StoreUserRequest  $request
     * @return \Illuminate\Http\RedirectResponse
     */
   public function store(StoreUserRequest $request)
    {
        $this->authorize('create', User::class); // Ensure authorization

        $validatedData = $request->validated();
        $profilePicturePath = null; // Initialize path to null

        // Handle profile picture upload FIRST
        if ($request->hasFile('profile_picture')) {
            $image = $request->file('profile_picture');
            $filename = time() . '.' . $image->getClientOriginalExtension();
            $destinationPath = public_path('images/profile_picture');

            // Ensure the directory exists
            if (!File::isDirectory($destinationPath)) {
                File::makeDirectory($destinationPath, 0777, true, true);
            }

            // Move the uploaded file
            $image->move($destinationPath, $filename);

            // Store the relative path
            $profilePicturePath = 'images/profile_picture/' . $filename;
            // Log::info("Profile picture prepared at: {$profilePicturePath}");
        }

        // Add the profile picture path to validated data
        // It will be null if no file was uploaded, or the path if it was.
        $validatedData['profile_picture'] = $profilePicturePath;

        // Hash password if provided, otherwise set a default
        if (isset($validatedData['password'])) {
            $validatedData['password'] = Hash::make($validatedData['password']);
        } else {
            // Default password, consider forcing a reset on first login or generating a complex one
            $validatedData['password'] = Hash::make(config('auth.default_user_password', 'password')); // Use config for default password
        }

        // Now, create the user with all validated data, including the profile_picture path
        $user = User::create($validatedData);

        // Log::info("User created successfully: User ID {$user->id}");

        return redirect()->route('users.index')->with('success', 'User created successfully!');
    }

    /**
     * Store a newly created customer in storage (API endpoint).
     * This is typically for frontend forms that create customers quickly.
     *
     * @param  \App\Http\Requests\StoreUserRequest  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function storeCustomer(StoreUserRequest $request)
{
    $validatedData = $request->validated();

    $validatedData['role'] = 'customer';
    $validatedData['is_active'] = true;
    $validatedData['password'] = Hash::make(config('auth.default_customer_password', '123456'));
    // $validatedData['customer_group_id'] = null;

    $user = User::create($validatedData);

    return response()->json([
        'success' => true,
        'message' => 'Customer created successfully!',
        'user' => [
            'id' => $user->id,
            'name' => $user->name,
            'phone' => $user->phone,
        ]
    ]);
}

    /**
     * Display the specified user's profile.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\View\View
     */
    public function showProfile(User $user)
    {
        // Authorize that the current user can view this specific profile
        $this->authorize('view', $user);

        return view('profile.show', compact('user'));
    }

    /**
     * Show the form for editing the specified user.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\View\View
     */
    public function edit(User $user)
    {
        // Authorize that the current user can update this specific user
        $this->authorize('update', $user);
        $customerGroups = CustomerGroup::all(); // Get all groups
        return view('profile.edit', compact('user', 'customerGroups'));
    }

    /**
     * Update the specified user in storage.
     *
     * @param  \App\Http\Requests\UpdateUserRequest  $request
     * @param  \App\Models\User  $user
     * @return \Illuminate\Http\RedirectResponse
     */
public function update(UpdateUserRequest $request, User $user)
    {
        // Authorize that the current user can update this specific user
        $this->authorize('update', $user);

        $validatedData = $request->validated();

        // --- Handle profile picture upload/removal ---
        // Initialize profile_picture path to current value, will be overwritten if updated/removed
        $profilePicturePath = $user->profile_picture;

        if ($request->hasFile('profile_picture')) {
            $image = $request->file('profile_picture');
            $filename = time() . '.' . $image->getClientOriginalExtension();
            $destinationPath = public_path('images/profile_picture');

            // Delete old profile picture if it exists
            if ($user->profile_picture && File::exists(public_path($user->profile_picture))) {
                File::delete(public_path($user->profile_picture));
                // Log::info("Old profile picture deleted for user {$user->id}: {$user->profile_picture}");
            }

            // Ensure the directory exists
            if (!File::isDirectory($destinationPath)) {
                File::makeDirectory($destinationPath, 0777, true, true);
            }

            // Move the new uploaded file
            $image->move($destinationPath, $filename);

            // Set the new relative path
            $profilePicturePath = 'images/profile_picture/' . $filename;
            // Log::info("New profile picture uploaded for user {$user->id}: {$profilePicturePath}");

        } elseif ($request->boolean('profile_picture_removed')) { // Using boolean() for safer checkbox check
            // If the user specifically indicated to remove the picture
            if ($user->profile_picture && File::exists(public_path($user->profile_picture))) {
                File::delete(public_path($user->profile_picture));
                // Log::info("Profile picture explicitly removed for user {$user->id}: {$user->profile_picture}");
            }
            $profilePicturePath = null; // Set path to null
        }
        // If neither new file uploaded nor removal requested, $profilePicturePath retains its original value.

        // Add the determined profile picture path to validated data for the update
        $validatedData['profile_picture'] = $profilePicturePath;
        // --- End profile picture handling ---


        // Only update password if provided and not empty
        if (isset($validatedData['password']) && !empty($validatedData['password'])) {
            $validatedData['password'] = Hash::make($validatedData['password']);
        } else {
            unset($validatedData['password']); // Do not update password if empty or not provided
        }

        // Prevent non-admins from changing roles or activity status of others
        if (auth()->user()->role !== 'admin' && auth()->id() !== $user->id) { // Added condition to allow self-update
            unset($validatedData['role']);
            unset($validatedData['is_active']);
        }

        // Update the user with all prepared data
        $user->update($validatedData);

        // Log::info("User updated successfully: User ID {$user->id}");

        return redirect()->route('users.index')->with('success', 'User updated successfully!');
    }

    /**
     * Remove the specified user from storage (soft delete).
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(User $user)
    {
        // Authorize that the current user can delete this specific user
        $this->authorize('delete', $user);

        // Prevent a user from deleting themselves
        if (auth()->id() === $user->id) {
            return back()->with('error', 'You cannot delete your own account!');
        }

        // Optionally delete profile picture from storage
        if ($user->profile_picture) {
            Storage::disk('public')->delete(str_replace('/storage/', '', $user->profile_picture));
        }

        $user->delete(); // This performs a soft delete due to SoftDeletes trait

        return redirect()->route('users.index')->with('success', 'User deleted successfully!');
    }

    /**
     * Restore a soft-deleted user.
     *
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function restore($id)
    {
        $user = User::onlyTrashed()->findOrFail($id);
        $this->authorize('restore', $user); // Authorize restore permission

        $user->restore();

        return redirect()->route('users.index')->with('success', 'User restored successfully!');
    }

    /**
     * Permanently delete a user (force delete).
     *
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function forceDelete($id)
    {
        $user = User::onlyTrashed()->findOrFail($id);
        $this->authorize('forceDelete', $user); // Authorize force delete permission

        // Permanently delete profile picture from storage
        if ($user->profile_picture) {
            Storage::disk('public')->delete(str_replace('/storage/', '', $user->profile_picture));
        }

        $user->forceDelete(); // This permanently deletes the user

        return redirect()->route('users.index')->with('success', 'User permanently deleted!');
    }
    /**
     * Get the FCM token for the authenticated user.
     *
     * @return \Illuminate\Http\JsonResponse
     */
     public function updateFcmToken(Request $request)
    {
        try {
            // 1. Validate the incoming request data
            $request->validate([
                'fcm_token' => 'required|string|max:255', // Expecting 'fcm_token'
            ]);

            // 2. Ensure a user is authenticated (middleware should handle this, but good defensive check)
            if ($request->user()) {
                // 3. Update the authenticated user's fcm_token
                $request->user()->update(['fcm_token' => $request->fcm_token]); // Use $request->fcm_token here
                return response()->json(['success' => true, 'message' => 'FCM token updated successfully.']);
            } else {
                // This should ideally not be reached if middleware is working, but provides a clear response
                return response()->json(['success' => false, 'message' => 'User not authenticated.'], 401);
            }

        } catch (ValidationException $e) {
            // Catch validation errors specifically
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            // Catch other general exceptions
            report($e); // Log the exception to Laravel logs
            return response()->json(['success' => false, 'message' => 'An internal server error occurred.'], 500);
        }
    }
}