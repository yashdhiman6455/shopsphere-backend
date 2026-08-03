<?php

namespace App\Services;

use App\Models\Order;
use App\Models\User;
use App\Events\OrderPlaced;
use App\Mail\InvoiceMail;
use App\Mail\OrderConfirmationMail;
use App\Repositories\CartRepository;
use App\Repositories\CouponRepository;
use App\Repositories\OrderRepository;
use App\Repositories\ProductRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class OrderService
{
    public function __construct(
        protected OrderRepository $orderRepository,
        protected ProductRepository $productRepository,
        protected CartRepository $cartRepository,
        protected CouponRepository $couponRepository
    ) {}

    public function createOrder(User $user, array $data): Order
    {
        return DB::transaction(function () use ($user, $data) {
            $cartItems = $this->cartRepository->getCartByUser($user->id);

            if ($cartItems->isEmpty()) {
                throw new \Exception('Cart is empty.');
            }

            $subtotal = 0;
            $orderItems = [];

            foreach ($cartItems as $cartItem) {
                $product = $cartItem->product;

                if (!$product) {
                    throw new \Exception('A product in your cart is no longer available.');
                }

                if (!$product->is_active) {
                    throw new \Exception("Product \"{$product->name}\" is no longer available.");
                }

                if ($product->quantity < $cartItem->quantity) {
                    throw new \Exception("Insufficient stock for \"{$product->name}\".");
                }

                $effectivePrice = $product->getEffectivePrice();
                $itemTotal = round($effectivePrice * $cartItem->quantity, 2);
                $subtotal += $itemTotal;

                $orderItems[] = [
                    'product_id' => $product->id,
                    'quantity' => $cartItem->quantity,
                    'price' => $effectivePrice,
                    'total' => $itemTotal,
                ];
            }

            $discount = 0;
            $couponId = null;

            if (!empty($data['coupon_code'])) {
                $coupon = $this->couponRepository->findByCode($data['coupon_code']);

                if ($coupon && $coupon->isValid()) {
                    $discount = $coupon->calculateDiscount($subtotal);
                    $couponId = $coupon->id;
                    $this->couponRepository->incrementUsage($coupon->id);
                }
            }

            $shippingCost = $data['shipping_cost'] ?? 0;
            $tax = round($subtotal * 0.08, 2);
            $total = round($subtotal - $discount + $shippingCost + $tax, 2);

            $order = $this->orderRepository->create([
                'user_id' => $user->id,
                'order_number' => Order::generateOrderNumber(),
                'status' => 'pending',
                'subtotal' => $subtotal,
                'discount' => $discount,
                'shipping_cost' => $shippingCost,
                'tax' => $tax,
                'total' => $total,
                'coupon_id' => $couponId,
                'payment_method' => $data['payment_method'] ?? 'cod',
                'payment_status' => 'pending',
                'shipping_name' => $data['shipping_name'] ?? $user->name,
                'shipping_email' => $data['shipping_email'] ?? $user->email,
                'shipping_phone' => $data['shipping_phone'] ?? null,
                'shipping_address' => $data['shipping_address'],
                'shipping_city' => $data['shipping_city'],
                'shipping_state' => $data['shipping_state'] ?? null,
                'shipping_zip_code' => $data['shipping_zip_code'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);

            foreach ($orderItems as $item) {
                $order->items()->create($item);
            }

            $this->cartRepository->clearCart($user->id);

            $order->load('items.product');

            try {
                Mail::to($user->email)->queue(new OrderConfirmationMail($order));
                Mail::to($user->email)->queue(new InvoiceMail($order));
            } catch (\Exception $e) {
                \Log::error('Failed to send order emails: ' . $e->getMessage());
            }

            OrderPlaced::dispatch($order);

            return $order;
        });
    }

    public function getOrdersByUser(int $userId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->orderRepository->getOrdersByUserPaginated($userId, $perPage);
    }

    public function getOrderById(int $id): Order
    {
        return $this->orderRepository->findByIdOrFail($id)->load(['items.product', 'coupon', 'user']);
    }

    public function getAllOrders(int $perPage = 15): LengthAwarePaginator
    {
        return $this->orderRepository->paginate($perPage);
    }

    public function updateStatus(int $id, string $status): Order
    {
        $this->orderRepository->findByIdOrFail($id);

        return $this->orderRepository->updateStatus($id, $status);
    }

    public function getOrderStats(): array
    {
        return $this->orderRepository->getOrderStats();
    }

    public function getOrdersForSeller(int $sellerId, ?string $status = null, int $perPage = 15): LengthAwarePaginator
    {
        return $this->orderRepository->getOrdersForSeller($sellerId, $status, $perPage);
    }

    public function getSellerOrderStatusCounts(int $sellerId): array
    {
        return $this->orderRepository->getOrdersForSellerByStatusCount($sellerId);
    }

    public function updateSellerOrderStatus(int $sellerId, int $orderId, string $status): Order
    {
        $order = $this->orderRepository->findByIdOrFail($orderId);

        $hasSellerProduct = $order->items()
            ->whereHas('product', fn ($q) => $q->where('seller_id', $sellerId))
            ->exists();

        if (!$hasSellerProduct) {
            throw new \Exception('This order does not contain any of your products.');
        }

        return $this->orderRepository->updateStatus($orderId, $status);
    }

    public function getSellerRevenue(int $sellerId): float
    {
        return $this->orderRepository->getSellerRevenue($sellerId);
    }

    public function getSellerSalesByDate(int $sellerId, int $days = 30): \Illuminate\Database\Eloquent\Collection
    {
        return $this->orderRepository->getSellerSalesByDate($sellerId, $days);
    }

    public function getSellerTopProducts(int $sellerId, int $limit = 5): \Illuminate\Database\Eloquent\Collection
    {
        return $this->orderRepository->getSellerTopProducts($sellerId, $limit);
    }
}
