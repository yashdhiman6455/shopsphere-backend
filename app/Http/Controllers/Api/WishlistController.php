<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\WishlistResource;
use App\Services\WishlistService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class WishlistController extends Controller
{
    public function __construct(private WishlistService $wishlistService) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        return WishlistResource::collection($this->wishlistService->getWishlist($request->user()));
    }

    public function toggle(Request $request, int $productId): JsonResponse
    {
        $result = $this->wishlistService->toggle($request->user(), $productId);

        return response()->json([
            'success' => true,
            'message' => $result['added'] ? 'Added to wishlist.' : 'Removed from wishlist.',
            'data' => $result,
        ]);
    }

    public function store(Request $request, int $productId): JsonResponse
    {
        $this->wishlistService->add($request->user(), $productId);

        return response()->json([
            'success' => true,
            'message' => 'Added to wishlist.',
        ], 201);
    }

    public function destroy(Request $request, int $productId): JsonResponse
    {
        $this->wishlistService->remove($request->user(), $productId);

        return response()->json([
            'success' => true,
            'message' => 'Removed from wishlist.',
        ]);
    }
}
