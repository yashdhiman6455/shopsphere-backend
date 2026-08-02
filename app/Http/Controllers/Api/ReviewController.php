<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreReviewRequest;
use App\Http\Resources\ReviewResource;
use App\Services\ReviewService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ReviewController extends Controller
{
    public function __construct(private ReviewService $reviewService) {}

    public function index(int $productId): AnonymousResourceCollection
    {
        $perPage = (int) request('per_page', 10);

        return ReviewResource::collection($this->reviewService->getForProduct($productId, $perPage));
    }

    public function store(StoreReviewRequest $request, int $productId): JsonResponse
    {
        try {
            $review = $this->reviewService->createForUser($request->user(), $productId, $request->validated());
        } catch (\RuntimeException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Review submitted successfully.',
            'data' => [
                'review' => new ReviewResource($review->load('user')),
                'stats' => $this->reviewService->getReviewsStats($productId),
            ],
        ], 201);
    }

    public function destroy(Request $request, int $productId): JsonResponse
    {
        try {
            $this->reviewService->deleteOwn($request->user(), $productId);
        } catch (\RuntimeException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Review deleted.',
            'data' => [
                'stats' => $this->reviewService->getReviewsStats($productId),
            ],
        ]);
    }
}
