<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Faq;
use Illuminate\Http\Request;

class FaqController extends Controller
{
    /**
     * Get active FAQs
     */
    public function index()
    {
        $faqs = Faq::active()
            ->ordered()
            ->get();

        return response()->json($faqs);
    }

    /**
     * Get all FAQs (Admin)
     */
    public function adminIndex(Request $request)
    {
        $faqs = Faq::latest()
            ->paginate($request->get('per_page', 15));

        return response()->json($faqs);
    }

    /**
     * Create FAQ (Admin)
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'question' => 'required|string|max:500',
            'answer' => 'required|string',
            'category' => 'nullable|string|max:100',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ]);

        $faq = Faq::create($validated);

        return response()->json([
            'message' => 'FAQ created successfully.',
            'faq' => $faq,
        ], 201);
    }

    /**
     * Update FAQ (Admin)
     */
    public function update(Request $request, $id)
    {
        $faq = Faq::findOrFail($id);

        $validated = $request->validate([
            'question' => 'sometimes|string|max:500',
            'answer' => 'sometimes|string',
            'category' => 'nullable|string|max:100',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ]);

        $faq->update($validated);

        return response()->json([
            'message' => 'FAQ updated successfully.',
            'faq' => $faq,
        ]);
    }

    /**
     * Delete FAQ (Admin)
     */
    public function destroy($id)
    {
        $faq = Faq::findOrFail($id);
        $faq->delete();

        return response()->json([
            'message' => 'FAQ deleted successfully.',
        ]);
    }
}