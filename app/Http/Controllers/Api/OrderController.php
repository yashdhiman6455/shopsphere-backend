<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Services\OrderService;
use App\Services\StripeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class OrderController extends Controller
{
    public function __construct(
        private OrderService $orderService
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $perPage = $request->query('per_page', 15);
        $orders = $this->orderService->getAllOrders($perPage);

        return OrderResource::collection($orders);
    }

    public function show(Request $request, $id): JsonResponse
    {
        $order = $this->orderService->getOrderById($id);

        if ($order->user_id !== $request->user()->id && ! $request->user()->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. You can only view your own orders.',
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'order' => new OrderResource($order),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'shipping_name' => ['required', 'string', 'max:255'],
            'shipping_email' => ['required', 'email', 'max:255'],
            'shipping_phone' => ['nullable', 'string', 'max:20'],
            'shipping_address' => ['required', 'string'],
            'shipping_city' => ['required', 'string', 'max:255'],
            'shipping_state' => ['nullable', 'string', 'max:255'],
            'shipping_zip_code' => ['nullable', 'string', 'max:20'],
            'payment_method' => ['required', 'in:cod,card,bank_transfer'],
            'coupon_code' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
        ]);

        $order = $this->orderService->createOrder(
            $request->user(),
            $validated
        );

        return response()->json([
            'success' => true,
            'message' => 'Order placed successfully.',
            'data' => [
                'order' => new OrderResource($order),
            ],
        ], 201);
    }

    public function update(Request $request, $id): JsonResponse
    {
        $request->validate([
            'status' => ['sometimes', 'in:pending,processing,shipped,delivered,cancelled'],
            'payment_status' => ['sometimes', 'in:pending,paid,failed,refunded,cancelled'],
        ]);

        if ($request->has('status')) {
            $order = $this->orderService->updateStatus($id, $request->status);
        } else {
            $order = $this->orderService->getOrderById($id);
        }

        return response()->json([
            'success' => true,
            'message' => 'Order updated successfully.',
            'data' => [
                'order' => new OrderResource($order),
            ],
        ]);
    }

    public function updateStatus(Request $request, $id): JsonResponse
    {
        $request->validate([
            'status' => ['required', 'in:pending,processing,shipped,delivered,cancelled'],
        ]);

        $order = $this->orderService->updateStatus($id, $request->status);

        return response()->json([
            'success' => true,
            'message' => 'Order status updated successfully.',
            'data' => [
                'order' => new OrderResource($order),
            ],
        ]);
    }

    public function userOrders(Request $request): AnonymousResourceCollection
    {
        $perPage = $request->query('per_page', 15);
        $orders = $this->orderService->getOrdersByUser($request->user()->id, $perPage);

        return OrderResource::collection($orders);
    }

    public function cancel(Request $request, int $id): JsonResponse
    {
        $order = Order::where('user_id', $request->user()->id)->findOrFail($id);

        if (!in_array($order->status, ['pending', 'processing'])) {
            return response()->json([
                'success' => false,
                'message' => 'This order cannot be cancelled.',
            ], 422);
        }

        $refundMessage = '';

        if ($order->payment_status === 'paid' && $order->stripe_payment_intent) {
            $stripeService = app(StripeService::class);
            $refunded = $stripeService->refundOrder($order);

            if ($refunded) {
                $refundMessage = ' A refund has been initiated.';
            } else {
                $refundMessage = ' Refund failed. Please contact support.';
            }
        } else {
            $order->update([
                'status' => 'cancelled',
                'payment_status' => $order->payment_status === 'pending' ? 'cancelled' : $order->payment_status,
            ]);

            foreach ($order->items as $item) {
                if ($item->product) {
                    $item->product->increment('quantity', $item->quantity);
                }
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Order cancelled successfully.' . $refundMessage,
            'data' => [
                'order' => new OrderResource($order->fresh()),
            ],
        ]);
    }
}
