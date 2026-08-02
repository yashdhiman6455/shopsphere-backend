<?php

namespace App\Jobs;

use App\Models\Order;
use App\Models\User;
use App\Notifications\NewOrderNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\SerializesModels;

class NotifySellerNewOrder implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public Order $order
    ) {}

    public function handle(): void
    {
        $productIds = $this->order->items->pluck('product_id');

        $sellers = User::where('role', 'seller')
            ->whereNotNull('seller_approved_at')
            ->where('is_active', true)
            ->whereHas('products', fn ($q) => $q->whereIn('id', $productIds))
            ->get();

        foreach ($sellers as $seller) {
            $seller->notify(new NewOrderNotification($this->order));
        }
    }
}
