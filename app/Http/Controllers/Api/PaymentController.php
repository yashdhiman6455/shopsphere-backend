<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\StripeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Stripe\Exception\ApiErrorException;
use Stripe\Exception\SignatureVerificationException;

class PaymentController extends Controller
{
    public function __construct(
        private StripeService $stripeService
    ) {}

    /**
     * Create a Stripe Checkout Session for an order.
     */
    public function createCheckoutSession(Request $request): JsonResponse
    {
        $request->validate([
            'order_id' => ['required', 'exists:orders,id'],
        ]);

        $order = $request->user()->orders()->findOrFail($request->order_id);

        if ($order->payment_method !== 'card') {
            return response()->json([
                'success' => false,
                'message' => 'This order is not set up for card payment.',
            ], 422);
        }

        try {
            $result = $this->stripeService->createCheckoutSession($order);

            return response()->json([
                'success' => true,
                'data' => [
                    'session_id' => $result['session_id'],
                    'url' => $result['url'],
                ],
            ]);
        } catch (ApiErrorException $e) {
            Log::error('Stripe checkout session creation failed', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create payment session. Please try again.',
            ], 500);
        }
    }

    /**
     * Get checkout session status.
     */
    public function sessionStatus(Request $request): JsonResponse
    {
        $request->validate([
            'session_id' => ['required', 'string'],
        ]);

        try {
            $session = $this->stripeService->getSession($request->session_id);

            $orderId = $session->metadata->order_id ?? null;

            if ($session->payment_status === 'paid' && $orderId) {
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

            return response()->json([
                'success' => true,
                'data' => [
                    'status' => $session->payment_status,
                    'order_id' => $orderId,
                    'customer_email' => $session->customer_email,
                ],
            ]);
        } catch (ApiErrorException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid session.',
            ], 404);
        }
    }

    /**
     * Stripe webhook handler.
     */
    public function webhook(Request $request): JsonResponse
    {
        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');

        try {
            $this->stripeService->handleWebhook($payload, $sigHeader);
        } catch (SignatureVerificationException $e) {
            Log::error('Stripe webhook signature verification failed', [
                'error' => $e->getMessage(),
            ]);

            return response()->json(['error' => 'Invalid signature'], 400);
        } catch (\Exception $e) {
            Log::error('Stripe webhook error', [
                'error' => $e->getMessage(),
            ]);

            return response()->json(['error' => 'Webhook error'], 500);
        }

        return response()->json(['received' => true]);
    }
}
