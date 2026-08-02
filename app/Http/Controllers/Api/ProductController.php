<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Services\ProductService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ProductController extends Controller
{
    public function __construct(
        private ProductService $productService
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $filters = $request->only([
            'category_id', 'search', 'min_price', 'max_price', 'per_page',
        ]);

        $products = $this->productService->getAll($filters);

        return ProductResource::collection($products);
    }

    public function show($id): JsonResponse
    {
        $product = $this->productService->getById($id);

        return response()->json([
            'success' => true,
            'data' => [
                'product' => new ProductResource($product),
            ],
        ]);
    }

    public function store(\App\Http\Requests\Admin\StoreProductRequest $request): JsonResponse
    {
        $product = $this->productService->create($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Product created successfully.',
            'data' => [
                'product' => new ProductResource($product),
            ],
        ], 201);
    }

    public function update(\App\Http\Requests\Admin\UpdateProductRequest $request, $id): JsonResponse
    {
        $product = $this->productService->update($id, $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Product updated successfully.',
            'data' => [
                'product' => new ProductResource($product),
            ],
        ]);
    }

    public function destroy($id): JsonResponse
    {
        $this->productService->delete($id);

        return response()->json([
            'success' => true,
            'message' => 'Product deleted successfully.',
        ]);
    }
}
