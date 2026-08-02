<?php

namespace App\Jobs;

use App\Models\Product;
use App\Models\User;
use App\Notifications\LowStockNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendLowStockAlerts implements ShouldQueue
{
    use Queueable;

    public function handle(): void
    {
        $lowStock = Product::where('is_active', true)
            ->where('quantity', '<=', 10)
            ->whereNotNull('seller_id')
            ->with('seller')
            ->orderBy('quantity', 'asc')
            ->get();

        $grouped = $lowStock->groupBy('seller_id');

        foreach ($grouped as $sellerId => $products) {
            $seller = User::find($sellerId);

            if ($seller) {
                $seller->notify(new LowStockNotification($products));
            }
        }
    }
}
