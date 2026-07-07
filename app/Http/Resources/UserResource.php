<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'avatar' => $this->avatar_url,
            'role' => $this->role,
            'status' => $this->status,
            'address' => $this->address,
            'city' => $this->city,
            'state' => $this->state,
            'zip_code' => $this->zip_code,
            'country' => $this->country,
            'bio' => $this->bio,
            'created_at' => $this->created_at,
            'customer' => CustomerResource::make($this->whenLoaded('customer')),
            'washerman' => WashermanResource::make($this->whenLoaded('washerman')),
        ];
    }
}