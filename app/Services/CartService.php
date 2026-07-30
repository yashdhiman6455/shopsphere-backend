<?php

namespace App\Services;

use App\Models\Cart;
use App\Repositories\CartRepository;
use App\Repositories\ProductRepository;

class CartService
{
    public function __construct(
        protected CartRepository $cartRepository,
        protected ProductRepository $productRepository
    ) {}

    public function getCart(int $userId)
    {
        return $this->cartRepository->getCartByUser($userId);
    }

    public function addToCart(int $userId, int $productId, int $quantity = 1): Cart
    {
        $product = $this->productRepository->findByIdOrFail($productId);

        if (!$product->is_active) {
            throw new \Exception('Product is not available.');
        }

        if ($product->quantity < $quantity) {
            throw new \Exception('Insufficient stock for this product.');
        }

        $existingItem = $this->cartRepository->findCartItem($userId, $productId);

        if ($existingItem) {
            $newQuantity = $existingItem->quantity + $quantity;

            if ($product->quantity < $newQuantity) {
                throw new \Exception('Insufficient stock for the requested quantity.');
            }

            $existingItem->update(['quantity' => $newQuantity]);

            return $existingItem->fresh();
        }

        return $this->cartRepository->create([
            'user_id' => $userId,
            'product_id' => $productId,
            'quantity' => $quantity,
        ]);
    }

    public function updateQuantity(int $userId, int $productId, int $quantity): Cart
    {
        $existingItem = $this->cartRepository->findCartItem($userId, $productId);

        if (!$existingItem) {
            throw new \Exception('Cart item not found.');
        }

        if ($quantity <= 0) {
            $existingItem->delete();
            throw new \Exception('Item removed from cart.');
        }

        $product = $this->productRepository->findByIdOrFail($productId);

        if ($product->quantity < $quantity) {
            throw new \Exception('Insufficient stock for the requested quantity.');
        }

        $existingItem->update(['quantity' => $quantity]);

        return $existingItem->fresh();
    }

    public function removeFromCart(int $userId, int $productId): bool
    {
        return $this->cartRepository->removeItem($userId, $productId);
    }

    public function clearCart(int $userId): bool
    {
        return $this->cartRepository->clearCart($userId);
    }

    public function getCartTotal(int $userId): float
    {
        return $this->cartRepository->getCartTotal($userId);
    }
}
