<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderProgress;
use App\Models\User;
use App\Models\Washerman;
use App\Models\Notification;
use App\Events\OrderCreated;
use App\Events\OrderStatusUpdated;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderController extends Controller
{
    /**
     * Create new order (Customer)
     */
    public function store(Request $request): JsonResponse
    {
        // Validation
        $validated = $request->validate([
            'pickup_address' => 'required|string',
            'pickup_phone' => 'required|string|max:20',
            'pickup_date' => 'required|date|after_or_equal:today',
            'pickup_time' => 'nullable|date_format:H:i',
            'expected_delivery_date' => 'required|date|after:pickup_date',
            'service_id' => 'nullable|exists:services,id',
            'shirts_quantity' => 'sometimes|integer|min:0',
            'tshirts_quantity' => 'sometimes|integer|min:0',
            'pants_quantity' => 'sometimes|integer|min:0',
            'jeans_quantity' => 'sometimes|integer|min:0',
            'coats_quantity' => 'sometimes|integer|min:0',
            'bedsheets_quantity' => 'sometimes|integer|min:0',
            'blankets_quantity' => 'sometimes|integer|min:0',
            'curtains_quantity' => 'sometimes|integer|min:0',
            'other_items_quantity' => 'sometimes|integer|min:0',
            'special_instructions' => 'nullable|string',
            'order_notes' => 'nullable|string',
            'payment_method' => 'in:cash,card,bank_transfer,online',
            'images' => 'nullable|array',
            'images.*' => 'image|max:2048',
        ]);

        // Calculate total quantity - Safe with null coalescing
        $totalQuantity = ($validated['shirts_quantity'] ?? 0) +
                        ($validated['tshirts_quantity'] ?? 0) +
                        ($validated['pants_quantity'] ?? 0) +
                        ($validated['jeans_quantity'] ?? 0) +
                        ($validated['coats_quantity'] ?? 0) +
                        ($validated['bedsheets_quantity'] ?? 0) +
                        ($validated['blankets_quantity'] ?? 0) +
                        ($validated['curtains_quantity'] ?? 0) +
                        ($validated['other_items_quantity'] ?? 0);

        // Check minimum items
        if ($totalQuantity <= 0) {
            return response()->json([
                'message' => 'Please add at least one item to your order.',
            ], 400);
        }

        // Handle image uploads
        $imagePaths = [];
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                $path = $image->store('order-images', 'public');
                $imagePaths[] = $path;
            }
        }

        // Get current user
        /** @var \App\Models\User $currentUser */
        $currentUser = Auth::user();

        // Find available washerman (least busy)
        $availableWasherman = Washerman::where('approval_status', 'approved')
            ->where('is_available', true)
            ->orderBy('total_orders_active', 'asc')
            ->first();

        DB::beginTransaction();

        try {
            // Create order
            $order = Order::create([
                'customer_id' => $currentUser->id,
                'washerman_id' => $availableWasherman ? $availableWasherman->user_id : null,
                'service_id' => $validated['service_id'] ?? null,
                'pickup_address' => $validated['pickup_address'],
                'pickup_phone' => $validated['pickup_phone'],
                'pickup_date' => $validated['pickup_date'],
                'pickup_time' => $validated['pickup_time'] ?? null,
                'expected_delivery_date' => $validated['expected_delivery_date'],
                'shirts_quantity' => $validated['shirts_quantity'] ?? 0,
                'tshirts_quantity' => $validated['tshirts_quantity'] ?? 0,
                'pants_quantity' => $validated['pants_quantity'] ?? 0,
                'jeans_quantity' => $validated['jeans_quantity'] ?? 0,
                'coats_quantity' => $validated['coats_quantity'] ?? 0,
                'bedsheets_quantity' => $validated['bedsheets_quantity'] ?? 0,
                'blankets_quantity' => $validated['blankets_quantity'] ?? 0,
                'curtains_quantity' => $validated['curtains_quantity'] ?? 0,
                'other_items_quantity' => $validated['other_items_quantity'] ?? 0,
                'total_quantity' => $totalQuantity,
                'remaining_quantity' => $totalQuantity,
                'completed_quantity' => 0,
                'delivered_quantity' => 0,
                'special_instructions' => $validated['special_instructions'] ?? null,
                'order_notes' => $validated['order_notes'] ?? null,
                'payment_method' => $validated['payment_method'] ?? 'cash',
                'payment_status' => 'pending',
                'images' => !empty($imagePaths) ? $imagePaths : null,
                'status' => 'pending',
                'progress_percentage' => 0,
            ]);

            // Calculate total amount
            $totalAmount = $order->calculateTotalAmount();
            $taxPercentage = config('recco.tax_percentage', 0);
            $tax = $totalAmount * ($taxPercentage / 100);
            $finalAmount = $totalAmount + $tax;

            $order->update([
                'total_amount' => $totalAmount,
                'tax' => $tax,
                'final_amount' => $finalAmount,
            ]);

            // Create initial progress entry
            OrderProgress::create([
                'order_id' => $order->id,
                'updated_by' => $currentUser->id,
                'stage' => 'pending',
                'completed_quantity' => 0,
                'remaining_quantity' => $totalQuantity,
                'notes' => 'Order created successfully',
            ]);

            // Notify assigned washerman
            if ($availableWasherman) {
                Notification::create([
                    'user_id' => $availableWasherman->user_id,
                    'type' => 'new_order',
                    'title' => 'New Order Received',
                    'message' => "You have received a new order #{$order->order_number} with {$totalQuantity} items.",
                    'data' => ['order_id' => $order->id],
                    'link' => "/washerman/orders/{$order->id}",
                ]);
            }

            // Notify all admins
            $admins = User::admins()->get();
            foreach ($admins as $admin) {
                Notification::create([
                    'user_id' => $admin->id,
                    'type' => 'new_order',
                    'title' => 'New Order Placed',
                    'message' => "Customer {$currentUser->name} placed a new order #{$order->order_number}.",
                    'data' => ['order_id' => $order->id],
                    'link' => "/admin/orders/{$order->id}",
                ]);
            }

            // Notify customer
            Notification::create([
                'user_id' => $currentUser->id,
                'type' => 'order_created',
                'title' => 'Order Placed Successfully',
                'message' => "Your order #{$order->order_number} has been placed successfully. We'll notify you once a washerman accepts it.",
                'data' => ['order_id' => $order->id],
                'link' => "/customer/orders/{$order->id}",
            ]);

            // Update customer statistics
            if ($currentUser->customer) {
                $currentUser->customer->updateStatistics();
            }

            DB::commit();

            // Fire event
            event(new OrderCreated($order));

            return response()->json([
                'message' => 'Order placed successfully!',
                'order' => $order->load('service'),
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Order creation failed: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to create order: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Accept order (Washerman)
     */
    public function acceptOrder(int $id): JsonResponse
    {
        /** @var \App\Models\User $currentUser */
        $currentUser = Auth::user();

        $order = Order::where('washerman_id', $currentUser->id)
            ->where('status', 'pending')
            ->findOrFail($id);

        $order->update([
            'status' => 'accepted',
            'accepted_at' => now(),
        ]);

        // Create progress entry
        OrderProgress::create([
            'order_id' => $order->id,
            'updated_by' => $currentUser->id,
            'stage' => 'accepted',
            'completed_quantity' => 0,
            'remaining_quantity' => $order->total_quantity,
            'notes' => 'Order accepted by washerman',
        ]);

        // Notify customer
        Notification::create([
            'user_id' => $order->customer_id,
            'type' => 'order_accepted',
            'title' => 'Order Accepted',
            'message' => "Your order #{$order->order_number} has been accepted by the washerman.",
            'data' => ['order_id' => $order->id],
            'link' => "/customer/orders/{$order->id}",
        ]);

        event(new OrderStatusUpdated($order, 'accepted'));

        return response()->json([
            'message' => 'Order accepted successfully.',
            'order' => $order,
        ]);
    }

    /**
     * Reject order (Washerman)
     */
    public function rejectOrder(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        /** @var \App\Models\User $currentUser */
        $currentUser = Auth::user();

        $order = Order::where('washerman_id', $currentUser->id)
            ->where('status', 'pending')
            ->findOrFail($id);

        $order->update([
            'status' => 'rejected',
            'order_notes' => ($order->order_notes ?? '') . "\n\nRejection Reason: " . $request->reason,
        ]);

        // Create progress entry
        OrderProgress::create([
            'order_id' => $order->id,
            'updated_by' => $currentUser->id,
            'stage' => 'rejected',
            'notes' => 'Order rejected: ' . $request->reason,
        ]);

        // Notify customer
        Notification::create([
            'user_id' => $order->customer_id,
            'type' => 'order_rejected',
            'title' => 'Order Rejected',
            'message' => "Your order #{$order->order_number} has been rejected. We'll assign another washerman.",
            'data' => ['order_id' => $order->id],
            'link' => "/customer/orders/{$order->id}",
        ]);

        // Try to assign another washerman
        $nextWasherman = Washerman::where('approval_status', 'approved')
            ->where('is_available', true)
            ->where('user_id', '!=', $currentUser->id)
            ->orderBy('total_orders_active', 'asc')
            ->first();

        if ($nextWasherman) {
            $order->update([
                'washerman_id' => $nextWasherman->user_id,
                'status' => 'pending',
            ]);

            Notification::create([
                'user_id' => $nextWasherman->user_id,
                'type' => 'new_order',
                'title' => 'New Order Assigned',
                'message' => "You have been assigned order #{$order->order_number}.",
                'data' => ['order_id' => $order->id],
                'link' => "/washerman/orders/{$order->id}",
            ]);
        }

        return response()->json([
            'message' => 'Order rejected successfully.',
        ]);
    }

    /**
     * Update order progress (Washerman)
     */
    public function updateProgress(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'stage' => 'required|in:picked_up,washing,cleaning,ironing,packing,completed',
            'completed_quantity' => 'sometimes|integer|min:0',
            'remaining_quantity' => 'sometimes|integer|min:0',
            'notes' => 'nullable|string',
            'images' => 'nullable|array',
            'images.*' => 'image|max:2048',
        ]);

        /** @var \App\Models\User $currentUser */
        $currentUser = Auth::user();

        $order = Order::where('washerman_id', $currentUser->id)
            ->whereIn('status', ['accepted', 'picked_up', 'washing', 'cleaning', 'ironing', 'packing'])
            ->findOrFail($id);

        // Handle image uploads
        $imagePaths = [];
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                $path = $image->store('progress-images', 'public');
                $imagePaths[] = $path;
            }
        }

        DB::beginTransaction();

        try {
            // Prepare update data
            $updateData = ['status' => $validated['stage']];

            // Handle completed quantity
            if (isset($validated['completed_quantity'])) {
                $completedQty = $validated['completed_quantity'];
                $updateData['completed_quantity'] = $completedQty;
                $updateData['remaining_quantity'] = $order->total_quantity - $completedQty;

                // Calculate progress percentage
                $percentage = $order->total_quantity > 0
                    ? ($completedQty / $order->total_quantity) * 100
                    : 0;
                $updateData['progress_percentage'] = round($percentage, 2);
            }

            // Special stage handling
            if ($validated['stage'] === 'picked_up') {
                $updateData['started_at'] = now();
            }

            if ($validated['stage'] === 'completed') {
                $updateData['completed_at'] = now();
                $updateData['completed_quantity'] = $order->total_quantity;
                $updateData['remaining_quantity'] = 0;
                $updateData['progress_percentage'] = 100;
            }

            $order->update($updateData);

            // Create progress entry
            OrderProgress::create([
                'order_id' => $order->id,
                'updated_by' => $currentUser->id,
                'stage' => $validated['stage'],
                'completed_quantity' => $validated['completed_quantity'] ?? $order->completed_quantity,
                'remaining_quantity' => $validated['remaining_quantity'] ?? $order->remaining_quantity,
                'notes' => $validated['notes'] ?? null,
                'images' => !empty($imagePaths) ? $imagePaths : null,
            ]);

            // Stage labels for notification
            $stageLabels = [
                'picked_up' => 'Picked Up',
                'washing' => 'Washing Started',
                'cleaning' => 'Cleaning In Progress',
                'ironing' => 'Ironing In Progress',
                'packing' => 'Packing',
                'completed' => 'Completed',
            ];

            $stageLabel = $stageLabels[$validated['stage']] ?? $validated['stage'];

            // Notify customer
            Notification::create([
                'user_id' => $order->customer_id,
                'type' => 'order_progress',
                'title' => "Order Update: {$stageLabel}",
                'message' => "Your order #{$order->order_number} status has been updated to {$stageLabel}.",
                'data' => [
                    'order_id' => $order->id,
                    'stage' => $validated['stage'],
                    'completed_quantity' => $order->completed_quantity,
                    'remaining_quantity' => $order->remaining_quantity,
                ],
                'link' => "/customer/orders/{$order->id}",
            ]);

            DB::commit();

            event(new OrderStatusUpdated($order, $validated['stage']));

            return response()->json([
                'message' => 'Order progress updated successfully.',
                'order' => $order->fresh(),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Order progress update failed: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to update progress: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Mark order as completed (Washerman)
     */
    public function markCompleted(int $id): JsonResponse
    {
        /** @var \App\Models\User $currentUser */
        $currentUser = Auth::user();

        $order = Order::where('washerman_id', $currentUser->id)
            ->whereIn('status', ['accepted', 'picked_up', 'washing', 'cleaning', 'ironing', 'packing'])
            ->findOrFail($id);

        $order->update([
            'status' => 'completed',
            'completed_at' => now(),
            'completed_quantity' => $order->total_quantity,
            'remaining_quantity' => 0,
            'progress_percentage' => 100,
        ]);

        // Create progress entry
        OrderProgress::create([
            'order_id' => $order->id,
            'updated_by' => $currentUser->id,
            'stage' => 'completed',
            'completed_quantity' => $order->total_quantity,
            'remaining_quantity' => 0,
            'notes' => 'All items completed',
        ]);

        // Notify customer
        Notification::create([
            'user_id' => $order->customer_id,
            'type' => 'order_completed',
            'title' => 'Order Completed',
            'message' => "Your order #{$order->order_number} has been completed and is ready for delivery.",
            'data' => ['order_id' => $order->id],
            'link' => "/customer/orders/{$order->id}",
        ]);

        event(new OrderStatusUpdated($order, 'completed'));

        return response()->json([
            'message' => 'Order marked as completed.',
            'order' => $order,
        ]);
    }

    /**
     * Mark order as delivered (Washerman)
     */
    public function markDelivered(int $id): JsonResponse
    {
        /** @var \App\Models\User $currentUser */
        $currentUser = Auth::user();

        $order = Order::where('washerman_id', $currentUser->id)
            ->where('status', 'completed')
            ->findOrFail($id);

        $order->update([
            'status' => 'delivered',
            'delivered_at' => now(),
            'actual_delivery_date' => now()->toDateString(),
            'delivered_quantity' => $order->total_quantity,
        ]);

        // Create progress entry
        OrderProgress::create([
            'order_id' => $order->id,
            'updated_by' => $currentUser->id,
            'stage' => 'delivered',
            'completed_quantity' => $order->total_quantity,
            'remaining_quantity' => 0,
            'notes' => 'Order delivered to customer',
        ]);

        // Notify customer
        Notification::create([
            'user_id' => $order->customer_id,
            'type' => 'order_delivered',
            'title' => 'Order Delivered',
            'message' => "Your order #{$order->order_number} has been delivered. Thank you for choosing Recco!",
            'data' => ['order_id' => $order->id],
            'link' => "/customer/orders/{$order->id}",
        ]);

        // Notify admins
        $admins = User::admins()->get();
        foreach ($admins as $admin) {
            Notification::create([
                'user_id' => $admin->id,
                'type' => 'order_delivered',
                'title' => 'Order Delivered',
                'message' => "Order #{$order->order_number} has been delivered.",
                'data' => ['order_id' => $order->id],
                'link' => "/admin/orders/{$order->id}",
            ]);
        }

        // Update statistics safely
        try {
            $customer = $order->customer;
            if ($customer && $customer->customer) {
                $customer->customer->updateStatistics();
            }

            $washerman = $order->washerman;
            if ($washerman && $washerman->washerman) {
                $washerman->washerman->updateStatistics();
            }
        } catch (\Exception $e) {
            Log::warning('Failed to update statistics: ' . $e->getMessage());
        }

        event(new OrderStatusUpdated($order, 'delivered'));

        return response()->json([
            'message' => 'Order marked as delivered.',
            'order' => $order,
        ]);
    }

    /**
     * Cancel order (Customer)
     */
    public function cancelOrder(Request $request, int $id): JsonResponse
    {
        /** @var \App\Models\User $currentUser */
        $currentUser = Auth::user();

        $order = Order::where('customer_id', $currentUser->id)
            ->whereIn('status', ['pending', 'accepted'])
            ->findOrFail($id);

        $order->update([
            'status' => 'cancelled',
            'order_notes' => ($order->order_notes ?? '') . "\n\nCancelled: " . ($request->reason ?? 'Customer request'),
        ]);

        // Create progress entry
        OrderProgress::create([
            'order_id' => $order->id,
            'updated_by' => $currentUser->id,
            'stage' => 'cancelled',
            'notes' => 'Order cancelled by customer',
        ]);

        // Notify washerman if assigned
        if ($order->washerman_id) {
            Notification::create([
                'user_id' => $order->washerman_id,
                'type' => 'order_cancelled',
                'title' => 'Order Cancelled',
                'message' => "Order #{$order->order_number} has been cancelled by the customer.",
                'data' => ['order_id' => $order->id],
                'link' => "/washerman/orders/{$order->id}",
            ]);
        }

        // Notify admins
        $admins = User::admins()->get();
        foreach ($admins as $admin) {
            Notification::create([
                'user_id' => $admin->id,
                'type' => 'order_cancelled',
                'title' => 'Order Cancelled',
                'message' => "Order #{$order->order_number} has been cancelled.",
                'data' => ['order_id' => $order->id],
                'link' => "/admin/orders/{$order->id}",
            ]);
        }

        return response()->json([
            'message' => 'Order cancelled successfully.',
            'order' => $order,
        ]);
    }
}