<?php

namespace App\Http\Controllers;
use App\Models\Product;
use App\Models\Measurement;
use App\Models\Rate;
use App\Models\ProductRate;
use App\Models\Design;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;



use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request) {
        // $products = Product::with('rates')->paginate(10);
        $query = Product::query()->with('rates')
            ->with('measurements')
            ->with('designs');
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }
        $products = $query->paginate(10);
        // Load related models
        // $products->load('rates', 'measurements', 'designs');
        return view('products.index', compact('products'));
        
    }
    
    public function create()
{
    $order = config('measurements');

    $measurements = Measurement::whereIn('name', $order)
        ->orderByRaw('FIELD(name, "' . implode('","', $order) . '")')
        ->get()->map(fn($m) => [
        'id' => $m->id,
        'name' => $m->name,
    ]);

    // $measurements = Measurement::orderBywhere('name')->get()->map(fn($m) => [
    //     'id' => $m->id,
    //     'name' => $m->name,
    // ]);

    return view('products.create', compact('measurements'));
}
    
 
public function store(Request $request)
{
    try {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'measurement_ids' => 'nullable|array',
            'measurement_ids.*' => 'exists:measurements,id',
            'rate' => 'required|numeric|min:0',
            'effective_date' => 'nullable|date',
            'images.*' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ],
    [
    'images.*.max' => 'Each image must be under 2MB.',
    'images.*.image' => 'Only valid image files are allowed.',
    'images.*.mimes' => 'Images must be JPG or PNG.',
]);

    } catch (\Illuminate\Validation\ValidationException $e) {
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'message' => ['Validation failed'],
                'errors' => $e->errors()
            ], 422);
        }

        throw $e;
    }

    DB::beginTransaction();

    try {
        // Create Product
        $product = Product::create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? '',
        ]);

        // Attach Measurements
        $product->measurements()->sync($validated['measurement_ids'] ?? []);

        // Store Rate
        $product->rates()->create([
            'rate' => $validated['rate'],
            'effective_date' => $validated['effective_date'] ?? now(),
        ]);

        // Upload Designs
        $designs = [];

        if ($request->hasFile('images')) {
    foreach ($request->file('images') as $image) {
        // Ensure destination folder exists
        $targetDir = public_path('images/designs');
        if (!file_exists($targetDir)) {
            mkdir($targetDir, 0755, true);
        }

        // Generate unique filename
        $filename = uniqid() . '.' . $image->getClientOriginalExtension();

        // Move to public/images/designs
        $image->move($targetDir, $filename);

        // Store relative path for reference
        $relativePath = 'images/designs/' . $filename;

        // Create design record
        $design = Design::create([
            'product_id' => $product->id,
            'design_title' => 'Default',
            'design_image' => $relativePath,
        ]);

        // Build response-friendly payload
        $designs[] = [
            'id' => $design->id,
            'design_title' => $design->design_title,
            'image_url' => asset($relativePath), // resolves to /btms/images/designs/...
        ];
    }
}

        DB::commit();

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'id' => $product->id,
                'name' => $product->name,
                'rate' => $validated['rate'],
                'designs' => $designs,
                'measurements' => $product->measurements->map(fn($m) => [
                    'id' => $m->id,
                    'name' => $m->name,
                ]),
            ]);
        }

        return redirect()->route('products.index')
            ->with('status', 'Product created successfully.');
    } catch (\Throwable $e) {
        DB::rollBack();

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'message' => 'Failed to create product',
                'error' => $e->getMessage(),
            ], 500);
        }

        return redirect()->route('products.index')
            ->with('error', 'Something went wrong: ' . $e->getMessage());
    }
}
    

    
    public function show(Product $product) {
        $product->load('rates', 'measurements', 'designs');
        return view('products.show', compact('product'));
    }
    public function edit(Product $product) {
        $product->load('rates', 'measurements', 'designs');
        $order = config('measurements');

    $measurements = \App\Models\Measurement::whereIn('name', $order)
        ->orderByRaw('FIELD(name, "' . implode('","', $order) . '")')
        ->get();

        return view('products.edit', compact('product', 'measurements'));
    }
    public function update(Request $request, Product $product)
{
    $validated = $request->validate([
        'name' => 'required|string|max:255',
        'description' => 'nullable|string',
        'measurement_ids' => 'nullable|array',
        'rate' => 'nullable|numeric|min:0',
        'effective_date' => 'nullable|date',
        'images.*' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
    ]);

    DB::beginTransaction();

    try {
        // Update product details
        $product->update([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
        ]);

        // Sync measurements
        $product->measurements()->sync($validated['measurement_ids'] ?? []);

        // Insert new rate (optional)
        if (!empty($validated['rate'])) {
            $product->rates()->create([
                'rate' => $validated['rate'],
                'effective_date' => $validated['effective_date'] ?? now(),
            ]);
        }

        // Upload new images (optional)
        if ($request->hasFile('images')) {
    foreach ($request->file('images') as $image) {
        // Ensure destination folder exists
        $targetDir = public_path('images/designs');
        if (!file_exists($targetDir)) {
            mkdir($targetDir, 0755, true);
        }

        // Generate unique filename
        $filename = uniqid() . '.' . $image->getClientOriginalExtension();

        // Move to public/images/designs
        $image->move($targetDir, $filename);

        // Store relative path for reference
        $relativePath = 'images/designs/' . $filename;

        // Create design record
        $design = Design::create([
            'product_id' => $product->id,
            'design_title' => 'Default',
            'design_image' => $relativePath,
        ]);

        // Build response-friendly payload
        $designs[] = [
            'id' => $design->id,
            'design_title' => $design->design_title,
            'image_url' => asset($relativePath), // resolves to /btms/images/designs/...
        ];
    }
}

        DB::commit();

        return redirect()
            ->route('products.index')
            ->with('status', 'Product updated successfully.');

    } catch (\Exception $e) {
        DB::rollBack();

        // Optional: log the error or send to monitoring
        report($e);

        return redirect()
            ->route('products.index')
            ->with('error', 'Product update failed: ' . $e->getMessage());
    }
}
public function destroy(Product $product)
{
    DB::beginTransaction();

    try {
        $User = Auth::user();
        if($User->role !== 'admin') {
            return redirect()->route('products.index')->with('error', 'Unauthorized action.');
        }else{

        
        // Delete associated designs
        foreach ($product->designs as $design) {
            Storage::disk('public')->delete($design->design_image);
            $design->delete();
        }
        foreach ($product->rates as $rate) {
            $rate->delete();
        }
        foreach ($product->measurements as $measurement) {
            $product->measurements()->detach($measurement->id);
        }
        // Delete the product
        $product->delete();

        DB::commit();

        return redirect()
            ->route('products.index')
            ->with('status', 'Product deleted successfully.');
    }} catch (\Exception $e) {
        DB::rollBack();

        return redirect()
            ->route('products.index')
            ->with('error', 'Product deletion failed: ' . $e->getMessage());
    }
    
}

}
