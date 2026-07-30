<?php

namespace App\Repositories;

use App\Models\Cart;
use Illuminate\Database\Eloquent\Collection;

class CartRepository extends BaseRepository
{
    protected function model(): string
    {
        return Cart::class;
    }

    public function getCartByUser(int $userId): Collection
    {
        return $this->getBuilder()
            ->where('user_id', $userId)
            ->with('product')
            ->get();
    }

    public function getCartTotal(int $userId): float
    {
        return $this->getBuilder()
            ->where('user_id', $userId)
            ->join('products', 'carts.product_id', '=', 'products.id')
            ->selectRaw("
                COALESCE(SUM(
                    CASE
                        WHEN products.sale_price IS NOT NULL THEN products.sale_price * carts.quantity
                        ELSE products.price * carts.quantity
                    END
                ), 0) as total
            ")
            ->value('total') ?? 0.00;
    }

    public function clearCart(int $userId): bool
    {
        return $this->getBuilder()
            ->where('user_id', $userId)
            ->delete() > 0;
    }

    public function getCartItemsCount(int $userId): int
    {
        return $this->getBuilder()
            ->where('user_id', $userId)
            ->sum('quantity');
    }

    public function findCartItem(int $userId, int $productId): ?Cart
    {
        return $this->getBuilder()
            ->where('user_id', $userId)
            ->where('product_id', $productId)
            ->first();
    }

    public function addItem(int $userId, int $productId, int $quantity = 1): Cart
    {
        $existingItem = $this->findCartItem($userId, $productId);

        if ($existingItem) {
            $existingItem->increment('quantity', $quantity);

            return $existingItem->fresh();
        }

        return $this->create([
            'user_id' => $userId,
            'product_id' => $productId,
            'quantity' => $quantity,
        ]);
    }

    public function updateQuantity(int $userId, int $productId, int $quantity): Cart
    {
        $cartItem = $this->findCartItem($userId, $productId);

        if (!$cartItem) {
            return $this->create([
                'user_id' => $userId,
                'product_id' => $productId,
                'quantity' => $quantity,
            ]);
        }

        $cartItem->update(['quantity' => $quantity]);

        return $cartItem->fresh();
    }

    public function removeItem(int $userId, int $productId): bool
    {
        return $this->getBuilder()
            ->where('user_id', $userId)
            ->where('product_id', $productId)
            ->delete() > 0;
    }

    public function removeCartItem(int $cartItemId): bool
    {
        $cartItem = $this->findByIdOrFail($cartItemId);

        return $cartItem->delete();
    }

    public function mergeGuestCart(int $userId, array $guestItems): Collection
    {
        foreach ($guestItems as $item) {
            $this->addItem($userId, $item['product_id'], $item['quantity'] ?? 1);
        }

        return $this->getCartByUser($userId);
    }
}
