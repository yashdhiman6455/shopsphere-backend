<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use App\Services\CategoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CategoryController extends Controller
{
    public function __construct(
        private readonly CategoryService $categoryService
    ) {}

    public function index(Request $request): JsonResponse|AnonymousResourceCollection
    {
        if ($request->is('api/categories') && !$request->is('api/admin/*')) {
            $categories = $this->categoryService->getAll(15, true);

            return response()->json([
                'success' => true,
                'data' => CategoryResource::collection($categories),
            ]);
        }

        $perPage = $request->query('per_page', 15);
        $categories = $this->categoryService->getAll($perPage);

        return CategoryResource::collection($categories);
    }

    public function show($id): JsonResponse
    {
        $category = $this->categoryService->getById($id);

        return response()->json([
            'success' => true,
            'data' => [
                'category' => new CategoryResource($category),
            ],
        ]);
    }

    public function store(\App\Http\Requests\Admin\StoreCategoryRequest $request): JsonResponse
    {
        $category = $this->categoryService->create($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Category created successfully.',
            'data' => [
                'category' => new CategoryResource($category),
            ],
        ], 201);
    }

    public function update(\App\Http\Requests\Admin\UpdateCategoryRequest $request, $id): JsonResponse
    {
        $category = $this->categoryService->update($id, $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Category updated successfully.',
            'data' => [
                'category' => new CategoryResource($category),
            ],
        ]);
    }

    public function destroy($id): JsonResponse
    {
        $this->categoryService->delete($id);

        return response()->json([
            'success' => true,
            'message' => 'Category deleted successfully.',
        ]);
    }
}
