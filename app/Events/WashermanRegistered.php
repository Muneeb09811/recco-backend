<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class WashermanRegistered
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public User $washerman;

    public function __construct(User $washerman)
    {
        $this->washerman = $washerman;
    }
}