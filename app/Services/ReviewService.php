<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use App\Repositories\ReviewRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class ReviewService
{
    public function __construct(
        protected ReviewRepository $reviewRepository
    ) {}

    public function getForProduct(int $productId, int $perPage = 10): LengthAwarePaginator
    {
        return $this->reviewRepository->getForProduct($productId, $perPage);
    }

    public function createForUser(User $user, int $productId, array $data): Review
    {
        $product = Product::findOrFail($productId);

        $hasPurchased = $user->orders()
            ->where('status', '!=', 'cancelled')
            ->whereHas('items', fn ($q) => $q->where('product_id', $productId))
            ->exists();

        if (!$hasPurchased) {
            throw new \RuntimeException('You can only review products you have purchased.');
        }

        if ($this->reviewRepository->findByUserAndProduct($user->id, $productId)) {
            throw new \RuntimeException('You have already reviewed this product.');
        }

        return $this->reviewRepository->create([
            'user_id' => $user->id,
            'product_id' => $productId,
            'rating' => $data['rating'],
            'comment' => $data['comment'] ?? null,
        ]);
    }

    public function deleteOwn(User $user, int $productId): bool
    {
        $review = $this->reviewRepository->findByUserAndProduct($user->id, $productId);

        if (!$review) {
            throw new \RuntimeException('Review not found.');
        }

        return $review->delete();
    }

    public function getReviewsStats(int $productId): array
    {
        return $this->reviewRepository->getStats($productId);
    }
}
