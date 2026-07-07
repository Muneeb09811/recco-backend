<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\WashermanController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\ServiceController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\ReviewController;
use App\Http\Controllers\Api\ContactController;
use App\Http\Controllers\Api\TestimonialController;
use App\Http\Controllers\Api\FaqController;
use App\Http\Controllers\Api\SettingController;

/*
|--------------------------------------------------------------------------
| API Routes - RECCO Laundry Management System
|--------------------------------------------------------------------------
*/

// ==================== PUBLIC ROUTES ====================
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);

// Public content
Route::get('/services', [ServiceController::class, 'index']);
Route::get('/services/{id}', [ServiceController::class, 'show']);
Route::get('/testimonials', [ReviewController::class, 'testimonials']);
Route::get('/faqs', [FaqController::class, 'index']);
Route::post('/contact', [ContactController::class, 'store']);

// ==================== PROTECTED ROUTES ====================
Route::middleware('auth:sanctum')->group(function () {
    
    // Auth
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);
    Route::post('/change-password', [AuthController::class, 'changePassword']);
    Route::put('/profile', [AuthController::class, 'updateProfile']);
    
    // Notifications (common)
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markAsRead']);
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllAsRead']);
    Route::delete('/notifications/{id}', [NotificationController::class, 'destroy']);
    
    // ==================== ADMIN ROUTES ====================
    Route::middleware('role:admin')->prefix('admin')->group(function () {
        
        // Dashboard & Analytics
        Route::get('/dashboard', [AdminController::class, 'dashboard']);
        Route::get('/dashboard/revenue-chart', [AdminController::class, 'revenueChart']);
        Route::get('/dashboard/orders-chart', [AdminController::class, 'ordersChart']);
        Route::get('/dashboard/customer-growth', [AdminController::class, 'customerGrowth']);
        Route::get('/dashboard/washerman-growth', [AdminController::class, 'washermanGrowth']);
        Route::get('/dashboard/daily-orders', [AdminController::class, 'dailyOrders']);
        
        // Customer Management
        Route::get('/customers', [AdminController::class, 'customers']);
        Route::get('/customers/{id}', [AdminController::class, 'customerDetails']);
        Route::delete('/customers/{id}', [AdminController::class, 'deleteCustomer']);
        
        // Washerman Management
        Route::get('/washermen', [AdminController::class, 'washermen']);
        Route::get('/washermen/{id}', [AdminController::class, 'washermanDetails']);
        Route::get('/washermen/pending-approvals', [AdminController::class, 'pendingApprovals']);
        Route::post('/washermen/{id}/approve', [AdminController::class, 'approveWasherman']);
        Route::post('/washermen/{id}/reject', [AdminController::class, 'rejectWasherman']);
        Route::delete('/washermen/{id}', [AdminController::class, 'deleteWasherman']);
        
        // Order Management
        Route::get('/orders', [AdminController::class, 'orders']);
        Route::get('/orders/{id}', [AdminController::class, 'orderDetails']);
        Route::get('/orders/export', [AdminController::class, 'exportOrders']);
        Route::post('/orders/{id}/assign', [AdminController::class, 'assignOrder']);
        
        // Payment Management
        Route::get('/payments', [AdminController::class, 'payments']);
        Route::post('/payments/{id}/mark-paid', [AdminController::class, 'markPaymentPaid']);
        
        // Service Management
        Route::apiResource('/services', ServiceController::class);
        
        // Testimonial Management
        Route::apiResource('/testimonials', TestimonialController::class);
        
        // FAQ Management
        Route::get('/faqs', [FaqController::class, 'adminIndex']);
        Route::post('/faqs', [FaqController::class, 'store']);
        Route::put('/faqs/{id}', [FaqController::class, 'update']);
        Route::delete('/faqs/{id}', [FaqController::class, 'destroy']);
        
        // Contact Messages
        Route::get('/contact-messages', [ContactController::class, 'index']);
        Route::post('/contact-messages/{id}/reply', [ContactController::class, 'reply']);
        Route::delete('/contact-messages/{id}', [ContactController::class, 'destroy']);
        
        // Settings
        Route::get('/settings', [SettingController::class, 'index']);
        Route::put('/settings', [SettingController::class, 'update']);
    });
    
    // ==================== CUSTOMER ROUTES ====================
    Route::middleware('role:customer')->prefix('customer')->group(function () {
        
        // Dashboard
        Route::get('/dashboard', [CustomerController::class, 'dashboard']);
        
        // Orders
        Route::post('/orders', [OrderController::class, 'store']);
        Route::get('/orders/active', [CustomerController::class, 'activeOrders']);
        Route::get('/orders/history', [CustomerController::class, 'orderHistory']);
        Route::get('/orders/{id}', [CustomerController::class, 'orderDetails']);
        Route::post('/orders/{id}/cancel', [OrderController::class, 'cancelOrder']);
        
        // Invoices
        Route::get('/invoices', [CustomerController::class, 'invoices']);
        
        // Reviews
        Route::post('/orders/{id}/review', [CustomerController::class, 'submitReview']);
        Route::get('/reviews', [CustomerController::class, 'myReviews']);
        
        // Addresses
        Route::get('/addresses', [CustomerController::class, 'addresses']);
        Route::post('/addresses', [CustomerController::class, 'storeAddress']);
        Route::put('/addresses/{id}', [CustomerController::class, 'updateAddress']);
        Route::delete('/addresses/{id}', [CustomerController::class, 'destroyAddress']);
        
        // Notifications
        Route::get('/notifications', [CustomerController::class, 'notifications']);
        Route::post('/notifications/{id}/read', [CustomerController::class, 'markNotificationRead']);
        Route::post('/notifications/read-all', [CustomerController::class, 'markAllNotificationsRead']);
    });
    
    // ==================== WASHERMAN ROUTES ====================
    Route::middleware(['role:washerman', 'washerman.approved'])->prefix('washerman')->group(function () {
        
        // Dashboard
        Route::get('/dashboard', [WashermanController::class, 'dashboard']);
        
        // Orders
        Route::get('/orders', [WashermanController::class, 'assignedOrders']);
        Route::get('/orders/{id}', [WashermanController::class, 'orderDetails']);
        Route::post('/orders/{id}/accept', [OrderController::class, 'acceptOrder']);
        Route::post('/orders/{id}/reject', [OrderController::class, 'rejectOrder']);
        Route::post('/orders/{id}/progress', [OrderController::class, 'updateProgress']);
        Route::post('/orders/{id}/complete', [OrderController::class, 'markCompleted']);
        Route::post('/orders/{id}/deliver', [OrderController::class, 'markDelivered']);
        
        // Performance
        Route::get('/performance', [WashermanController::class, 'performance']);
        Route::get('/history', [WashermanController::class, 'history']);
        
        // Notifications
        Route::get('/notifications', [WashermanController::class, 'notifications']);
    });
});