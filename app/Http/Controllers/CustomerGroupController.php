<?php

namespace App\Http\Controllers;

use App\Models\CustomerGroup; // Make sure to import your CustomerGroup model
use Illuminate\Http\Request;
use Illuminate\Validation\Rule; // Don't forget this import for unique validation

class CustomerGroupController extends Controller
{
    /**
     * Display a listing of the customer groups.
     */
    public function index()
    {
        $customerGroups = CustomerGroup::withCount('users')->latest()->paginate(10);
        return view('customer_groups.index', compact('customerGroups'));
    }

    /**
     * Show the form for creating a new customer group.
     */
    public function create()
    {
        return view('customer_groups.create');
    }

    /**
     * Store a newly created customer group in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('customer_groups', 'name'), // Ensure unique name
            ],
        ]);

        CustomerGroup::create($request->all());

        return redirect()->route('customer_groups.index')
                         ->with('success', 'Customer group created successfully.');
    }

    /**
     * Display the specified customer group.
     */
    public function show(CustomerGroup $customerGroup)
    {
        // This is optional. Often, the 'index' view with a list is enough.
        // If you need a separate detail view, uncomment and create customer_groups.show.blade.php
        // return view('customer_groups.show', compact('customerGroup'));
        abort(404); // If you don't need a show method, return 404 or just remove it from routes
    }

    /**
     * Show the form for editing the specified customer group.
     */
    public function edit(CustomerGroup $customerGroup)
    {
        return view('customer_groups.edit', compact('customerGroup'));
    }

    /**
     * Update the specified customer group in storage.
     */
    public function update(Request $request, CustomerGroup $customerGroup)
    {
        $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('customer_groups', 'name')->ignore($customerGroup->id), // Ignore current group's ID for unique check
            ],
        ]);

        $customerGroup->update($request->all());

        return redirect()->route('customer_groups.index')
                         ->with('success', 'Customer group updated successfully.');
    }

    /**
     * Remove the specified customer group from storage.
     */
    public function destroy(CustomerGroup $customerGroup)
    {
        // Consider adding a check here to prevent deleting groups that are in use by customers
        // e.g., if ($customerGroup->users()->exists()) { return back()->with('error', 'Cannot delete group with associated customers.'); }
        $customerGroup->delete();

        return redirect()->route('customer_groups.index')
                         ->with('success', 'Customer group deleted successfully.');
    }
}