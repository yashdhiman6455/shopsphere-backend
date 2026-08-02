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

    public function getOrdersForSeller(int $sellerId, ?string $status = null, int $perPage = 15): LengthAwarePaginator
    {
        return $this->getBuilder()
            ->whereHas('items.product', fn ($q) => $q->where('seller_id', $sellerId))
            ->when($status, function (Builder $query) use ($status) {
                $query->where('status', $status);
            })
            ->with([
                'user',
                'items' => function ($q) use ($sellerId) {
                    $q->whereHas('product', fn ($p) => $p->where('seller_id', $sellerId))
                        ->with(['product' => fn ($p) => $p->where('seller_id', $sellerId)]);
                },
            ])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    public function getOrdersForSellerByStatusCount(int $sellerId): array
    {
        return $this->getBuilder()
            ->whereHas('items.product', fn ($q) => $q->where('seller_id', $sellerId))
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();
    }

    public function getSellerRevenue(int $sellerId): float
    {
        return (float) $this->getBuilder()
            ->where('status', '!=', 'cancelled')
            ->whereHas('items.product', fn ($q) => $q->where('seller_id', $sellerId))
            ->join('order_items', 'orders.id', '=', 'order_items.order_id')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->where('products.seller_id', $sellerId)
            ->sum('order_items.total');
    }

    public function getSellerSalesByDate(int $sellerId, int $days = 30): Collection
    {
        return $this->getBuilder()
            ->join('order_items', 'orders.id', '=', 'order_items.order_id')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->where('products.seller_id', $sellerId)
            ->where('orders.status', '!=', 'cancelled')
            ->where('orders.created_at', '>=', now()->subDays($days))
            ->selectRaw("DATE(orders.created_at) as date, SUM(order_items.total) as total_sales, COUNT(DISTINCT orders.id) as order_count")
            ->groupBy('date')
            ->orderBy('date', 'asc')
            ->get();
    }

    public function getSellerTopProducts(int $sellerId, int $limit = 5): Collection
    {
        return $this->getBuilder()
            ->join('order_items', 'orders.id', '=', 'order_items.order_id')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->where('products.seller_id', $sellerId)
            ->where('orders.status', '!=', 'cancelled')
            ->select('products.id', 'products.name', 'products.image')
            ->selectRaw('SUM(order_items.quantity) as total_sold, SUM(order_items.total) as total_revenue')
            ->groupBy('products.id', 'products.name', 'products.image')
            ->orderBy('total_sold', 'desc')
            ->limit($limit)
            ->get();
    }

    public function getSellerLowStockCount(int $sellerId, int $threshold = 10): int
    {
        return \App\Models\Product::where('seller_id', $sellerId)
            ->where('is_active', true)
            ->where('quantity', '<=', $threshold)
            ->count();
    }
}
