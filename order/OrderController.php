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
use App\Models\Notification;

class OrderController extends Controller
{
    // List all orders
    public function index(Request $request)
{
    $query = Order::with('user')->whereHas('user');

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
    ->paginate(10);

    }elseif(auth()->user()->role=="artisan"){
        $orders = $query
        ->whereIn('status', ['Pending', 'In Progress', 'Assigned'])
        ->where('assignedto', auth()->user()->id)
        ->latest()
        ->paginate(10);

    }else{
    $orders = $query->latest()->paginate(10);
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

} elseif (auth()->user()->role === 'admin' || auth()->user()->role === 'staff') {
    // Default group if not assigned
    $tailors = User::whereIn('role', ['tailor', 'artisan'])->get();
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
                return [
                    'id' => $d->id,
                    'design_title' => trim($d->design_title),
                    'image_url' => asset(trim($d->design_image, '/')),
                ];
            }),
            
            'measurements' => $p->measurements->map(fn($m) => [
                'id' => $m->id,
                'name' => $m->name,
            ]),
            'current_rate' => optional($p->rates->sortByDesc('effective_date')->first())->rate,
        ];
    });

    return view('orders.create', compact(
        'customers',
        'products',
        'productsJson',
        'measurements',
        'invoiceNumber'
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
            ]);

            // Measurements
            foreach ($item['measurements'] as $m) {
                $orderItem->measurements()->attach($m['id'], ['value' => $m['value']]);
            }

            // Existing designs
            if (!empty($item['design_ids'])) {
                $orderItem->designs()->attach($item['design_ids']);
                $orderItem->update(['design_id' => $item['design_ids'][0]]);
            }

            // Custom design uploads
            if ($request->hasFile("items.$i.custom_design_images")) {
                foreach ($request->file("items.$i.custom_design_images") as $img) {
                    $path = $img->store('products/designs', 'public');
                    $designTitle = $request->input("items.$i.custom_design_title") ?? 'Customer Upload';

                    $design = Design::create([
                    'product_id'   => $item['product_id'],
                    'design_title' => $designTitle,
                    'design_image' => $path,
                ]);

                    $orderItem->designs()->attach($design->id);

                    if (!$orderItem->design_id) {
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
        'items.design',
        'items.designs', // many-to-many pivot
        'items.measurements',
        'user',
    ]);

    // Attach image_url to each design
    $productsJson = Product::with(['designs', 'measurements'])->get()->map(function ($product) {
        $product->designs->each(function ($design) {
            $design->image_url = asset( trim($design->design_image, '/'));
        });
        return $product;
    });
    $customers = User::where('role', 'customer')->orderBy('name')->get();
    // $users = User::where('role', 'customer')->orderBy('name')->get();
    $measurements = Measurement::orderBy('name')->get();

    return view('orders.edit', [
        'order' => $order,
        'productsJson' => $productsJson,
        'customers' => $customers,
        'measurements' => $measurements,
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

        $order->items()->delete(); // or diff and update smartly

        foreach ($data['items'] as $item) {
            $orderItem = $order->items()->create([
                'product_id' => $item['product_id'],
                'quantity'   => $item['quantity'],
                'rate'       => $item['rate'],
            ]);

            $orderItem->designs()->attach($item['design_ids'] ?? []);
            $orderItem->update(['design_id' => $item['design_ids'][0] ?? null]);

            foreach ($item['measurements'] as $m) {
                $orderItem->measurements()->attach($m['id'], ['value' => $m['value']]);
            }
        }
       Notification::create([
    'order_id' => $order->id,
    'title' => 'Order Updated',
    'message' => "Order #{$order->id}  has been updated.",
     'link' => route('products.show', $order->id),

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
                $item->designs()->detach();
                $item->measurements()->detach();
                $item->delete();
            }

            // Optionally delete custom uploads or related resources here
             Notification::create([
    'order_id' => $order->id,
    'title' => 'Order Deleted',
    'message' => "Order #{$order->id} has been Deleted.",
     'link' => route('products.show', $order),
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
    'message' => "Order #{$order->id} status changed to {$order->status}.",
     'link' => route('products.show', $order),

]);
        $order->update(['status' => $request->status]);
       
        return response()->json([
            'success' => true,
            'message' => 'Status updated successfully',
            'status'  => $order->status,
        ]);
    }

    public function assignOrder(Request $request, Order $order)
{
    // dd($request->all());
    $data = $request->validate([
        'assignedto' => 'required|exists:users,id',
    ]);

    $order->update([
        'assignedto' => $data['assignedto'],
        'status' => 'Assigned',
    ]);

    return back()->with('status', 'Artisan assigned successfully!');
}
}
