<?php

namespace App\Repositories;

use App\Models\Review;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ReviewRepository extends BaseRepository
{
    protected function model(): string
    {
        return Review::class;
    }

    public function getForProduct(int $productId, int $perPage = 10): LengthAwarePaginator
    {
        return $this->getBuilder()
            ->where('product_id', $productId)
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    public function getAll(): Collection
    {
        return $this->getBuilder()
            ->with(['user', 'product'])
            ->latest()
            ->get();
    }

    public function findByUserAndProduct(int $userId, int $productId): ?Review
    {
        return $this->getBuilder()
            ->where('user_id', $userId)
            ->where('product_id', $productId)
            ->first();
    }

    public function averageRating(int $productId): float
    {
        return (float) $this->getBuilder()->where('product_id', $productId)->avg('rating') ?? 0;
    }

    public function getStats(int $productId): array
    {
        $row = $this->getBuilder()
            ->where('product_id', $productId)
            ->selectRaw('AVG(rating) as average_rating, COUNT(*) as total')
            ->first();

        return [
            'average_rating' => round((float) ($row->average_rating ?? 0), 2),
            'total' => (int) ($row->total ?? 0),
        ];
    }
}
