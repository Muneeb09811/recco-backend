<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Service;
use Illuminate\Http\Request;

class ServiceController extends Controller
{
    /**
     * Get all active services
     */
    public function index()
    {
        $services = Service::active()
            ->ordered()
            ->get();

        return response()->json($services);
    }

    /**
     * Get service details
     */
    public function show($id)
    {
        $service = Service::findOrFail($id);
        return response()->json($service);
    }

    /**
     * Create new service (Admin)
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:services',
            'description' => 'required|string',
            'icon' => 'nullable|string',
            'image' => 'nullable|image|max:2048',
            'base_price' => 'required|numeric|min:0',
            'price_unit' => 'required|in:per_item,per_kg,flat',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ]);

        if ($request->hasFile('image')) {
            $validated['image'] = $request->file('image')->store('services', 'public');
        }

        $service = Service::create($validated);

        return response()->json([
            'message' => 'Service created successfully.',
            'service' => $service,
        ], 201);
    }

    /**
     * Update service (Admin)
     */
    public function update(Request $request, $id)
    {
        $service = Service::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'slug' => 'sometimes|string|max:255|unique:services,slug,' . $id,
            'description' => 'sometimes|string',
            'icon' => 'nullable|string',
            'image' => 'nullable|image|max:2048',
            'base_price' => 'sometimes|numeric|min:0',
            'price_unit' => 'sometimes|in:per_item,per_kg,flat',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ]);

        if ($request->hasFile('image')) {
            $validated['image'] = $request->file('image')->store('services', 'public');
        }

        $service->update($validated);

        return response()->json([
            'message' => 'Service updated successfully.',
            'service' => $service,
        ]);
    }

    /**
     * Delete service (Admin)
     */
    public function destroy($id)
    {
        $service = Service::findOrFail($id);
        $service->delete();

        return response()->json([
            'message' => 'Service deleted successfully.',
        ]);
    }
}