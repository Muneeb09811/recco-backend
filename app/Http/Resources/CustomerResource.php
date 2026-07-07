<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'company_name' => $this->company_name,
            'total_orders' => $this->total_orders,
            'active_orders' => $this->active_orders,
            'completed_orders' => $this->completed_orders,
            'total_spent' => $this->total_spent,
            'loyalty_points' => $this->loyalty_points,
            'is_vip' => $this->is_vip,
        ];
    }
}