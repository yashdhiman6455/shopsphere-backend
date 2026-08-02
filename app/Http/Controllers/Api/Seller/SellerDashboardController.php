<?php

namespace App\Http\Controllers\Api\Seller;

use App\Http\Controllers\Controller;
use App\Http\Resources\SellerOrderResource;
use App\Services\SellerDashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SellerDashboardController extends Controller
{
    public function __construct(private SellerDashboardService $sellerDashboardService) {}

    public function index(Request $request): JsonResponse
    {
        $stats = $this->sellerDashboardService->getStats($request->user());

        return response()->json([
            'success' => true,
            'data' => [
                'stats' => [
                    'total_products' => $stats['total_products'],
                    'active_products' => $stats['active_products'],
                    'total_orders' => array_sum($stats['total_orders']),
                    'order_status_counts' => $stats['total_orders'],
                    'total_revenue' => $stats['total_revenue'],
                    'low_stock_products' => $stats['low_stock_products'],
                ],
                'recent_orders' => SellerOrderResource::collection($stats['recent_orders']),
                'low_stock' => $stats['low_stock'],
                'top_products' => $stats['top_products'],
            ],
        ]);
    }
}
