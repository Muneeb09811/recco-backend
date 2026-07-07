<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContactMessage extends Model
{
    protected $fillable = [
        'name',
        'email',
        'phone',
        'subject',
        'message',
        'status',
        'reply',
        'replied_by',
        'replied_at',
    ];

    protected $casts = [
        'replied_at' => 'datetime',
    ];

    public function repliedBy()
    {
        return $this->belongsTo(User::class, 'replied_by');
    }

    public function scopeNew($query)
    {
        return $query->where('status', 'new');
    }
}