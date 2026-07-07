<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'avatar',
        'role',
        'status',
        'address',
        'city',
        'state',
        'zip_code',
        'country',
        'bio',
        'email_verified_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    // Relationships
    public function customer()
    {
        return $this->hasOne(Customer::class);
    }

    public function washerman()
    {
        return $this->hasOne(Washerman::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class, 'customer_id');
    }

    public function assignedOrders()
    {
        return $this->hasMany(Order::class, 'washerman_id');
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }

    public function unreadNotifications()
    {
        return $this->notifications()->where('is_read', false);
    }

    public function addresses()
    {
        return $this->hasMany(Address::class);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class, 'customer_id');
    }

    public function activityLogs()
    {
        return $this->hasMany(ActivityLog::class);
    }

    // Scopes
    public function scopeAdmins($query)
    {
        return $query->where('role', 'admin');
    }

    public function scopeCustomers($query)
    {
        return $query->where('role', 'customer');
    }

    public function scopeWashermen($query)
    {
        return $query->where('role', 'washerman');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    // Helpers
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isCustomer(): bool
    {
        return $this->role === 'customer';
    }

    public function isWasherman(): bool
    {
        return $this->role === 'washerman';
    }

    public function isApproved(): bool
    {
        if ($this->isWasherman()) {
            return $this->washerman && $this->washerman->approval_status === 'approved';
        }
        return $this->status === 'active';
    }

    public function getAvatarUrlAttribute(): string
    {
        return $this->avatar 
            ? asset('storage/' . $this->avatar) 
            : 'https://ui-avatars.com/api/?name=' . urlencode($this->name) . '&background=6366f1&color=fff';
    }
}