<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Models\Design;
use App\Models\Measurement;
use App\Models\OrderItem;
use App\Models\Rate;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use App\Models\Notification; // Ensure this is imported
use App\Models\CustomerGroup; // Import CustomerGroup model
use Illuminate\Support\Facades\File; // Import File facade

class OrderController extends Controller
{
    // List all orders
    public function index(Request $request)
{
    $query = Order::with('user' , 'artisan')->whereHas('user');

    if ($request->filled('search')) {
        $search = $request->search;

        $query->where(function ($q) use ($search) {
            $q->where('order_number', 'like', "%{$search}%")
            ->orWhere('status', 'like', "%{$search}%")
              ->orWhereHas('user', function ($q2) use ($search) {
                  $q2->where('name', 'like', "%{$search}%");
              });
        });
    }

    if ($request->filled('search_date')) {
        $query->whereDate('delivery_date', $request->search_date);
    }
     if ($request->boolean('hide_delivered', true)) { // Default to true if parameter is not present
            $query->where('status', '!=', 'Delivered');
        }
    if(auth()->user()->role=="tailor"){
        $orders = $query
    ->whereIn('status', ['Pending', 'In Progress'])
    ->latest()
    ->paginate(9);

    }elseif(auth()->user()->role=="artisan"){
        $orders = $query
        ->whereIn('status', ['Pending', 'In Progress', 'Assigned'])
        ->where('assignedto', auth()->user()->id)
        ->latest()
        ->paginate(9);

    }else{
    $orders = $query->latest()->paginate(9);
    }

    $order = $query->where('assignedto', auth()->id())->first();

    if (auth()->user()->role === 'tailor') {
    $tailors = User::where('role', 'artisan')->get();

} elseif (
    auth()->user()->role === 'artisan' &&
    auth()->user()->is_active &&
    $order // only if there's a match
) {
    // Load other artisans (exclude current user)
    $tailors = User::where('role', 'artisan')
                   ->where('id', '!=', auth()->id())
                   ->get();

} elseif (auth()->user()->role === 'admin' || auth()->user()->role === 'staff' ) {
    // Default group if not assigned
    $tailors = User::whereIn('role', ['tailor', 'artisan'])->get();
}

 if ($request->ajax()) {
        return response()->json([
            'data' => $orders->items(),
            'next_page_url' => $orders->nextPageUrl(),
            'has_more_pages' => $orders->hasMorePages(),
            'current_page' => $orders->currentPage(), // Added for Alpine state
            'last_page' => $orders->lastPage() // Added for Alpine state
        ]);
    }
    return view('orders.index', compact('orders', 'tailors'));
}


    // Show form to create a new order
     public function create()
    {
        $customers = User::where('role','customer')->orderBy('name')->get();
        $products  = Product::with('designs','measurements','rates')->get();

        $lastInvoice = Order::max('order_number');
        $invoiceNumber = $lastInvoice ? $lastInvoice + 1 : 1;

        $productOrder = config('measurements');
        $measurements = Measurement::whereIn('name', $productOrder)
            ->orderByRaw('FIELD(name, "' . implode('","', $productOrder) . '")')
            ->get();

        $productsJson = $products->map(function ($p) {
            return [
                'id' => $p->id,
                'name' => $p->name,
                'designs' => $p->designs->map(function ($d) {
                    $imagePath = Str::after(ltrim($d->design_image, '/'), 'public/');
                    return [
                        'id' => $d->id,
                        'design_title' => trim($d->design_title),
                        'image_url' => asset($imagePath),
                    ];
                }),
                'measurements' => $p->measurements->map(fn($m) => [
                    'id' => $m->id,
                    'name' => $m->name,
                ]),
                'current_rate' => optional($p->rates->sortByDesc('effective_date')->first())->rate,
            ];
        });

        // Fetch customer groups
        $customerGroups = CustomerGroup::all(); // <-- NEW LINE

        return view('orders.create', compact(
            'customers',
            'products',
            'productsJson',
            'measurements',
            'invoiceNumber',
            'customerGroups' // <-- NEW LINE: Pass customer groups to the view
        ));
    
}



    // Store new order with transaction
    public function loadPreviousMeasurements(User $user)
{
    // dd($user);
    $latestItems = OrderItem::with('measurements')
    ->whereHas('order', fn($q) => $q->where('user_id', $user->id))
    ->orderBy('created_at', 'desc')
    ->get()
    ->groupBy('product_id');

return response()->json($latestItems);
}

