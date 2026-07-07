<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderProgressResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'stage' => $this->stage,
            'completed_quantity' => $this->completed_quantity,
            'remaining_quantity' => $this->remaining_quantity,
            'notes' => $this->notes,
            'images' => $this->images,
            'updated_by' => UserResource::make($this->whenLoaded('updatedBy')),
            'created_at' => $this->created_at,
        ];
    }
}