<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\CustomerCommunication;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use App\Models\Notification as AppNotification;
use App\Events\NewNotification;

// --- NEW IMPORTS for kreait/firebase-php ---
use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification as FirebaseNotification;
class CustomerCommunicationController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Display a listing of the customer communications for a specific user.
     */
    public function index(User $customer)
    {
        // ... (your existing index method code)
        $communications = $customer->communications()->with('loggedBy')->latest()->get();
        return response()->json(['communications' => $communications]);
    }

    /**
     * Store a newly created customer communication in storage.
     */
    public function store(Request $request, User $customer)
    {
        // ... (your existing store method code)
        $request->validate([
            'type' => ['required', 'string', Rule::in(CustomerCommunication::getTypes())],
            'subject' => ['nullable', 'string', 'max:255'],
            'content' => ['required', 'string'],
        ]);

        $communication = $customer->communications()->create([
            'type' => $request->type,
            'subject' => $request->subject,
            'content' => $request->content,
            'logged_by_user_id' => Auth::id(),
        ]);

//         $communication->load('loggedBy');
//         return response()->json([
//             'message' => 'Communication logged successfully.',
//             'communication' => $communication
//         ], 201);
//          Notification::create([
//     'order_id' => Auth::id(),
//     'title' => $request->subject,
//     'message' => $request->content,
//     'link' => route('communication.show', $communication), // Assuming you want to link to the order details
// ]);
// --- NOTIFICATION LOGIC ---
        $admin = User::where('role', 'admin')->first();
        if ($admin) {
            $customer = User::find($request->user_id);
            $title = "Communication Logged";
            $message = Auth::user()->name . " logged a '{$request->type}' with {$customer->name}.";

            // 1. Create DB Notification
            $dbNotification = AppNotification::create([ 'user_id' => $admin->id, 'title' => $title, 'message' => $message ]);

            // 2. Broadcast to Web UI (Pusher)
            event(new NewNotification($dbNotification));

            // 3. Send Push Notification (Firebase with kreait/firebase-php)
            if ($admin->fcm_token) {
                try {
                    $factory = (new Factory)->withServiceAccount(base_path('firebase_credentials.json'));
                    $messaging = $factory->createMessaging();

                    $notification = FirebaseNotification::create($title, $message);
                    
                    $message = CloudMessage::withTarget('token', $admin->fcm_token)
                        ->withNotification($notification)
                        ->withData(['click_action' => route('users.show', $customer->id)]); // Optional data payload
                    info('FCM Payload:', $message->jsonSerialize());
                    $messaging->send($message);

                } catch (\Exception $e) {
                    report($e);
                }
            }
        }
        // --- END NOTIFICATION LOGIC ---

        return response()->json(['message' => 'Communication logged successfully.'], 201);
    }
      
    /**
     * Display the specified communication. (Usually not needed for logs, index is enough)
     */
    public function show(CustomerCommunication $communication)
    {
        // ... (your existing show method code)
        return response()->json(['communication' => $communication->load('loggedBy')]);
    }

    /**
     * Update the specified communication in storage.
     *
     * IMPORTANT: Add User $customer as the second argument!
     */
    public function update(Request $request, User $customer, CustomerCommunication $communication) // <-- FIXED LINE
    {
        // No need to check $customer if you're implicitly assuming it's correct
        // However, you *could* add a check here if $customer->id !== $communication->user_id
        // if you wanted to ensure the communication truly belongs to the parent customer in the URL.

        $request->validate([
            'type' => ['required', 'string', Rule::in(CustomerCommunication::getTypes())],
            'subject' => ['nullable', 'string', 'max:255'],
            'content' => ['required', 'string'],
        ]);

        $communication->update($request->only(['type', 'subject', 'content']));

        $communication->load('loggedBy');

        return response()->json([
            'message' => 'Communication updated successfully.',
            'communication' => $communication
        ]);
    }

    /**
     * Remove the specified communication from storage.
     *
     * IMPORTANT: Add User $customer as the second argument!
     */
    public function destroy(User $customer, CustomerCommunication $communication) // <-- FIXED LINE
    {
        // Similar to update, you *could* check if $customer->id === $communication->user_id here.
        $communication->delete();

        return response()->json([
            'message' => 'Communication deleted successfully.'
        ]);
    }
}