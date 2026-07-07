<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Notification;
use App\Models\Review;
use App\Models\Address;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class CustomerController extends Controller
{
    /**
     * Get customer dashboard statistics
     */
    public function dashboard(): JsonResponse
    {
        $user = Auth::user();

        $stats = [
            'total_orders' => Order::where('customer_id', $user->id)->count(),
            'pending_orders' => Order::where('customer_id', $user->id)->where('status', 'pending')->count(),
            'active_orders' => Order::where('customer_id', $user->id)
                ->whereIn('status', ['accepted', 'picked_up', 'washing', 'cleaning', 'ironing', 'packing', 'completed'])
                ->count(),
            'completed_orders' => Order::where('customer_id', $user->id)->where('status', 'completed')->count(),
            'delivered_orders' => Order::where('customer_id', $user->id)->where('status', 'delivered')->count(),
        ];

        return response()->json($stats);
    }

    /**
     * Get active orders - FIXED WITH ERROR HANDLING
     */
    public function activeOrders(Request $request): JsonResponse
    {
        $user = Auth::user();

        try {
            Log::info('Fetching active orders for user', ['user_id' => $user->id]);

            // Simple query without complex relationships
            $orders = Order::where('customer_id', $user->id)
                ->whereIn('status', [
                    'pending',
                    'accepted',
                    'picked_up',
                    'washing',
                    'cleaning',
                    'ironing',
                    'packing',
                    'completed'
                ])
                ->with(['service', 'washerman'])
                ->orderBy('created_at', 'desc')
                ->paginate($request->get('per_page', 10));

            Log::info('Active orders fetched successfully', [
                'count' => $orders->total(),
                'user_id' => $user->id
            ]);

            return response()->json($orders);

        } catch (\Exception $e) {
            Log::error('Active orders error: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            
            return response()->json([
                'message' => 'Error fetching active orders: ' . $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ], 500);
        }
    }

    /**
     * Get order history (delivered/cancelled)
     */
    public function orderHistory(Request $request): JsonResponse
    {
        $user = Auth::user();

        try {
            $orders = Order::where('customer_id', $user->id)
                ->whereIn('status', ['delivered', 'cancelled', 'rejected'])
                ->with(['service', 'washerman'])
                ->orderBy('created_at', 'desc')
                ->paginate($request->get('per_page', 10));

            return response()->json($orders);

        } catch (\Exception $e) {
            Log::error('Order history error: ' . $e->getMessage());
            return response()->json([
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get order details with live tracking
     */
    public function orderDetails(int $id): JsonResponse
    {
        $user = Auth::user();

        try {
            $order = Order::where('customer_id', $user->id)
                ->with([
                    'service',
                    'washerman',
                    'progress.updatedBy',
                    'payment',
                    'review'
                ])
                ->findOrFail($id);

            return response()->json($order);

        } catch (\Exception $e) {
            Log::error('Order details error: ' . $e->getMessage());
            return response()->json([
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get customer invoices (paid orders)
     */
    public function invoices(Request $request): JsonResponse
    {
        $user = Auth::user();

        try {
            $orders = Order::where('customer_id', $user->id)
                ->where('payment_status', 'paid')
                ->with(['payment'])
                ->orderBy('created_at', 'desc')
                ->paginate($request->get('per_page', 10));

            return response()->json($orders);

        } catch (\Exception $e) {
            Log::error('Invoices error: ' . $e->getMessage());
            return response()->json([
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get customer notifications
     */
    public function notifications(Request $request): JsonResponse
    {
        $user = Auth::user();

        try {
            $notifications = Notification::where('user_id', $user->id)
                ->orderBy('created_at', 'desc')
                ->paginate($request->get('per_page', 20));

            return response()->json($notifications);

        } catch (\Exception $e) {
            Log::error('Notifications error: ' . $e->getMessage());
            return response()->json([
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mark notification as read
     */
    public function markNotificationRead(int $id): JsonResponse
    {
        $user = Auth::user();

        try {
            $notification = Notification::where('user_id', $user->id)
                ->findOrFail($id);

            $notification->markAsRead();

            return response()->json([
                'message' => 'Notification marked as read.',
            ]);

        } catch (\Exception $e) {
            Log::error('Mark notification error: ' . $e->getMessage());
            return response()->json([
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mark all notifications as read
     */
    public function markAllNotificationsRead(): JsonResponse
    {
        $user = Auth::user();

        try {
            Notification::where('user_id', $user->id)
                ->where('is_read', false)
                ->update([
                    'is_read' => true,
                    'read_at' => now()
                ]);

            return response()->json([
                'message' => 'All notifications marked as read.',
            ]);

        } catch (\Exception $e) {
            Log::error('Mark all notifications error: ' . $e->getMessage());
            return response()->json([
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Submit review for order
     */
    public function submitReview(Request $request, int $orderId): JsonResponse
    {
        $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:1000',
        ]);

        $user = Auth::user();

        try {
            $order = Order::where('customer_id', $user->id)
                ->where('status', 'delivered')
                ->findOrFail($orderId);

            if ($order->review) {
                return response()->json([
                    'message' => 'You have already reviewed this order.',
                ], 400);
            }

            $review = Review::create([
                'order_id' => $order->id,
                'customer_id' => $user->id,
                'washerman_id' => $order->washerman_id,
                'rating' => $request->rating,
                'comment' => $request->comment,
                'is_approved' => true,
            ]);

            if ($order->washerman && $order->washerman->washerman) {
                $order->washerman->washerman->updateRating();
            }

            return response()->json([
                'message' => 'Review submitted successfully.',
                'review' => $review,
            ], 201);

        } catch (\Exception $e) {
            Log::error('Submit review error: ' . $e->getMessage());
            return response()->json([
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get customer reviews
     */
    public function myReviews(): JsonResponse
    {
        $user = Auth::user();

        try {
            $reviews = Review::where('customer_id', $user->id)
                ->with(['order', 'washerman'])
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json($reviews);

        } catch (\Exception $e) {
            Log::error('My reviews error: ' . $e->getMessage());
            return response()->json([
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get customer addresses
     */
    public function addresses(): JsonResponse
    {
        $user = Auth::user();

        try {
            $addresses = Address::where('user_id', $user->id)
                ->orderBy('is_default', 'desc')
                ->get();

            return response()->json($addresses);

        } catch (\Exception $e) {
            Log::error('Addresses error: ' . $e->getMessage());
            return response()->json([
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store new address
     */
    public function storeAddress(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'label' => 'required|string|max:50',
            'address' => 'required|string',
            'city' => 'required|string',
            'state' => 'nullable|string',
            'zip_code' => 'nullable|string',
            'phone' => 'nullable|string',
            'is_default' => 'boolean',
        ]);

        $user = Auth::user();

        try {
            if ($validated['is_default'] ?? false) {
                Address::where('user_id', $user->id)->update(['is_default' => false]);
            }

            $address = Address::create(array_merge($validated, [
                'user_id' => $user->id,
            ]));

            return response()->json([
                'message' => 'Address added successfully.',
                'address' => $address,
            ], 201);

        } catch (\Exception $e) {
            Log::error('Store address error: ' . $e->getMessage());
            return response()->json([
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update address
     */
    public function updateAddress(Request $request, int $id): JsonResponse
    {
        $user = Auth::user();

        try {
            $address = Address::where('user_id', $user->id)->findOrFail($id);

            $validated = $request->validate([
                'label' => 'sometimes|string|max:50',
                'address' => 'sometimes|string',
                'city' => 'sometimes|string',
                'state' => 'nullable|string',
                'zip_code' => 'nullable|string',
                'phone' => 'nullable|string',
                'is_default' => 'boolean',
            ]);

            if ($validated['is_default'] ?? false) {
                Address::where('user_id', $user->id)->update(['is_default' => false]);
            }

            $address->update($validated);

            return response()->json([
                'message' => 'Address updated successfully.',
                'address' => $address,
            ]);

        } catch (\Exception $e) {
            Log::error('Update address error: ' . $e->getMessage());
            return response()->json([
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete address
     */
    public function destroyAddress(int $id): JsonResponse
    {
        $user = Auth::user();

        try {
            $address = Address::where('user_id', $user->id)->findOrFail($id);
            $address->delete();

            return response()->json([
                'message' => 'Address deleted successfully.',
            ]);

        } catch (\Exception $e) {
            Log::error('Delete address error: ' . $e->getMessage());
            return response()->json([
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }
}