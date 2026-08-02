<?php

namespace App\Services;

use App\Models\Product;
use App\Models\User;
use App\Models\Wishlist;
use App\Repositories\WishlistRepository;
use Illuminate\Database\Eloquent\Collection;

class WishlistService
{
    public function __construct(
        protected WishlistRepository $wishlistRepository
    ) {}

    public function getWishlist(User $user): Collection
    {
        return $this->wishlistRepository->getWishlistForUser($user->id);
    }

    public function getProductIds(User $user): array
    {
        return $this->wishlistRepository->getWishlistProductIds($user->id);
    }

    public function toggle(User $user, int $productId): array
    {
        Product::findOrFail($productId);

        if ($this->wishlistRepository->hasItem($user->id, $productId)) {
            $this->wishlistRepository->removeItem($user->id, $productId);

            return ['added' => false];
        }

        $this->wishlistRepository->addItem($user->id, $productId);

        return ['added' => true];
    }

    public function add(User $user, int $productId): Wishlist
    {
        Product::findOrFail($productId);

        return $this->wishlistRepository->addItem($user->id, $productId);
    }

    public function remove(User $user, int $productId): bool
    {
        return $this->wishlistRepository->removeItem($user->id, $productId);
    }
}