   public function store(Request $request)
{
    // dd($request->all());
    $data = $request->validate([
        'user_id' => 'required|exists:users,id',
        'delivery_date' => 'required|date',
        'order_date' => 'required|date',
        'discount' => 'nullable|numeric|min:0',
        'received' => 'nullable|numeric|min:0',
        'design_charge' => 'nullable|numeric|min:0',
        'total_amount' => 'nullable|numeric|min:0',
        'remarks' => 'nullable|string|max:255',
        'items' => 'required|array|min:1',
        'items.*.product_id' => 'required|exists:products,id',
        'items.*.rate' => 'required|numeric|min:0',
        'items.*.quantity' => 'required|integer|min:1',
        'items.*.design_ids' => 'nullable|array',
        'items.*.design_ids.*' => 'exists:designs,id',
        'items.*.custom_design_images' => 'nullable|array',
        'items.*.custom_design_images.*' => 'image|mimes:jpg,jpeg,png|max:2048',
        'items.*.custom_design_title' => 'nullable|string|max:255',
        'items.*.narration' => 'nullable|string|max:1000', // Added narration validation
        'items.*.measurements' => 'required|array',
        'items.*.measurements.*.id' => 'required|exists:measurements,id',
        'items.*.measurements.*.value' => 'required|string|max:50',
        
    ]);

    try {
        DB::beginTransaction();

        // Generate next order number safely
        $lastNumber = DB::table('orders')->lockForUpdate()->max('order_number');
        $orderNumber = $lastNumber ? $lastNumber + 1 : 1001;

        // Calculate subtotal
        $subtotal = collect($data['items'])->sum(function ($item) {
            return intval($item['quantity']) * floatval($item['rate']);
        });

        $order = Order::create([
            'user_id'        => $data['user_id'],
            'order_date'     => $data['order_date'],
            'delivery_date'  => $data['delivery_date'],
            'order_number'   => $orderNumber,
            'discount'       => $data['discount'] ?? 0,
            'received'       => $data['received'] ?? 0,
            'design_charge'  => $data['design_charge'] ?? 0,
            'remarks'        => $data['remarks'] ?? '',
            'total_amount'   => $data['total_amount']
                ?? max(0, $subtotal - ($data['discount'] ?? 0) + ($data['design_charge'] ?? 0)),
        ]);

        foreach ($data['items'] as $i => $item) {
            $orderItem = $order->items()->create([
                'product_id' => $item['product_id'],
                'rate'       => $item['rate'],
                'quantity'   => $item['quantity'],
                'custom_design_title' => $item['custom_design_title'] ?? null,
                'narration' => $item['narration'] ?? null, // Save narration
            ]);

            // Measurements
            foreach ($item['measurements'] as $m) {
                $orderItem->measurements()->attach($m['id'], ['value' => $m['value']]);
            }

            // Existing designs
            if (!empty($item['design_ids'])) {
                $orderItem->designs()->attach($item['design_ids']);
                // Note: design_id on order_items table is single, design_ids is many-to-many.
                // It might be better to remove design_id from order_items if you are using a pivot table for designs.
                // For now, keeping your existing logic.
                $orderItem->update(['design_id' => $item['design_ids'][0]]);
            }

            // Custom design uploads
            if ($request->hasFile("items.$i.custom_design_images")) {
                $destinationPath = public_path('images/designs');

                // Create the directory if it doesn't exist
                if (!File::isDirectory($destinationPath)) {
                    File::makeDirectory($destinationPath, 0777, true, true);
                }

                foreach ($request->file("items.$i.custom_design_images") as $img) {
                    $fileName = uniqid() . '.' . $img->getClientOriginalExtension();
                    $img->move($destinationPath, $fileName); // Move to public/images/designs
                    $publicPath = 'images/designs/' . $fileName; // Store relative path

                    $designTitle = $request->input("items.$i.custom_design_title") ?? 'Customer Upload';

                    $design = Design::create([
                        'product_id'   => $item['product_id'],
                        'design_title' => $designTitle,
                        'design_image' => $publicPath, // Store the public relative path
                    ]);

                    $orderItem->designs()->attach($design->id);

                    if (!$orderItem->design_id) { // Only set if not already set by existing designs
                        $orderItem->update(['design_id' => $design->id]);
                    }
                }
            }
        }
        Notification::create([
            'order_id' => $order->id,
            'title' => 'New Order Received',
            'message' => "Order #{$order->id} has been placed.",
            'link' => route('orders.show', $order), // Assuming you want to link to the order details
        ]);
        DB::commit();

        return redirect()
            ->route('orders.print', $order)
            ->with('status', 'Order placed successfully!');
    } catch (\Throwable $e) {
        DB::rollBack();

        return back()
            ->withInput()
            ->with('error', 'Order failed: ' . $e->getMessage());
    }
}


