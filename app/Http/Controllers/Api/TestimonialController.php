<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Testimonial;
use Illuminate\Http\Request;

class TestimonialController extends Controller
{
    /**
     * Get all testimonials (Admin)
     */
    public function index(Request $request)
    {
        $testimonials = Testimonial::latest()
            ->paginate($request->get('per_page', 15));

        return response()->json($testimonials);
    }

    /**
     * Create testimonial (Admin)
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_name' => 'required|string|max:255',
            'customer_avatar' => 'nullable|image|max:2048',
            'designation' => 'nullable|string|max:255',
            'content' => 'required|string',
            'rating' => 'integer|min:1|max:5',
            'is_featured' => 'boolean',
            'is_approved' => 'boolean',
        ]);

        if ($request->hasFile('customer_avatar')) {
            $validated['customer_avatar'] = $request->file('customer_avatar')->store('testimonials', 'public');
        }

        $testimonial = Testimonial::create($validated);

        return response()->json([
            'message' => 'Testimonial created successfully.',
            'testimonial' => $testimonial,
        ], 201);
    }

    /**
     * Update testimonial (Admin)
     */
    public function update(Request $request, $id)
    {
        $testimonial = Testimonial::findOrFail($id);

        $validated = $request->validate([
            'customer_name' => 'sometimes|string|max:255',
            'customer_avatar' => 'nullable|image|max:2048',
            'designation' => 'nullable|string|max:255',
            'content' => 'sometimes|string',
            'rating' => 'integer|min:1|max:5',
            'is_featured' => 'boolean',
            'is_approved' => 'boolean',
        ]);

        if ($request->hasFile('customer_avatar')) {
            $validated['customer_avatar'] = $request->file('customer_avatar')->store('testimonials', 'public');
        }

        $testimonial->update($validated);

        return response()->json([
            'message' => 'Testimonial updated successfully.',
            'testimonial' => $testimonial,
        ]);
    }

    /**
     * Delete testimonial (Admin)
     */
    public function destroy($id)
    {
        $testimonial = Testimonial::findOrFail($id);
        $testimonial->delete();

        return response()->json([
            'message' => 'Testimonial deleted successfully.',
        ]);
    }
}