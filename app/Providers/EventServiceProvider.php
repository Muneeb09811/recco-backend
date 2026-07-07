<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;
use App\Events\OrderCreated;
use App\Events\OrderStatusUpdated;
use App\Listeners\SendOrderConfirmation;
use App\Listeners\NotifyAdmin;
use App\Listeners\UpdateOrderStatistics;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        OrderCreated::class => [
            SendOrderConfirmation::class,
            NotifyAdmin::class,
        ],
        OrderStatusUpdated::class => [
            UpdateOrderStatistics::class,
        ],
    ];

    public function boot(): void
    {
        //
    }

    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}