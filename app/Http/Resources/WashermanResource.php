<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WashermanResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'shop_name' => $this->shop_name,
            'cnic' => $this->cnic,
            'experience' => $this->experience,
            'specialization' => $this->specialization,
            'rating' => $this->rating,
            'total_reviews' => $this->total_reviews,
            'total_orders_completed' => $this->total_orders_completed,
            'total_orders_pending' => $this->total_orders_pending,
            'total_orders_active' => $this->total_orders_active,
            'average_delivery_time' => $this->average_delivery_time,
            'approval_status' => $this->approval_status,
            'rejection_reason' => $this->rejection_reason,
            'approved_at' => $this->approved_at,
            'is_available' => $this->is_available,
            'service_charge' => $this->service_charge,
            'service_area' => $this->service_area,
        ];
    }
}