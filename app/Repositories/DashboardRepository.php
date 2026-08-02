<?php

namespace App\Repositories;

use App\Models\Cart;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class DashboardRepository
{
    public function getTotalUsers(): int
    {
        return User::count();
    }

    public function getActiveSellers(): int
    {
        return User::where('role', 'seller')
            ->whereNotNull('seller_approved_at')
            ->where('is_active', true)
            ->count();
    }

    public function getPendingSellers(): int
    {
        return User::where('role', 'seller')
            ->whereNull('seller_approved_at')
            ->where('is_active', true)
            ->count();
    }

    public function getTotalProducts(): int
    {
        return Product::count();
    }

    public function getTotalOrders(): int
    {
        return Order::count();
    }

    public function getTotalRevenue(): float
    {
        return Order::where('status', '!=', 'cancelled')->sum('total');
    }

    public function getMonthlySales(int $months = 12): Collection
    {
        return Order::selectRaw("DATE_FORMAT(created_at, '%Y-%m') as month, SUM(total) as total_sales, COUNT(*) as order_count")
            ->where('status', '!=', 'cancelled')
            ->where('created_at', '>=', now()->subMonths($months))
            ->groupBy('month')
            ->orderBy('month', 'asc')
            ->get();
    }

    public function getOrderStatusCounts(): array
    {
        return Order::selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();
    }

    public function getRecentOrders(int $limit = 5): Collection
    {
        return Order::with(['user', 'items.product'])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    public function getTopSellingProducts(int $limit = 5): Collection
    {
        return Product::select('products.id', 'products.name', 'products.image', 'products.price')
            ->join('order_items', 'products.id', '=', 'order_items.product_id')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->where('orders.status', '!=', 'cancelled')
            ->selectRaw('SUM(order_items.quantity) as total_sold, SUM(order_items.total) as total_revenue')
            ->groupBy('products.id', 'products.name', 'products.image', 'products.price')
            ->orderBy('total_sold', 'desc')
            ->limit($limit)
            ->get();
    }

    public function getTopCustomers(int $limit = 5): Collection
    {
        return User::select('users.id', 'users.name', 'users.email', 'users.image')
            ->join('orders', 'users.id', '=', 'orders.user_id')
            ->where('orders.status', '!=', 'cancelled')
            ->selectRaw('COUNT(orders.id) as order_count, SUM(orders.total) as total_spent')
            ->groupBy('users.id', 'users.name', 'users.email', 'users.image')
            ->orderBy('total_spent', 'desc')
            ->limit($limit)
            ->get();
    }

    public function getSalesByCategory(): Collection
    {
        return Category::select('categories.id', 'categories.name')
            ->join('products', 'categories.id', '=', 'products.category_id')
            ->join('order_items', 'products.id', '=', 'order_items.product_id')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->where('orders.status', '!=', 'cancelled')
            ->selectRaw('SUM(order_items.total) as total_sales, COUNT(order_items.id) as items_sold')
            ->groupBy('categories.id', 'categories.name')
            ->orderBy('total_sales', 'desc')
            ->get();
    }

    public function getDailySales(int $days = 30): Collection
    {
        return Order::selectRaw("DATE(created_at) as date, SUM(total) as total_sales, COUNT(*) as order_count")
            ->where('status', '!=', 'cancelled')
            ->where('created_at', '>=', now()->subDays($days))
            ->groupBy('date')
            ->orderBy('date', 'asc')
            ->get();
    }

    public function getNewUsersThisMonth(): int
    {
        return User::where('created_at', '>=', now()->startOfMonth())->count();
    }

    public function getNewOrdersThisMonth(): int
    {
        return Order::where('created_at', '>=', now()->startOfMonth())->count();
    }

    public function getRevenueThisMonth(): float
    {
        return Order::where('status', '!=', 'cancelled')
            ->where('created_at', '>=', now()->startOfMonth())
            ->sum('total');
    }

    public function getLowStockProducts(int $threshold = 10): Collection
    {
        return Product::where('is_active', true)
            ->where('quantity', '<=', $threshold)
            ->orderBy('quantity', 'asc')
            ->get();
    }

    public function getOutOfStockProducts(): Collection
    {
        return Product::where('is_active', true)
            ->where('quantity', 0)
            ->get();
    }

    public function getCartCount(): int
    {
        return Cart::count();
    }

    public function getActiveCouponsCount(): int
    {
        return \App\Models\Coupon::where('is_active', true)
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>=', now());
            })
            ->count();
    }
}
