<?php

namespace App\Repositories;

use App\Models\Order;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class OrderRepository extends BaseRepository
{
    protected function model(): string
    {
        return Order::class;
    }

    public function getOrdersByUser(int $userId): Collection
    {
        return $this->getBuilder()
            ->where('user_id', $userId)
            ->with(['items.product', 'coupon'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function getOrdersByUserPaginated(int $userId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->getBuilder()
            ->where('user_id', $userId)
            ->with(['items.product', 'coupon'])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    public function getOrderStats(): array
    {
        $totalOrders = $this->count();
        $totalRevenue = (clone $this->getBuilder())->sum('total');
        $pendingOrders = (clone $this->getBuilder())->where('status', 'pending')->count();
        $processingOrders = (clone $this->getBuilder())->where('status', 'processing')->count();
        $shippedOrders = (clone $this->getBuilder())->where('status', 'shipped')->count();
        $deliveredOrders = (clone $this->getBuilder())->where('status', 'delivered')->count();
        $cancelledOrders = (clone $this->getBuilder())->where('status', 'cancelled')->count();
        $averageOrderValue = $totalOrders > 0 ? round($totalRevenue / $totalOrders, 2) : 0;

        return [
            'total_orders' => $totalOrders,
            'total_revenue' => $totalRevenue,
            'pending_orders' => $pendingOrders,
            'processing_orders' => $processingOrders,
            'shipped_orders' => $shippedOrders,
            'delivered_orders' => $deliveredOrders,
            'cancelled_orders' => $cancelledOrders,
            'average_order_value' => $averageOrderValue,
        ];
    }

    public function getRecentOrders(int $limit = 10): Collection
    {
        return $this->getBuilder()
            ->with(['user', 'items.product'])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    public function updateStatus(int $orderId, string $status): Order
    {
        $order = $this->findByIdOrFail($orderId);
        $order->update(['status' => $status]);

        return $order->fresh();
    }

    public function findByOrderNumber(string $orderNumber): ?Order
    {
        return $this->getBuilder()
            ->where('order_number', $orderNumber)
            ->with(['user', 'items.product', 'coupon'])
            ->first();
    }

    public function getOrdersByStatus(string $status): Collection
    {
        return $this->getBuilder()
            ->where('status', $status)
            ->with(['user', 'items.product'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function getOrdersByDateRange(string $startDate, string $endDate): Collection
    {
        return $this->getBuilder()
            ->whereBetween('created_at', [$startDate, $endDate])
            ->with(['user', 'items.product'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function getOrdersWithPendingPayment(): Collection
    {
        return $this->getBuilder()
            ->where('payment_status', 'pending')
            ->with(['user', 'items.product'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function getMonthlySales(): Collection
    {
        return $this->getBuilder()
            ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as month, SUM(total) as total_sales, COUNT(*) as order_count")
            ->where('status', '!=', 'cancelled')
            ->groupBy('month')
            ->orderBy('month', 'desc')
            ->limit(12)
            ->get();
    }

    public function toggleActive(int $id): Order
    {
        $order = $this->findByIdOrFail($id);
        $order->update(['status' => $order->status === 'cancelled' ? 'pending' : 'cancelled']);

        return $order->fresh();
    }
}
