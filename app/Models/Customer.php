<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'company_name',
        'total_orders',
        'active_orders',
        'completed_orders',
        'total_spent',
        'loyalty_points',
        'is_vip',
    ];

    protected $casts = [
        'is_vip' => 'boolean',
        'total_spent' => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class, 'customer_id', 'user_id');
    }

    public function activeOrders()
    {
        return $this->orders()->whereIn('status', ['pending', 'accepted', 'picked_up', 'washing', 'cleaning', 'ironing', 'packing', 'completed']);
    }

    public function completedOrders()
    {
        return $this->orders()->where('status', 'delivered');
    }

    public function pendingOrders()
    {
        return $this->orders()->where('status', 'pending');
    }

    public function payments()
    {
        return $this->hasMany(Payment::class, 'customer_id', 'user_id');
    }

    public function reviews()
    {
        return $this->hasMany(Review::class, 'customer_id', 'user_id');
    }

    public function addresses()
    {
        return $this->hasMany(Address::class, 'user_id');
    }

    // Auto-update statistics
    public function updateStatistics(): void
    {
        $this->update([
            'total_orders' => $this->orders()->count(),
            'active_orders' => $this->activeOrders()->count(),
            'completed_orders' => $this->completedOrders()->count(),
            'total_spent' => $this->orders()
                ->where('payment_status', 'paid')
                ->sum('final_amount'),
        ]);
    }
}