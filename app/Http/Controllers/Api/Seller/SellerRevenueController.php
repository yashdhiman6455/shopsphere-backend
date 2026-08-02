<?php

namespace App\Http\Controllers\Api\Seller;

use App\Http\Controllers\Controller;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SellerRevenueController extends Controller
{
    public function __construct(private OrderService $orderService) {}

    public function index(Request $request): JsonResponse
    {
        $days = (int) $request->get('days', 30);
        $seller = $request->user();

        return response()->json([
            'success' => true,
            'data' => [
                'total_revenue' => $this->orderService->getSellerRevenue($seller->id),
                'daily_sales' => $this->orderService->getSellerSalesByDate($seller->id, $days),
                'top_products' => $this->orderService->getSellerTopProducts($seller->id, 10),
            ],
        ]);
    }
}
