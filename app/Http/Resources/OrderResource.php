<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_number' => $this->order_number,
            'formatted_order_number' => $this->formatted_order_number,
            'customer' => UserResource::make($this->whenLoaded('customer')),
            'washerman' => UserResource::make($this->whenLoaded('washerman')),
            'service' => ServiceResource::make($this->whenLoaded('service')),
            'pickup_address' => $this->pickup_address,
            'pickup_phone' => $this->pickup_phone,
            'pickup_date' => $this->pickup_date?->format('Y-m-d'),
            'pickup_time' => $this->pickup_time,
            'expected_delivery_date' => $this->expected_delivery_date?->format('Y-m-d'),
            'actual_delivery_date' => $this->actual_delivery_date?->format('Y-m-d'),
            'items' => [
                'shirts' => $this->shirts_quantity,
                'tshirts' => $this->tshirts_quantity,
                'pants' => $this->pants_quantity,
                'jeans' => $this->jeans_quantity,
                'coats' => $this->coats_quantity,
                'bedsheets' => $this->bedsheets_quantity,
                'blankets' => $this->blankets_quantity,
                'curtains' => $this->curtains_quantity,
                'other' => $this->other_items_quantity,
            ],
            'total_quantity' => $this->total_quantity,
            'completed_quantity' => $this->completed_quantity,
            'remaining_quantity' => $this->remaining_quantity,
            'delivered_quantity' => $this->delivered_quantity,
            'status' => $this->status,
            'status_color' => $this->status_color,
            'payment_status' => $this->payment_status,
            'payment_method' => $this->payment_method,
            'total_amount' => $this->total_amount,
            'discount' => $this->discount,
            'tax' => $this->tax,
            'final_amount' => $this->final_amount,
            'special_instructions' => $this->special_instructions,
            'order_notes' => $this->order_notes,
            'images' => $this->images,
            'progress_percentage' => $this->progress_percentage,
            'accepted_at' => $this->accepted_at,
            'started_at' => $this->started_at,
            'completed_at' => $this->completed_at,
            'delivered_at' => $this->delivered_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'progress' => OrderProgressResource::collection($this->whenLoaded('progress')),
            'payment' => PaymentResource::make($this->whenLoaded('payment')),
        ];
    }
}