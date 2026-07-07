<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    protected $fillable = [
        'order_id',
        'customer_id',
        'washerman_id',
        'rating',
        'comment',
        'is_approved',
    ];

    protected $casts = [
        'is_approved' => 'boolean',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function customer()
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function washerman()
    {
        return $this->belongsTo(User::class, 'washerman_id');
    }

    public function scopeApproved($query)
    {
        return $query->where('is_approved', true);
    }
}