<?php

namespace App\Repositories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Collection;

class CategoryRepository extends BaseRepository
{
    protected function model(): string
    {
        return Category::class;
    }

    public function getActiveCategories(): Collection
    {
        return $this->getBuilder()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    public function findBySlug(string $slug): ?Category
    {
        return $this->getBuilder()
            ->where('slug', $slug)
            ->first();
    }

    public function findBySlugOrFail(string $slug): Category
    {
        return $this->getBuilder()
            ->where('slug', $slug)
            ->firstOrFail();
    }

    public function getCategoriesWithProductsCount(): Collection
    {
        return $this->getBuilder()
            ->withCount('products')
            ->orderBy('name')
            ->get();
    }

    public function getActiveWithProductsCount(): Collection
    {
        return $this->getBuilder()
            ->where('is_active', true)
            ->withCount('products')
            ->orderBy('name')
            ->get();
    }

    public function toggleActive(int $id): Category
    {
        $category = $this->findByIdOrFail($id);
        $category->update(['is_active' => !$category->is_active]);

        return $category->fresh();
    }

    public function slugExists(string $slug, ?int $excludeId = null): bool
    {
        $query = $this->getBuilder()->where('slug', $slug);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }
}
