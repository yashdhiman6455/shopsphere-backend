<?php

namespace App\Jobs;

use App\Models\Order;
use App\Repositories\ProductRepository;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\SerializesModels;

class ReduceProductStock implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public Order $order
    ) {}

    public function handle(ProductRepository $productRepository): void
    {
        foreach ($this->order->items as $item) {
            $product = $productRepository->findById($item->product_id);

            if ($product) {
                $productRepository->update($product->id, [
                    'quantity' => max(0, $product->quantity - $item->quantity),
                ]);
            }
        }
    }
}
