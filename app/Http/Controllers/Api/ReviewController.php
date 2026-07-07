<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Review;
use App\Models\Testimonial;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    /**
     * Get approved reviews
     */
    public function index()
    {
        $reviews = Review::approved()
            ->with(['customer', 'washerman', 'order'])
            ->latest()
            ->paginate(10);

        return response()->json($reviews);
    }

    /**
     * Get testimonials
     */
    public function testimonials()
    {
        $testimonials = Testimonial::approved()
            ->featured()
            ->ordered()
            ->get();

        return response()->json($testimonials);
    }
}