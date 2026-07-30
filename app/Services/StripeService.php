<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Stripe\Checkout\Session as StripeCheckoutSession;
use Stripe\Exception\ApiErrorException;
use Stripe\Stripe;
use Stripe\Webhook;

class StripeService
{
    public function __construct()
    {
        Stripe::setApiKey(config('services.stripe.secret'));
    }

    /**
     * Create a Stripe Checkout Session for an order.
     *
     * @return array{session_id: string, url: string}
     *
     * @throws ApiErrorException
     */
    public function createCheckoutSession(Order $order): array
    {
        $lineItems = [];

        foreach ($order->items as $item) {
            $lineItems[] = [
                'price_data' => [
                    'currency' => 'usd',
                    'unit_amount' => (int) ($item->price * 100),
                    'product_data' => [
                        'name' => $item->product->name,
                    ],
                ],
                'quantity' => $item->quantity,
            ];
        }

        $params = [
            'payment_method_types' => ['card'],
            'mode' => 'payment',
            'customer_email' => $order->shipping_email,
            'line_items' => $lineItems,
            'success_url' => config('services.stripe.frontend_url') . '/checkout/success?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => config('services.stripe.frontend_url') . '/checkout/cancel',
            'metadata' => [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
            ],
        ];

        if ($order->discount > 0) {
            $params['discounts'] = [
                [
                    'coupon_data' => [
                        'amount_off' => (int) ($order->discount * 100),
                        'currency' => 'usd',
                        'duration' => 'once',
                        'name' => 'Order Discount',
                    ],
                ],
            ];
        }

        $session = StripeCheckoutSession::create($params);

        $order->update([
            'stripe_session_id' => $session->id,
        ]);

        return [
            'session_id' => $session->id,
            'url' => $session->url,
        ];
    }

    /**
     * Retrieve a Stripe Checkout Session by ID.
     */
    public function getSession(string $sessionId): StripeCheckoutSession
    {
        return StripeCheckoutSession::retrieve([
            'id' => $sessionId,
            'expand' => ['payment_intent'],
        ]);
    }

    /**
     * Handle a Stripe webhook event.
     */
    public function handleWebhook(string $payload, string $sigHeader): void
    {
        $event = Webhook::constructEvent(
            $payload,
            $sigHeader,
            config('services.stripe.webhook_secret')
        );

        match ($event->type) {
            'checkout.session.completed' => $this->handleCheckoutCompleted($event->data->object),
            'payment_intent.payment_failed' => $this->handlePaymentFailed($event->data->object),
            'charge.refunded' => $this->handleChargeRefunded($event->data->object),
            default => null,
        };
    }

    private function handleCheckoutCompleted($session): void
    {
        $orderId = $session->metadata->order_id ?? null;

        if ($orderId) {
            $order = Order::find($orderId);
            if ($order && $order->payment_status !== 'paid') {
                $paymentIntent = is_string($session->payment_intent)
                    ? $session->payment_intent
                    : $session->payment_intent->id ?? null;

                $order->update([
                    'payment_status' => 'paid',
                    'status' => 'processing',
                    'stripe_payment_intent' => $paymentIntent,
                ]);
            }
        }
    }

    private function handlePaymentFailed($paymentIntent): void
    {
        $order = Order::where('stripe_payment_intent', $paymentIntent->id)->first();

        if ($order) {
            $order->update([
                'payment_status' => 'failed',
                'status' => 'cancelled',
            ]);

            foreach ($order->items as $item) {
                if ($item->product) {
                    $item->product->increment('quantity', $item->quantity);
                }
            }
        }
    }

    private function handleChargeRefunded($charge): void
    {
        $paymentIntentId = $charge->payment_intent ?? null;

        if ($paymentIntentId) {
            $order = Order::where('stripe_payment_intent', $paymentIntentId)->first();

            if ($order) {
                $order->update([
                    'payment_status' => 'refunded',
                    'status' => 'cancelled',
                ]);

                foreach ($order->items as $item) {
                    if ($item->product) {
                        $item->product->increment('quantity', $item->quantity);
                    }
                }
            }
        }
    }

    public function refundOrder(Order $order): bool
    {
        if (!$order->stripe_payment_intent) {
            return false;
        }

        try {
            \Stripe\Refund::create([
                'payment_intent' => $order->stripe_payment_intent,
            ]);

            $order->update([
                'payment_status' => 'refunded',
                'status' => 'cancelled',
            ]);

            foreach ($order->items as $item) {
                if ($item->product) {
                    $item->product->increment('quantity', $item->quantity);
                }
            }

            return true;
        } catch (\Exception $e) {
            Log::error('Stripe refund failed', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
