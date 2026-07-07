<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Notification;
use Illuminate\Http\Request;

class WashermanController extends Controller
{
    /**
     * Get washerman dashboard statistics
     */
    public function dashboard()
    {
        $user = auth()->user();

        $stats = [
            'pending_orders' => $user->assignedOrders()->where('status', 'pending')->count(),
            'accepted_orders' => $user->assignedOrders()->where('status', 'accepted')->count(),
            'active_orders' => $user->assignedOrders()
                ->whereIn('status', ['picked_up', 'washing', 'cleaning', 'ironing', 'packing'])
                ->count(),
            'completed_orders' => $user->assignedOrders()->where('status', 'completed')->count(),
            'delivered_orders' => $user->assignedOrders()->where('status', 'delivered')->count(),
            'today_orders' => $user->assignedOrders()->today()->count(),
            
            'remaining_clothes' => $user->assignedOrders()
                ->whereIn('status', ['accepted', 'picked_up', 'washing', 'cleaning', 'ironing', 'packing', 'completed'])
                ->sum('remaining_quantity'),
            
            'completed_clothes' => $user->assignedOrders()
                ->whereIn('status', ['completed', 'delivered'])
                ->sum('completed_quantity'),
            
            'total_clothes' => $user->assignedOrders()
                ->whereIn('status', ['accepted', 'picked_up', 'washing', 'cleaning', 'ironing', 'packing', 'completed', 'delivered'])
                ->sum('total_quantity'),
        ];

        return response()->json($stats);
    }

    /**
     * Get assigned orders
     */
    public function assignedOrders(Request $request)
    {
        $query = auth()->user()->assignedOrders()->with(['customer', 'service', 'latestProgress']);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $orders = $query->latest()->paginate($request->get('per_page', 10));

        return response()->json($orders);
    }

    /**
     * Get order details
     */
    public function orderDetails($id)
    {
        $order = auth()->user()
            ->assignedOrders()
            ->with(['customer', 'service', 'progress.updatedBy', 'payment', 'review'])
            ->findOrFail($id);

        return response()->json($order);
    }

    /**
     * Get washerman performance stats
     */
    public function performance()
    {
        $user = auth()->user();
        $washerman = $user->washerman;

        $stats = [
            'total_orders' => $user->assignedOrders()->count(),
            'completed_orders' => $user->assignedOrders()->where('status', 'delivered')->count(),
            'average_rating' => $washerman->rating,
            'total_reviews' => $washerman->total_reviews,
            'average_delivery_time' => $washerman->average_delivery_time,
            'on_time_delivery_rate' => $this->calculateOnTimeDeliveryRate($user),
            'customer_satisfaction' => $this->calculateCustomerSatisfaction($user),
        ];

        $reviews = $user->reviews()
            ->with('customer')
            ->latest()
            ->take(10)
            ->get();

        return response()->json([
            'stats' => $stats,
            'reviews' => $reviews,
        ]);
    }

    /**
     * Get washerman notifications
     */
    public function notifications(Request $request)
    {
        $notifications = auth()->user()
            ->notifications()
            ->latest()
            ->paginate($request->get('per_page', 20));

        return response()->json($notifications);
    }

    /**
     * Calculate on-time delivery rate
     */
    private function calculateOnTimeDeliveryRate($user): float
    {
        $deliveredOrders = $user->assignedOrders()
            ->where('status', 'delivered')
            ->whereNotNull('delivered_at')
            ->get();

        if ($deliveredOrders->isEmpty()) {
            return 0;
        }

        $onTimeCount = $deliveredOrders->filter(function($order) {
            return $order->actual_delivery_date <= $order->expected_delivery_date;
        })->count();

        return ($onTimeCount / $deliveredOrders->count()) * 100;
    }

    /**
     * Calculate customer satisfaction
     */
    private function calculateCustomerSatisfaction($user): float
    {
        $avgRating = $user->reviews()->avg('rating');
        return $avgRating ? ($avgRating / 5) * 100 : 0;
    }
}