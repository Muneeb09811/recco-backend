<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class OrderProgressNotification extends Notification
{
    use Queueable;

    protected Order $order;
    protected string $stage;

    public function __construct(Order $order, string $stage)
    {
        $this->order = $order;
        $this->stage = $stage;
    }

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toDatabase($notifiable): array
    {
        $stageLabels = [
            'washing' => 'Washing Started',
            'cleaning' => 'Cleaning In Progress',
            'ironing' => 'Ironing In Progress',
            'packing' => 'Packing',
            'completed' => 'Completed',
            'delivered' => 'Delivered',
        ];

        return [
            'order_id' => $this->order->id,
            'order_number' => $this->order->order_number,
            'stage' => $this->stage,
            'title' => "Order Update: " . ($stageLabels[$this->stage] ?? $this->stage),
            'message' => "Your order #{$this->order->order_number} - {$stageLabels[$this->stage]}",
            'link' => "/customer/orders/{$this->order->id}",
        ];
    }
}