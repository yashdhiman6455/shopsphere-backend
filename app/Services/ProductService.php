<?php

namespace App\Services;

use App\Models\Product;
use App\Repositories\ProductRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class ProductService
{
    protected const CACHE_KEY_FEATURED = 'products.featured';

    public function __construct(
        protected ProductRepository $productRepository
    ) {}

    public function getAll(array $filters = []): LengthAwarePaginator
    {
        $perPage = $filters['per_page'] ?? 15;

        if (isset($filters['category_id']) || isset($filters['search']) || isset($filters['min_price']) || isset($filters['max_price'])) {
            return $this->productRepository->getFiltered(
                categoryId: $filters['category_id'] ?? null,
                search: $filters['search'] ?? null,
                minPrice: isset($filters['min_price']) ? (float) $filters['min_price'] : null,
                maxPrice: isset($filters['max_price']) ? (float) $filters['max_price'] : null,
                perPage: $perPage
            );
        }

        return $this->productRepository->paginateWithAvg($perPage);
    }

    public function getById(int $id): Product
    {
        return $this->productRepository->findByIdOrFail($id);
    }

    public function getBySlug(string $slug): Product
    {
        return $this->productRepository->findBySlugOrFail($slug);
    }

    public function create(array $data): Product
    {
        $data['slug'] = Str::slug($data['name']);

        $product = $this->productRepository->create($data);
        Cache::forget(self::CACHE_KEY_FEATURED);

        return $product;
    }

    public function update(int $id, array $data): Product
    {
        $this->productRepository->findByIdOrFail($id);

        if (isset($data['name'])) {
            $data['slug'] = Str::slug($data['name']);
        }

        $product = $this->productRepository->update($id, $data);
        Cache::forget(self::CACHE_KEY_FEATURED);

        return $product;
    }

    public function delete(int $id): bool
    {
        $this->productRepository->findByIdOrFail($id);

        $deleted = $this->productRepository->delete($id);
        Cache::forget(self::CACHE_KEY_FEATURED);

        return $deleted;
    }

    public function getFeatured(): \Illuminate\Database\Eloquent\Collection
    {
        return Cache::remember(self::CACHE_KEY_FEATURED, now()->addHour(), function () {
            return $this->productRepository->getFeaturedProducts();
        });
    }
}
