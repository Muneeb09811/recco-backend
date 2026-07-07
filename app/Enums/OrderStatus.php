<?php

namespace App\Enums;

enum OrderStatus: string
{
    case PENDING = 'pending';
    case ACCEPTED = 'accepted';
    case PICKED_UP = 'picked_up';
    case WASHING = 'washing';
    case CLEANING = 'cleaning';
    case IRONING = 'ironing';
    case PACKING = 'packing';
    case COMPLETED = 'completed';
    case DELIVERED = 'delivered';
    case CANCELLED = 'cancelled';
    case REJECTED = 'rejected';

    public function label(): string
    {
        return match($this) {
            self::PENDING => 'Pending',
            self::ACCEPTED => 'Accepted',
            self::PICKED_UP => 'Picked Up',
            self::WASHING => 'Washing',
            self::CLEANING => 'Cleaning',
            self::IRONING => 'Ironing',
            self::PACKING => 'Packing',
            self::COMPLETED => 'Completed',
            self::DELIVERED => 'Delivered',
            self::CANCELLED => 'Cancelled',
            self::REJECTED => 'Rejected',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::PENDING => 'yellow',
            self::ACCEPTED => 'blue',
            self::PICKED_UP => 'indigo',
            self::WASHING, self::CLEANING => 'cyan',
            self::IRONING => 'purple',
            self::PACKING => 'pink',
            self::COMPLETED => 'green',
            self::DELIVERED => 'emerald',
            self::CANCELLED, self::REJECTED => 'red',
        };
    }

    public function isActive(): bool
    {
        return in_array($this, [
            self::ACCEPTED, self::PICKED_UP, self::WASHING,
            self::CLEANING, self::IRONING, self::PACKING,
        ]);
    }
}