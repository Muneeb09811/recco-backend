<?php

namespace App\Listeners;

use App\Events\OrderCreated;
use App\Models\User;
use App\Models\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;

class NotifyAdmin implements ShouldQueue
{
    public $queue = 'notifications';

    public function handle(OrderCreated $event): void
    {
        $order = $event->order;
        $admins = User::admins()->get();

        foreach ($admins as $admin) {
            Notification::create([
                'user_id' => $admin->id,
                'type' => 'new_order',
                'title' => 'New Order Placed',
                'message' => "New order #{$order->order_number} by {$order->customer->name}",
                'link' => "/admin/orders/{$order->id}",
            ]);
        }
    }
}