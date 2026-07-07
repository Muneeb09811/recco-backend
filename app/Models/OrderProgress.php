<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderProgress extends Model
{
    protected $table = 'order_progress';

    protected $fillable = [
        'order_id',
        'updated_by',
        'stage',
        'completed_quantity',
        'remaining_quantity',
        'notes',
        'images',
    ];

    protected $casts = [
        'images' => 'array',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}