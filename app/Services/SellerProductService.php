<?php

namespace App\Services;

use App\Models\Product;
use App\Models\User;
use App\Repositories\ProductRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SellerProductService
{
    public function __construct(
        protected ProductRepository $productRepository
    ) {}

    public function getAllForSeller(User $seller, ?string $search = null, int $perPage = 15): LengthAwarePaginator
    {
        return $this->productRepository->getBySeller($seller->id, $search, $perPage);
    }

    public function getForSellerOrFail(User $seller, int $id): Product
    {
        return $this->productRepository->getBySellerAndIdOrFail($seller->id, $id);
    }

    public function createForSeller(User $seller, array $data): Product
    {
        $data['seller_id'] = $seller->id;
        $data['slug'] = $this->makeUniqueSlug($data['name']);
        $data['is_active'] = $data['is_active'] ?? true;

        $product = $this->productRepository->create($data);
        $this->syncImages($product, $data['images'] ?? []);

        return $product->fresh(['images', 'category']);
    }

    public function updateForSeller(User $seller, int $id, array $data): Product
    {
        $product = $this->productRepository->getBySellerAndIdOrFail($seller->id, $id);

        if (isset($data['name'])) {
            $data['slug'] = $this->makeUniqueSlug($data['name'], $product->id);
        }

        $product->update($data);

        if (array_key_exists('images', $data)) {
            $this->syncImages($product, $data['images'] ?? []);
        }

        return $product->fresh(['images', 'category']);
    }

    public function deleteForSeller(User $seller, int $id): bool
    {
        $product = $this->productRepository->getBySellerAndIdOrFail($seller->id, $id);

        return $product->delete();
    }

    public function uploadImage(UploadedFile $file): string
    {
        $path = $file->store('products', 'public');

        return Storage::url($path);
    }

    protected function syncImages(Product $product, array $urls): void
    {
        $product->images()->delete();

        foreach (array_values($urls) as $index => $url) {
            $product->images()->create([
                'image' => $url,
                'is_primary' => $index === 0,
            ]);
        }
    }

    protected function makeUniqueSlug(string $name, ?int $excludeId = null): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $counter = 2;

        while ($this->productRepository->slugExists($slug, $excludeId)) {
            $slug = $base . '-' . $counter++;
        }

        return $slug;
    }
}
