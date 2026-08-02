<?php

namespace App\Services;

use App\Models\User;
use App\Repositories\OrderRepository;
use App\Repositories\ProductRepository;

class SellerDashboardService
{
    public function __construct(
        protected OrderRepository $orderRepository,
        protected ProductRepository $productRepository
    ) {}

    public function getStats(User $seller): array
    {
        $sellerId = $seller->id;

        return [
            'total_products' => $this->productRepository->countForSeller($sellerId),
            'active_products' => $this->productRepository->countActiveForSeller($sellerId),
            'total_orders' => $this->orderRepository->getOrdersForSellerByStatusCount($sellerId),
            'total_revenue' => $this->orderRepository->getSellerRevenue($sellerId),
            'low_stock_products' => $this->productRepository->getLowStockForSeller($sellerId)->count(),
            'recent_orders' => $this->orderRepository->getOrdersForSeller($sellerId, null, 5),
            'low_stock' => $this->productRepository->getLowStockForSeller($sellerId),
            'top_products' => $this->orderRepository->getSellerTopProducts($sellerId, 5),
        ];
    }
}