    // Show a single order
    public function show(Order $order)
    {
        $order->load('user','items.product','items.design','items.measurements');
        return view('orders.show', compact('order'));
    }
    public function print(Order $order)
    {
        $order->load('user','items.product','items.design','items.measurements');
        return view('orders.print', compact('order'));
    }
    public function edit(Order $order)
{
    $order->load([
        'items.product',
        'items.design', // The main design (if any)
        'items.designs', // All associated designs (pivot)
        'items.measurements',
        'user',
    ]);

    // Prepare products JSON for Alpine.js
    $productsJson = Product::with(['designs', 'measurements'])->get()->map(function ($product) {
        $product->designs->each(function ($design) {
            // Assuming design_image stores 'images/designs/filename.jpg'
            $design->image_url = asset($design->design_image);
        });
        return $product;
    });

    // Transform order items for Alpine.js `items` array
    $orderItemsForAlpine = $order->items->map(function($item) {
        // Collect custom design image URLs if available from the Designs relation
        $customDesignUrls = $item->designs
            ->filter(fn($design) => !empty($design->design_image) && Str::startsWith($design->design_image, 'images/designs')) // Check for the new path
            ->map(fn($design) => asset($design->design_image))
            ->values()
            ->toArray();

        return [
            'id' => $item->product_id, // This is the product_id for order item in Alpine
            'order_item_id' => $item->id, // The actual order_item ID if needed for backend updates
            'name' => $item->product->name,
            'rate' => $item->rate,
            'quantity' => $item->quantity,
            'narration' => $item->narration, // Pass narration
            'selectedDesignIds' => $item->designs->pluck('id')->toArray(),
            'designs' => $item->product->designs->map(function ($d) {
                return [
                    'id' => $d->id,
                    'design_title' => trim($d->design_title),
                    'image_url' => asset($d->design_image), // Use asset for existing designs
                ];
            }),
            'measurements' => $item->product->measurements->map(function($m) use ($item) {
                $pivot = $item->measurements->where('id', $m->id)->first();
                return [
                    'id' => $m->id,
                    'name' => $m->name,
                    'pivot' => ['value' => $pivot ? $pivot->pivot->value : ''],
                ];
            }),
            'custom_design_title' => $item->custom_design_title,
            'custom_design_images_names' => [], // This will be populated by the file input on new uploads
            'existing_custom_design_urls' => $customDesignUrls, // Existing custom designs for display
        ];
    });


    $customers = User::where('role', 'customer')->orderBy('name')->get();
    $measurements = Measurement::orderBy('name')->get(); // Not directly used by Alpine now, but good to have.

    return view('orders.edit', [
        'order' => $order,
        'productsJson' => $productsJson,
        'customers' => $customers,
        'measurements' => $measurements,
        'orderItemsForAlpine' => $orderItemsForAlpine, // Pass this to Alpine.js init
    ]);
}
public function update(Request $request, Order $order)
{
    // dd($request->all());
    $data = $request->validate([
        'user_id' => 'required|exists:users,id',
        'order_number' => 'required|string|max:255',
        'order_date' => 'required|date',
        'delivery_date' => 'required|date',
        'design_charge' => 'nullable|numeric|min:0',
        'discount' => 'nullable|numeric|min:0',
        'received' => 'nullable|numeric|min:0',
        'total_amount' => 'nullable|numeric|min:0',
        'remarks'=> 'nullable|string',
        'items' => 'required|array|min:1',
        'items.*.product_id' => 'required|exists:products,id',
        'items.*.rate' => 'required|numeric|min:0',
        'items.*.quantity' => 'required|integer|min:1',
        'items.*.design_ids' => 'nullable|array',
        'items.*.design_ids.*' => 'exists:designs,id',
        'items.*.custom_design_images' => 'nullable|array',
        'items.*.custom_design_images.*' => 'image|mimes:jpg,jpeg,png|max:2048',
        'items.*.custom_design_title' => 'nullable|string|max:255',
        'items.*.narration' => 'nullable|string|max:1000', // Added narration validation
        'items.*.measurements' => 'required|array',
        'items.*.measurements.*.id' => 'required|exists:measurements,id',
        'items.*.measurements.*.value' => 'required|string|max:50',
    ]);


    DB::beginTransaction();

    try {
        $order->update([
            'order_number' => $data['order_number'],
            'order_date'   => $data['order_date'],
            'delivery_date'=> $data['delivery_date'],
            'design_charge' => $data['design_charge'],
            'discount'     => $data['discount'],
            'received'     => $data['received'],
            'total_amount' => max(0, $data['total_amount']),
            'remarks'     => $data['remarks'],
        ]);

        // Delete existing order items and re-create them for simplicity.
        // A more robust solution for large forms might involve diffing and updating existing items.
        // First, detach designs and measurements from old order items before deleting to clean pivot tables
        foreach ($order->items as $item) {
            $item->designs()->detach();
            $item->measurements()->detach();
        }
        $order->items()->delete(); // Now delete the order items themselves

        foreach ($data['items'] as $i => $item) {
            $orderItem = $order->items()->create([
                'product_id' => $item['product_id'],
                'quantity'   => $item['quantity'],
                'rate'       => $item['rate'],
                'custom_design_title' => $item['custom_design_title'] ?? null,
                'narration' => $item['narration'] ?? null, // Save narration
            ]);

            // Sync designs (existing and newly selected)
            if (!empty($item['design_ids'])) {
                $orderItem->designs()->sync($item['design_ids']);
                $orderItem->update(['design_id' => $item['design_ids'][0] ?? null]);
            } else {
                 $orderItem->designs()->detach();
                 $orderItem->update(['design_id' => null]);
            }


            foreach ($item['measurements'] as $m) {
                $orderItem->measurements()->attach($m['id'], ['value' => $m['value']]);
            }

            // Handle new custom design uploads for existing items
            if ($request->hasFile("items.$i.custom_design_images")) {
                $destinationPath = public_path('images/designs');

                // Create the directory if it doesn't exist
                if (!File::isDirectory($destinationPath)) {
                    File::makeDirectory($destinationPath, 0777, true, true);
                }

                foreach ($request->file("items.$i.custom_design_images") as $img) {
                    $fileName = uniqid() . '.' . $img->getClientOriginalExtension();
                    $img->move($destinationPath, $fileName); // Move to public/images/designs
                    $publicPath = 'images/designs/' . $fileName; // Store relative path

                    $designTitle = $request->input("items.$i.custom_design_title") ?? 'Customer Upload';

                    $design = Design::create([
                        'product_id'   => $item['product_id'],
                        'design_title' => $designTitle,
                        'design_image' => $publicPath, // Store the public relative path
                    ]);

                    $orderItem->designs()->attach($design->id);
                    if (!$orderItem->design_id) { // Only set if not already set by existing designs
                        $orderItem->update(['design_id' => $design->id]);
                    }
                }
            }
        }
       Notification::create([
            'order_id' => $order->id,
            'title' => 'Order Updated',
            'message' => "Order #{$order->id}  has been updated.",
            'link' => route('orders.show', $order->id), // Corrected link
        ]);
        DB::commit();

        return redirect()->route('orders.show', $order)->with('status', 'Order updated!');
    } catch (\Exception $e) {
        DB::rollBack();
        return back()->withInput()->with('error', $e->getMessage());
    }
}
public function destroy(Order $order)
{
    try {
        DB::transaction(function () use ($order) {
            // Delete related items and pivots manually if not cascading
            foreach ($order->items as $item) {
                // Optionally delete custom uploaded image files here
                // Example: if ($item->design && Str::startsWith($item->design->design_image, 'images/designs')) {
                //      File::delete(public_path($item->design->design_image));
                // }
                $item->designs()->detach();
                $item->measurements()->detach();
                $item->delete();
            }

            // Optionally delete custom uploads or related resources here
             Notification::create([
                'order_id' => $order->id,
                'title' => 'Order Deleted',
                'message' => "Order #{$order->id} has been Deleted.",
                'link' => route('orders.index'), // Link to index after deletion
            ]);
            $order->delete();

        });

        return redirect()->route('orders.index')->with('status', 'Order deleted successfully!');
    } catch (\Exception $e) {
        return back()->with('error', 'Failed to delete order: ' . $e->getMessage());
    }
}
    public function updateStatus(Request $request, Order $order)
{
    $request->validate([
        'status' => 'required|in:Assigned,Pending,Completed,Cancelled,Alteration,Delivered,In Progress',
    ]);

    Notification::create([
        'order_id' => $order->id,
        'title' => 'Status Changed',
        'message' => "Order #{$order->id} status changed to {$request->status}.",
        'link' => route('orders.show', $order),
    ]);

    $order->update(['status' => $request->status]);

    // Don't return a response here — return true if needed
    return true;
}

     public function assignOrder(Request $request, Order $order)
{
    $data = $request->validate([
        'tailor_id' => 'required|exists:users,id',
    ]);

    // Inject a manual status field for the updateStatus call
    $request->merge(['status' => 'Assigned']);

    // Now call the method internally
    $this->updateStatus($request, $order);

    $order->update([
        'assignedto' => $data['tailor_id'],
    ]);

    return response()->json($order->load('user', 'artisan'));
}

}