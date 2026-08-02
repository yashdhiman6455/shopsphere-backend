<?php

namespace App\Listeners;

use App\Events\OrderPlaced;
use App\Jobs\NotifySellerNewOrder;
use App\Jobs\ReduceProductStock;

class HandleOrderPlaced
{
    public function handle(OrderPlaced $event): void
    {
        $order = $event->order->load('items');

        ReduceProductStock::dispatch($order);
        NotifySellerNewOrder::dispatch($order);
    }
}
