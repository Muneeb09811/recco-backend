<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ServiceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'icon' => $this->icon,
            'image' => $this->image ? asset('storage/' . $this->image) : null,
            'base_price' => $this->base_price,
            'price_unit' => $this->price_unit,
            'is_active' => $this->is_active,
            'sort_order' => $this->sort_order,
        ];
    }
}