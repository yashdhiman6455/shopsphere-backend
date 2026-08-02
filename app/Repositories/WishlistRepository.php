<?php

namespace App\Repositories;

use App\Models\Wishlist;
use Illuminate\Database\Eloquent\Collection;

class WishlistRepository extends BaseRepository
{
    protected function model(): string
    {
        return Wishlist::class;
    }

    public function getWishlistForUser(int $userId): Collection
    {
        return $this->getBuilder()
            ->where('user_id', $userId)
            ->with(['product' => fn ($q) => $q->with('category')->with('seller')])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function findItem(int $userId, int $productId): ?Wishlist
    {
        return $this->getBuilder()
            ->where('user_id', $userId)
            ->where('product_id', $productId)
            ->first();
    }

    public function addItem(int $userId, int $productId): Wishlist
    {
        return $this->getBuilder()->firstOrCreate([
            'user_id' => $userId,
            'product_id' => $productId,
        ]);
    }

    public function removeItem(int $userId, int $productId): bool
    {
        return (bool) $this->getBuilder()
            ->where('user_id', $userId)
            ->where('product_id', $productId)
            ->delete();
    }

    public function hasItem(int $userId, int $productId): bool
    {
        return $this->exists(['user_id' => $userId, 'product_id' => $productId]);
    }

    public function getWishlistProductIds(int $userId): array
    {
        return $this->getBuilder()
            ->where('user_id', $userId)
            ->pluck('product_id')
            ->all();
    }

    public function countForUser(int $userId): int
    {
        return $this->count(['user_id' => $userId]);
    }
}
