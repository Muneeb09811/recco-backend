<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Washerman;
use App\Models\Order;
use App\Models\Payment;
use App\Models\ApprovalRequest;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class AdminController extends Controller
{
    /**
     * Get admin dashboard statistics
     */
    public function dashboard(): JsonResponse
    {
        try {
            $stats = [
                'total_customers' => User::customers()->count(),
                'total_washermen' => User::washermen()->count(),
                'pending_washerman_requests' => Washerman::where('approval_status', 'pending')->count(),
                'approved_washermen' => Washerman::where('approval_status', 'approved')->count(),
                'rejected_washermen' => Washerman::where('approval_status', 'rejected')->count(),
                
                'total_orders' => Order::count(),
                'pending_orders' => Order::pending()->count(),
                'active_orders' => Order::active()->count(),
                'completed_orders' => Order::completed()->count(),
                'delivered_orders' => Order::delivered()->count(),
                'today_orders' => Order::today()->count(),
                
                'monthly_revenue' => Order::thisMonth()
                    ->where('payment_status', 'paid')
                    ->sum('final_amount'),
                
                'weekly_revenue' => Order::thisWeek()
                    ->where('payment_status', 'paid')
                    ->sum('final_amount'),
                
                'pending_payments' => Order::where('payment_status', 'pending')
                    ->sum('final_amount'),
                
                'paid_payments' => Order::where('payment_status', 'paid')
                    ->sum('final_amount'),
            ];

            return response()->json($stats);
            
        } catch (\Exception $e) {
            Log::error('Admin dashboard error: ' . $e->getMessage());
            return response()->json([
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get revenue chart data
     */
    public function revenueChart(Request $request): JsonResponse
    {
        $period = $request->get('period', 'monthly');
        
        $query = Order::where('payment_status', 'paid');
        
        if ($period === 'weekly') {
            $data = $query->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])
                ->selectRaw('DATE(created_at) as date, SUM(final_amount) as total')
                ->groupBy('date')
                ->orderBy('date')
                ->get();
        } else {
            $data = $query->whereYear('created_at', now()->year)
                ->selectRaw('MONTH(created_at) as month, SUM(final_amount) as total')
                ->groupBy('month')
                ->orderBy('month')
                ->get();
        }

        return response()->json($data);
    }

    /**
     * Get orders chart data
     */
    public function ordersChart(Request $request): JsonResponse
    {
        $period = $request->get('period', 'monthly');
        
        if ($period === 'weekly') {
            $data = Order::whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])
                ->selectRaw('DATE(created_at) as date, COUNT(*) as total')
                ->groupBy('date')
                ->orderBy('date')
                ->get();
        } else {
            $data = Order::whereYear('created_at', now()->year)
                ->selectRaw('MONTH(created_at) as month, COUNT(*) as total')
                ->groupBy('month')
                ->orderBy('month')
                ->get();
        }

        return response()->json($data);
    }

    /**
     * Get customer growth chart
     */
    public function customerGrowth(): JsonResponse
    {
        $data = User::customers()
            ->whereYear('created_at', now()->year)
            ->selectRaw('MONTH(created_at) as month, COUNT(*) as total')
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        return response()->json($data);
    }

    /**
     * Get washerman growth chart
     */
    public function washermanGrowth(): JsonResponse
    {
        $data = User::washermen()
            ->whereYear('created_at', now()->year)
            ->selectRaw('MONTH(created_at) as month, COUNT(*) as total')
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        return response()->json($data);
    }

    /**
     * Get daily orders
     */
    public function dailyOrders(): JsonResponse
    {
        $data = Order::whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])
            ->selectRaw('DATE(created_at) as date, COUNT(*) as total')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return response()->json($data);
    }

    /**
     * Get all customers
     */
    public function customers(Request $request): JsonResponse
    {
        $query = User::customers()->with('customer');

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $customers = $query->latest()->paginate($request->get('per_page', 15));

        return response()->json($customers);
    }

    /**
     * Get customer details
     */
    public function customerDetails(int $id): JsonResponse
    {
        try {
            $customer = User::customers()
                ->with(['customer', 'orders', 'orders.service', 'orders.payment', 'addresses', 'reviews'])
                ->findOrFail($id);

            $stats = [
                'total_orders' => $customer->orders()->count(),
                'active_orders' => $customer->orders()->whereIn('status', ['pending', 'accepted', 'washing', 'cleaning', 'ironing', 'packing', 'completed'])->count(),
                'completed_orders' => $customer->orders()->where('status', 'delivered')->count(),
                'pending_orders' => $customer->orders()->where('status', 'pending')->count(),
                'total_spent' => $customer->orders()->where('payment_status', 'paid')->sum('final_amount'),
                'total_clothes' => $customer->orders()->sum('total_quantity'),
                'completed_clothes' => $customer->orders()->sum('completed_quantity'),
                'remaining_clothes' => $customer->orders()->sum('remaining_quantity'),
                'delivered_clothes' => $customer->orders()->sum('delivered_quantity'),
            ];

            return response()->json([
                'customer' => $customer,
                'stats' => $stats,
            ]);
            
        } catch (\Exception $e) {
            Log::error('Customer details error: ' . $e->getMessage());
            return response()->json([
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete customer
     */
    public function deleteCustomer(int $id): JsonResponse
    {
        try {
            $customer = User::customers()->findOrFail($id);
            $customer->delete();

            return response()->json([
                'message' => 'Customer deleted successfully.',
            ]);
            
        } catch (\Exception $e) {
            Log::error('Delete customer error: ' . $e->getMessage());
            return response()->json([
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all washermen
     */
    public function washermen(Request $request): JsonResponse
    {
        $query = User::washermen()->with('washerman');

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if ($request->has('approval_status')) {
            $query->whereHas('washerman', function($q) use ($request) {
                $q->where('approval_status', $request->approval_status);
            });
        }

        $washermen = $query->latest()->paginate($request->get('per_page', 15));

        return response()->json($washermen);
    }

    /**
     * Get washerman details
     */
    public function washermanDetails(int $id): JsonResponse
    {
        try {
            $washerman = User::washermen()
                ->with(['washerman', 'assignedOrders', 'assignedOrders.service', 'reviews'])
                ->findOrFail($id);

            $stats = [
                'total_assigned' => $washerman->assignedOrders()->count(),
                'pending_orders' => $washerman->assignedOrders()->where('status', 'pending')->count(),
                'active_orders' => $washerman->assignedOrders()->whereIn('status', ['accepted', 'washing', 'cleaning', 'ironing', 'packing'])->count(),
                'completed_orders' => $washerman->assignedOrders()->where('status', 'completed')->count(),
                'delivered_orders' => $washerman->assignedOrders()->where('status', 'delivered')->count(),
                'average_rating' => $washerman->reviews()->avg('rating') ?? 0,
                'total_reviews' => $washerman->reviews()->count(),
            ];

            return response()->json([
                'washerman' => $washerman,
                'stats' => $stats,
            ]);
            
        } catch (\Exception $e) {
            Log::error('Washerman details error: ' . $e->getMessage());
            return response()->json([
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete washerman
     */
    public function deleteWasherman(int $id): JsonResponse
    {
        try {
            $washerman = User::washermen()->findOrFail($id);
            $washerman->delete();

            return response()->json([
                'message' => 'Washerman deleted successfully.',
            ]);
            
        } catch (\Exception $e) {
            Log::error('Delete washerman error: ' . $e->getMessage());
            return response()->json([
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get pending washerman approvals - FIXED
     */
    public function pendingApprovals(): JsonResponse
    {
        try {
            $approvals = Washerman::where('approval_status', 'pending')
                ->with('user')
                ->latest()
                ->get();

            return response()->json($approvals);
            
        } catch (\Exception $e) {
            Log::error('Pending approvals error: ' . $e->getMessage());
            return response()->json([
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Approve washerman
     */
    public function approveWasherman(int $id): JsonResponse
    {
        try {
            $washerman = Washerman::with('user')->findOrFail($id);

            $washerman->update([
                'approval_status' => 'approved',
                'approved_at' => now(),
                'approved_by' => Auth::id(),
            ]);

            $washerman->user->update([
                'status' => 'active',
            ]);

            // Update approval request
            ApprovalRequest::where('user_id', $washerman->user_id)
                ->where('type', 'washerman_registration')
                ->where('status', 'pending')
                ->update([
                    'status' => 'approved',
                    'reviewed_by' => Auth::id(),
                    'reviewed_at' => now(),
                ]);

            // Send notification
            Notification::create([
                'user_id' => $washerman->user_id,
                'type' => 'washerman_approved',
                'title' => 'Account Approved!',
                'message' => 'Congratulations! Your washerman account has been approved. You can now login and start accepting orders.',
                'link' => '/washerman/dashboard',
            ]);

            return response()->json([
                'message' => 'Washerman approved successfully.',
            ]);
            
        } catch (\Exception $e) {
            Log::error('Approve washerman error: ' . $e->getMessage());
            return response()->json([
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reject washerman
     */
    public function rejectWasherman(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        try {
            $washerman = Washerman::with('user')->findOrFail($id);

            $washerman->update([
                'approval_status' => 'rejected',
                'rejection_reason' => $request->reason,
            ]);

            $washerman->user->update([
                'status' => 'rejected',
            ]);

            // Update approval request
            ApprovalRequest::where('user_id', $washerman->user_id)
                ->where('type', 'washerman_registration')
                ->where('status', 'pending')
                ->update([
                    'status' => 'rejected',
                    'admin_notes' => $request->reason,
                    'reviewed_by' => Auth::id(),
                    'reviewed_at' => now(),
                ]);

            // Send notification
            Notification::create([
                'user_id' => $washerman->user_id,
                'type' => 'washerman_rejected',
                'title' => 'Account Rejected',
                'message' => 'Your washerman account has been rejected. Reason: ' . $request->reason,
                'link' => '/login',
            ]);

            return response()->json([
                'message' => 'Washerman rejected successfully.',
            ]);
            
        } catch (\Exception $e) {
            Log::error('Reject washerman error: ' . $e->getMessage());
            return response()->json([
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all orders
     */
    public function orders(Request $request): JsonResponse
    {
        $query = Order::with(['customer', 'washerman', 'service']);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('payment_status')) {
            $query->where('payment_status', $request->payment_status);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                  ->orWhereHas('customer', function($q) use ($search) {
                      $q->where('name', 'like', "%{$search}%");
                  });
            });
        }

        $orders = $query->latest()->paginate($request->get('per_page', 15));

        return response()->json($orders);
    }

    /**
     * Get order details
     */
    public function orderDetails(int $id): JsonResponse
    {
        try {
            $order = Order::with([
                'customer', 
                'washerman', 
                'service', 
                'items', 
                'progress.updatedBy', 
                'payment', 
                'review'
            ])->findOrFail($id);

            return response()->json($order);
            
        } catch (\Exception $e) {
            Log::error('Order details error: ' . $e->getMessage());
            return response()->json([
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Assign order to washerman
     */
    public function assignOrder(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'washerman_id' => 'required|exists:users,id',
        ]);

        try {
            $order = Order::findOrFail($id);
            $order->update([
                'washerman_id' => $request->washerman_id,
                'status' => 'pending',
            ]);

            // Notify washerman
            Notification::create([
                'user_id' => $request->washerman_id,
                'type' => 'new_order',
                'title' => 'New Order Assigned',
                'message' => "You have been assigned order #{$order->order_number}.",
                'data' => ['order_id' => $order->id],
                'link' => "/washerman/orders/{$order->id}",
            ]);

            return response()->json([
                'message' => 'Order assigned successfully.',
                'order' => $order,
            ]);
            
        } catch (\Exception $e) {
            Log::error('Assign order error: ' . $e->getMessage());
            return response()->json([
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export orders report
     */
    public function exportOrders(Request $request): JsonResponse
    {
        $query = Order::with(['customer', 'washerman', 'service']);

        if ($request->has('from_date')) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }

        if ($request->has('to_date')) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }

        $orders = $query->get();

        return response()->json([
            'message' => 'Export initiated',
            'count' => $orders->count(),
            'data' => $orders,
        ]);
    }

    /**
     * Get all payments
     */
    public function payments(Request $request): JsonResponse
    {
        $query = Payment::with(['order', 'customer']);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('method')) {
            $query->where('payment_method', $request->method);
        }

        $payments = $query->latest()->paginate($request->get('per_page', 15));

        return response()->json($payments);
    }

    /**
     * Mark payment as paid
     */
    public function markPaymentPaid(int $id): JsonResponse
    {
        try {
            $payment = Payment::findOrFail($id);
            $payment->update([
                'status' => 'completed',
                'paid_at' => now(),
            ]);

            $payment->order->update([
                'payment_status' => 'paid',
            ]);

            return response()->json([
                'message' => 'Payment marked as paid.',
                'payment' => $payment,
            ]);
            
        } catch (\Exception $e) {
            Log::error('Mark payment error: ' . $e->getMessage());
            return response()->json([
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }
}