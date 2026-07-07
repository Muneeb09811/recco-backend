<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Washerman extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'shop_name',
        'cnic',
        'experience',
        'specialization',
        'rating',
        'total_reviews',
        'total_orders_completed',
        'total_orders_pending',
        'total_orders_active',
        'average_delivery_time',
        'approval_status',
        'rejection_reason',
        'approved_at',
        'approved_by',
        'is_available',
        'service_charge',
        'service_area',
    ];

    protected $casts = [
        'rating' => 'decimal:2',
        'average_delivery_time' => 'decimal:2',
        'service_charge' => 'decimal:2',
        'is_available' => 'boolean',
        'approved_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function assignedOrders()
    {
        return $this->hasMany(Order::class, 'washerman_id', 'user_id');
    }

    public function pendingOrders()
    {
        return $this->assignedOrders()->where('status', 'pending');
    }

    public function activeOrders()
    {
        return $this->assignedOrders()->whereIn('status', ['accepted', 'picked_up', 'washing', 'cleaning', 'ironing', 'packing']);
    }

    public function completedOrders()
    {
        return $this->assignedOrders()->where('status', 'completed');
    }

    public function deliveredOrders()
    {
        return $this->assignedOrders()->where('status', 'delivered');
    }

    public function reviews()
    {
        return $this->hasMany(Review::class, 'washerman_id', 'user_id');
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function isPending(): bool
    {
        return $this->approval_status === 'pending';
    }

    public function isApproved(): bool
    {
        return $this->approval_status === 'approved';
    }

    public function isRejected(): bool
    {
        return $this->approval_status === 'rejected';
    }

    public function updateStatistics(): void
    {
        $this->update([
            'total_orders_completed' => $this->deliveredOrders()->count(),
            'total_orders_pending' => $this->pendingOrders()->count(),
            'total_orders_active' => $this->activeOrders()->count(),
        ]);
    }

    public function updateRating(): void
    {
        $avgRating = $this->reviews()->avg('rating');
        $totalReviews = $this->reviews()->count();
        
        $this->update([
            'rating' => $avgRating ?? 0,
            'total_reviews' => $totalReviews,
        ]);
    }
}