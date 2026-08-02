<?php

namespace App\Http\Controllers\Api\Seller;

use App\Http\Controllers\Controller;
use App\Http\Requests\Seller\StoreSellerProductRequest;
use App\Http\Requests\Seller\UpdateSellerProductRequest;
use App\Http\Resources\SellerProductResource;
use App\Services\SellerProductService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class SellerProductController extends Controller
{
    public function __construct(private SellerProductService $sellerProductService) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $perPage = (int) $request->get('per_page', 15);

        return SellerProductResource::collection(
            $this->sellerProductService->getAllForSeller($request->user(), $request->get('search'), $perPage)
        );
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $product = $this->sellerProductService->getForSellerOrFail($request->user(), $id);

        return response()->json([
            'success' => true,
            'data' => [
                'product' => new SellerProductResource($product->load('images', 'category')),
            ],
        ]);
    }

    public function store(StoreSellerProductRequest $request): JsonResponse
    {
        $product = $this->sellerProductService->createForSeller($request->user(), $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Product created successfully.',
            'data' => [
                'product' => new SellerProductResource($product->load('images', 'category')),
            ],
        ], 201);
    }

    public function update(UpdateSellerProductRequest $request, int $id): JsonResponse
    {
        $product = $this->sellerProductService->updateForSeller($request->user(), $id, $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Product updated successfully.',
            'data' => [
                'product' => new SellerProductResource($product->load('images', 'category')),
            ],
        ]);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $this->sellerProductService->deleteForSeller($request->user(), $id);

        return response()->json([
            'success' => true,
            'message' => 'Product deleted successfully.',
        ]);
    }

    public function upload(Request $request): JsonResponse
    {
        $request->validate([
            'image' => ['required', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:5120'],
        ]);

        $url = $this->sellerProductService->uploadImage($request->file('image'));

        return response()->json([
            'success' => true,
            'message' => 'Image uploaded successfully.',
            'data' => ['url' => $url],
        ]);
    }
}
