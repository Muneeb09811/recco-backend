<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'transaction_id' => $this->transaction_id,
            'amount' => $this->amount,
            'payment_method' => $this->payment_method,
            'status' => $this->status,
            'payment_details' => $this->payment_details,
            'paid_at' => $this->paid_at,
        ];
    }
}