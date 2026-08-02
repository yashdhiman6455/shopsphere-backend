<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Services\OrderService;
use App\Services\RefundService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RefundController extends Controller
{
    public function __construct(
        private OrderService $orderService,
        private RefundService $refundService
    ) {}

    public function store(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $order = $this->orderService->getOrderById($id);
            $this->refundService->refundOrder($order, $request->get('reason'));
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Order refunded successfully.',
            'data' => [
                'order' => new OrderResource($order->fresh(['items.product', 'user', 'coupon'])),
            ],
        ]);
    }
}
