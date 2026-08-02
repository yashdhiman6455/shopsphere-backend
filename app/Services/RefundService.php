<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Refund;
use App\Repositories\OrderRepository;
use Illuminate\Support\Facades\DB;

class RefundService
{
    public function __construct(
        protected OrderRepository $orderRepository,
        protected StripeService $stripeService
    ) {}

    public function refundOrder(Order $order, ?string $reason = null): Refund
    {
        return DB::transaction(function () use ($order, $reason) {
            if ($order->payment_status === 'refunded' || $order->refunded_at) {
                throw new \Exception('This order has already been refunded.');
            }

            $stripeRefundId = null;

            if ($order->payment_method === 'card' && $order->stripe_payment_intent) {
                if (!$this->stripeService->refundOrder($order)) {
                    throw new \Exception('Refund could not be processed by the payment provider.');
                }
                $stripeRefundId = $order->stripe_payment_intent . '-refund';
            }

            $order->update([
                'payment_status' => 'refunded',
                'status' => 'cancelled',
                'refunded_at' => now(),
            ]);

            foreach ($order->items as $item) {
                if ($item->product) {
                    $item->product->increment('quantity', $item->quantity);
                }
            }

            return Refund::create([
                'order_id' => $order->id,
                'user_id' => $order->user_id,
                'amount' => $order->total,
                'reason' => $reason,
                'status' => 'processed',
                'stripe_refund_id' => $stripeRefundId,
            ]);
        });
    }
}
