<?php

namespace App\Listeners;

use App\Events\OrderCreated;
use App\Models\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendOrderConfirmation implements ShouldQueue
{
    public $queue = 'notifications';

    public function __construct() {}

    public function handle(OrderCreated $event): void
    {
        $order = $event->order;
        
        // Already handled in controller, but this is for async processing
        Notification::create([
            'user_id' => $order->customer_id,
            'type' => 'order_created',
            'title' => 'Order Confirmed',
            'message' => "Your order #{$order->order_number} is confirmed and being processed.",
            'link' => "/customer/orders/{$order->id}",
        ]);
    }
}