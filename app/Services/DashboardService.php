<?php

namespace App\Services;

use App\Repositories\DashboardRepository;

class DashboardService
{
    public function __construct(
        protected DashboardRepository $dashboardRepository
    ) {}

    public function getStats(): array
    {
        return [
            'total_users' => $this->dashboardRepository->getTotalUsers(),
            'active_sellers' => $this->dashboardRepository->getActiveSellers(),
            'pending_sellers' => $this->dashboardRepository->getPendingSellers(),
            'total_products' => $this->dashboardRepository->getTotalProducts(),
            'total_orders' => $this->dashboardRepository->getTotalOrders(),
            'total_revenue' => $this->dashboardRepository->getTotalRevenue(),
            'order_status_counts' => $this->dashboardRepository->getOrderStatusCounts(),
            'recent_orders' => $this->dashboardRepository->getRecentOrders(),
            'top_selling_products' => $this->dashboardRepository->getTopSellingProducts(),
            'top_customers' => $this->dashboardRepository->getTopCustomers(),
            'sales_by_category' => $this->dashboardRepository->getSalesByCategory(),
            'low_stock_products' => $this->dashboardRepository->getLowStockProducts(),
            'active_coupons' => $this->dashboardRepository->getActiveCouponsCount(),
        ];
    }

    public function getMonthlySales(): \Illuminate\Database\Eloquent\Collection
    {
        return $this->dashboardRepository->getMonthlySales();
    }
}
