<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'order_number',
        'customer_id',
        'washerman_id',
        'service_id',
        'pickup_address',
        'pickup_phone',
        'pickup_date',
        'pickup_time',
        'expected_delivery_date',
        'actual_delivery_date',
        'shirts_quantity',
        'tshirts_quantity',
        'pants_quantity',
        'jeans_quantity',
        'coats_quantity',
        'bedsheets_quantity',
        'blankets_quantity',
        'curtains_quantity',
        'other_items_quantity',
        'total_quantity',
        'completed_quantity',
        'remaining_quantity',
        'delivered_quantity',
        'status',
        'payment_status',
        'payment_method',
        'total_amount',
        'discount',
        'tax',
        'final_amount',
        'special_instructions',
        'order_notes',
        'images',
        'progress_percentage',
        'accepted_at',
        'started_at',
        'completed_at',
        'delivered_at',
    ];

    protected $casts = [
        'images' => 'array',
        'pickup_date' => 'date',
        'expected_delivery_date' => 'date',
        'actual_delivery_date' => 'date',
        'accepted_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'delivered_at' => 'datetime',
        'total_amount' => 'decimal:2',
        'discount' => 'decimal:2',
        'tax' => 'decimal:2',
        'final_amount' => 'decimal:2',
        'progress_percentage' => 'decimal:2',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($order) {
            if (!$order->order_number) {
                $order->order_number = 'REC-' . strtoupper(uniqid());
            }
        });
    }

    // Relationships
    public function customer()
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function washerman()
    {
        return $this->belongsTo(User::class, 'washerman_id');
    }

    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function progress()
    {
        return $this->hasMany(OrderProgress::class)->orderBy('created_at', 'desc');
    }

    public function latestProgress()
    {
        return $this->hasOne(OrderProgress::class)->latestOfMany();
    }

    public function payment()
    {
        return $this->hasOne(Payment::class);
    }

    public function review()
    {
        return $this->hasOne(Review::class);
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', ['accepted', 'picked_up', 'washing', 'cleaning', 'ironing', 'packing']);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeDelivered($query)
    {
        return $query->where('status', 'delivered');
    }

    public function scopeToday($query)
    {
        return $query->whereDate('created_at', today());
    }

    public function scopeThisWeek($query)
    {
        return $query->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]);
    }

    public function scopeThisMonth($query)
    {
        return $query->whereMonth('created_at', now()->month)
                     ->whereYear('created_at', now()->year);
    }

    // Helpers
    public function calculateTotalQuantity(): int
    {
        return $this->shirts_quantity + 
               $this->tshirts_quantity + 
               $this->pants_quantity + 
               $this->jeans_quantity + 
               $this->coats_quantity + 
               $this->bedsheets_quantity + 
               $this->blankets_quantity + 
               $this->curtains_quantity + 
               $this->other_items_quantity;
    }

    public function calculateTotalAmount(): float
    {
        // Calculate based on service pricing
        $service = $this->service;
        if (!$service) return 0;

        $total = 0;
        $total += $this->shirts_quantity * $service->base_price;
        $total += $this->tshirts_quantity * $service->base_price;
        $total += $this->pants_quantity * $service->base_price;
        $total += $this->jeans_quantity * ($service->base_price * 1.2);
        $total += $this->coats_quantity * ($service->base_price * 2);
        $total += $this->bedsheets_quantity * ($service->base_price * 1.5);
        $total += $this->blankets_quantity * ($service->base_price * 2.5);
        $total += $this->curtains_quantity * ($service->base_price * 1.8);
        $total += $this->other_items_quantity * $service->base_price;

        return $total;
    }

    public function updateProgress(): void
    {
        $totalQty = $this->total_quantity ?: $this->calculateTotalQuantity();
        $completedQty = $this->completed_quantity;
        
        $percentage = $totalQty > 0 ? ($completedQty / $totalQty) * 100 : 0;
        
        $this->update([
            'remaining_quantity' => $totalQty - $completedQty,
            'progress_percentage' => round($percentage, 2),
        ]);

        // Auto-complete if all items done
        if ($completedQty >= $totalQty && $totalQty > 0) {
            $this->update([
                'status' => 'completed',
                'completed_at' => now(),
            ]);
        }
    }

    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'pending' => 'yellow',
            'accepted' => 'blue',
            'picked_up' => 'indigo',
            'washing', 'cleaning' => 'cyan',
            'ironing' => 'purple',
            'packing' => 'pink',
            'completed' => 'green',
            'delivered' => 'emerald',
            'cancelled', 'rejected' => 'red',
            default => 'gray',
        };
    }

    public function getFormattedOrderNumberAttribute(): string
    {
        return '#' . $this->order_number;
    }
}