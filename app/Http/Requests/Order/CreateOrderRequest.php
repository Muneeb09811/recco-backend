<?php

namespace App\Http\Requests\Order;

use Illuminate\Foundation\Http\FormRequest;

class CreateOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'pickup_address' => 'required|string|min:10',
            'pickup_phone' => 'required|string|max:20',
            'pickup_date' => 'required|date|after_or_equal:today',
            'pickup_time' => 'nullable|date_format:H:i',
            'expected_delivery_date' => 'required|date|after:pickup_date',
            'service_id' => 'nullable|exists:services,id',
            'shirts_quantity' => 'integer|min:0',
            'tshirts_quantity' => 'integer|min:0',
            'pants_quantity' => 'integer|min:0',
            'jeans_quantity' => 'integer|min:0',
            'coats_quantity' => 'integer|min:0',
            'bedsheets_quantity' => 'integer|min:0',
            'blankets_quantity' => 'integer|min:0',
            'curtains_quantity' => 'integer|min:0',
            'other_items_quantity' => 'integer|min:0',
            'special_instructions' => 'nullable|string|max:1000',
            'order_notes' => 'nullable|string|max:1000',
            'payment_method' => 'in:cash,card,bank_transfer,online',
            'images' => 'nullable|array|max:5',
            'images.*' => 'image|max:2048',
        ];
    }
}