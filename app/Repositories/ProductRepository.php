<?php

namespace App\Repositories;

use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ProductRepository extends BaseRepository
{
    protected function model(): string
    {
        return Product::class;
    }

    public function findByIdOrFail(int $id, array $columns = ['*']): Product
    {
        $product = $this->getBuilder()
            ->with(['category', 'images'])
            ->withAvg('reviews', 'rating')
            ->find($id, $columns);

        if (!$product) {
            throw new ModelNotFoundException("Product not found with ID: {$id}");
        }

        return $product;
    }

    public function getActiveProducts(): Collection
    {
        return $this->getBuilder()
            ->where('is_active', true)
            ->with('category')
            ->withAvg('reviews', 'rating')
            ->get();
    }

    public function getActiveProductsPaginated(int $perPage = 15): LengthAwarePaginator
    {
        return $this->getBuilder()
            ->where('is_active', true)
            ->with('category')
            ->withAvg('reviews', 'rating')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    public function getFeaturedProducts(): Collection
    {
        return $this->getBuilder()
            ->where('is_active', true)
            ->where('is_featured', true)
            ->with('category')
            ->withAvg('reviews', 'rating')
            ->get();
    }

    public function paginateWithAvg(int $perPage = 15): LengthAwarePaginator
    {
        return $this->getBuilder()
            ->withAvg('reviews', 'rating')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    public function getFiltered(
        ?int $categoryId = null,
        ?string $search = null,
        ?float $minPrice = null,
        ?float $maxPrice = null,
        int $perPage = 15
    ): LengthAwarePaginator {
        return $this->getBuilder()
            ->where('is_active', true)
            ->when($categoryId, function (Builder $query) use ($categoryId) {
                $query->where('category_id', $categoryId);
            })
            ->when($search, function (Builder $query) use ($search) {
                $query->where(function (Builder $q) use ($search) {
                    $q->where('name', 'LIKE', "%{$search}%")
                        ->orWhere('description', 'LIKE', "%{$search}%");
                });
            })
            ->when($minPrice !== null, function (Builder $query) use ($minPrice) {
                $query->where(function (Builder $q) use ($minPrice) {
                    $q->where('sale_price', '>=', $minPrice)
                        ->orWhere(function (Builder $q2) use ($minPrice) {
                            $q2->whereNull('sale_price')
                                ->where('price', '>=', $minPrice);
                        });
                });
            })
            ->when($maxPrice !== null, function (Builder $query) use ($maxPrice) {
                $query->where(function (Builder $q) use ($maxPrice) {
                    $q->where('sale_price', '<=', $maxPrice)
                        ->orWhere(function (Builder $q2) use ($maxPrice) {
                            $q2->whereNull('sale_price')
                                ->where('price', '<=', $maxPrice);
                        });
                });
            })
            ->with('category')
            ->withAvg('reviews', 'rating')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    public function findBySlug(string $slug): ?Product
    {
        return $this->getBuilder()
            ->where('slug', $slug)
            ->with('category')
            ->withAvg('reviews', 'rating')
            ->first();
    }

    public function findBySlugOrFail(string $slug): Product
    {
        return $this->getBuilder()
            ->where('slug', $slug)
            ->with('category')
            ->withAvg('reviews', 'rating')
            ->firstOrFail();
    }

    public function getRelatedProducts(int $productId, int $limit = 4): Collection
    {
        $product = $this->findByIdOrFail($productId);

        return $this->getBuilder()
            ->where('id', '!=', $productId)
            ->where('category_id', $product->category_id)
            ->where('is_active', true)
            ->withAvg('reviews', 'rating')
            ->limit($limit)
            ->get();
    }

    public function search(string $term): Collection
    {
        return $this->getBuilder()
            ->where('is_active', true)
            ->where(function (Builder $query) use ($term) {
                $query->where('name', 'LIKE', "%{$term}%")
                    ->orWhere('description', 'LIKE', "%{$term}%");
            })
            ->with('category')
            ->withAvg('reviews', 'rating')
            ->get();
    }

    public function decrementQuantity(int $productId, int $quantity): Product
    {
        $product = $this->findByIdOrFail($productId);
        $product->decrement('quantity', $quantity);

        return $product->fresh();
    }

    public function getInStockProducts(): Collection
    {
        return $this->getBuilder()
            ->where('is_active', true)
            ->where('quantity', '>', 0)
            ->with('category')
            ->withAvg('reviews', 'rating')
            ->get();
    }

    public function toggleActive(int $id): Product
    {
        $product = $this->findByIdOrFail($id);
        $product->update(['is_active' => !$product->is_active]);

        return $product->fresh();
    }

    public function toggleFeatured(int $id): Product
    {
        $product = $this->findByIdOrFail($id);
        $product->update(['is_featured' => !$product->is_featured]);

        return $product->fresh();
    }

    public function slugExists(string $slug, ?int $excludeId = null): bool
    {
        $query = $this->getBuilder()->where('slug', $slug);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }

    public function getBySeller(int $sellerId, ?string $search = null, int $perPage = 15): LengthAwarePaginator
    {
        return $this->getBuilder()
            ->where('seller_id', $sellerId)
            ->when($search, function (Builder $query) use ($search) {
                $query->where(function (Builder $q) use ($search) {
                    $q->where('name', 'LIKE', "%{$search}%")
                        ->orWhere('slug', 'LIKE', "%{$search}%");
                });
            })
            ->with('category')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    public function getBySellerAndIdOrFail(int $sellerId, int $productId): Product
    {
        $product = $this->getBuilder()
            ->where('seller_id', $sellerId)
            ->with(['images', 'category'])
            ->find($productId);

        if (!$product) {
            throw new ModelNotFoundException("Product not found for this seller.");
        }

        return $product;
    }

    public function countForSeller(int $sellerId): int
    {
        return $this->count(['seller_id' => $sellerId]);
    }

    public function countActiveForSeller(int $sellerId): int
    {
        return $this->getBuilder()->where('seller_id', $sellerId)->where('is_active', true)->count();
    }

    public function getLowStockForSeller(int $sellerId, int $threshold = 10): Collection
    {
        return $this->getBuilder()
            ->where('seller_id', $sellerId)
            ->where('is_active', true)
            ->where('quantity', '<=', $threshold)
            ->orderBy('quantity', 'asc')
            ->get();
    }
}
