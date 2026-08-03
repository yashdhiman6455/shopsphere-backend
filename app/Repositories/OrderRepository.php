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
            ->with(['items.product' => fn ($q) => $q->withAvg('reviews', 'rating'), 'coupon'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function getOrdersByUserPaginated(int $userId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->getBuilder()
            ->where('user_id', $userId)
            ->with(['items.product' => fn ($q) => $q->withAvg('reviews', 'rating'), 'coupon'])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    public function getOrderStats(): array
    {
        $row = (clone $this->getBuilder())
            ->selectRaw("
                COUNT(*) as total_orders,
                COALESCE(SUM(total), 0) as total_revenue,
                COALESCE(SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END), 0) as pending_orders,
                COALESCE(SUM(CASE WHEN status = 'processing' THEN 1 ELSE 0 END), 0) as processing_orders,
                COALESCE(SUM(CASE WHEN status = 'shipped' THEN 1 ELSE 0 END), 0) as shipped_orders,
                COALESCE(SUM(CASE WHEN status = 'delivered' THEN 1 ELSE 0 END), 0) as delivered_orders,
                COALESCE(SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END), 0) as cancelled_orders
            ")
            ->first();

        $totalOrders = (int) $row->total_orders;
        $totalRevenue = (float) $row->total_revenue;

        return [
            'total_orders' => $totalOrders,
            'total_revenue' => $totalRevenue,
            'pending_orders' => (int) $row->pending_orders,
            'processing_orders' => (int) $row->processing_orders,
            'shipped_orders' => (int) $row->shipped_orders,
            'delivered_orders' => (int) $row->delivered_orders,
            'cancelled_orders' => (int) $row->cancelled_orders,
            'average_order_value' => $totalOrders > 0 ? round($totalRevenue / $totalOrders, 2) : 0,
        ];
    }

    public function getRecentOrders(int $limit = 10): Collection
    {
        return $this->getBuilder()
            ->with(['user', 'items.product' => fn ($q) => $q->withAvg('reviews', 'rating')])
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
            ->with(['user', 'items.product' => fn ($q) => $q->withAvg('reviews', 'rating'), 'coupon'])
            ->first();
    }

    public function getOrdersByStatus(string $status): Collection
    {
        return $this->getBuilder()
            ->where('status', $status)
            ->with(['user', 'items.product' => fn ($q) => $q->withAvg('reviews', 'rating')])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function getOrdersByDateRange(string $startDate, string $endDate): Collection
    {
        return $this->getBuilder()
            ->whereBetween('created_at', [$startDate, $endDate])
            ->with(['user', 'items.product' => fn ($q) => $q->withAvg('reviews', 'rating')])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function getOrdersWithPendingPayment(): Collection
    {
        return $this->getBuilder()
            ->where('payment_status', 'pending')
            ->with(['user', 'items.product' => fn ($q) => $q->withAvg('reviews', 'rating')])
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
