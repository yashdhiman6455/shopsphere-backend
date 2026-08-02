<?php

namespace App\Http\Controllers\Api\Seller;

use App\Http\Controllers\Controller;
use App\Http\Resources\SellerOrderResource;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class SellerOrderController extends Controller
{
    public function __construct(private OrderService $orderService) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $perPage = (int) $request->get('per_page', 15);

        return SellerOrderResource::collection(
            $this->orderService->getOrdersForSeller($request->user()->id, $request->get('status'), $perPage)
        );
    }

    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'status' => ['required', 'string', 'in:pending,processing,packed,shipped,delivered,cancelled'],
        ]);

        try {
            $order = $this->orderService->updateSellerOrderStatus($request->user()->id, $id, $request->get('status'));
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Order status updated.',
            'data' => [
                'order' => new SellerOrderResource($order->load('items.product')),
            ],
        ]);
    }
}
