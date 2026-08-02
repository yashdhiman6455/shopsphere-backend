<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CartResource;
use App\Services\CartService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CartController extends Controller
{
    public function __construct(
        private CartService $cartService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $cartItems = $this->cartService->getCart($request->user()->id);
        $total = $this->cartService->getCartTotal($request->user()->id);

        return response()->json([
            'success' => true,
            'data' => [
                'items' => CartResource::collection($cartItems),
                'total' => $total,
            ],
        ]);
    }

    public function add(Request $request): JsonResponse
    {
        $request->validate([
            'product_id' => ['required', 'exists:products,id'],
            'quantity' => ['required', 'integer', 'min:1'],
        ]);

        $cartItem = $this->cartService->addToCart(
            $request->user()->id,
            $request->product_id,
            $request->quantity
        );

        return response()->json([
            'success' => true,
            'message' => 'Item added to cart.',
            'data' => [
                'cart_item' => new CartResource($cartItem),
            ],
        ], 201);
    }

    public function update(Request $request, $productId): JsonResponse
    {
        $request->validate([
            'quantity' => ['required', 'integer', 'min:1'],
        ]);

        $cartItem = $this->cartService->updateQuantity(
            $request->user()->id,
            $productId,
            $request->quantity
        );

        return response()->json([
            'success' => true,
            'message' => 'Cart updated successfully.',
            'data' => [
                'cart_item' => new CartResource($cartItem),
            ],
        ]);
    }

    public function remove(Request $request, $productId): JsonResponse
    {
        $this->cartService->removeFromCart($request->user()->id, $productId);

        return response()->json([
            'success' => true,
            'message' => 'Item removed from cart.',
        ]);
    }

    public function clear(Request $request): JsonResponse
    {
        $this->cartService->clearCart($request->user()->id);

        return response()->json([
            'success' => true,
            'message' => 'Cart cleared successfully.',
        ]);
    }
}
