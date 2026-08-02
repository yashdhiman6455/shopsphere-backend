<?php

namespace App\Services;

use App\Models\Category;
use App\Repositories\CategoryRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class CategoryService
{
    protected const CACHE_KEY_ACTIVE = 'categories.active';

    public function __construct(
        protected CategoryRepository $categoryRepository
    ) {}

    public function getAll(int $perPage = 15, bool $activeOnly = false): LengthAwarePaginator|Collection
    {
        if ($activeOnly) {
            return Cache::remember(self::CACHE_KEY_ACTIVE, now()->addHour(), function () {
                return $this->categoryRepository->getActiveCategories();
            });
        }

        return $this->categoryRepository->paginate($perPage);
    }

    public function getById(int $id): Category
    {
        return $this->categoryRepository->findByIdOrFail($id);
    }

    public function getBySlug(string $slug): Category
    {
        return $this->categoryRepository->findBySlugOrFail($slug);
    }

    public function create(array $data): Category
    {
        $data['slug'] = Str::slug($data['name']);

        $category = $this->categoryRepository->create($data);
        Cache::forget(self::CACHE_KEY_ACTIVE);

        return $category;
    }

    public function update(int $id, array $data): Category
    {
        $this->categoryRepository->findByIdOrFail($id);

        if (isset($data['name'])) {
            $data['slug'] = Str::slug($data['name']);
        }

        $category = $this->categoryRepository->update($id, $data);
        Cache::forget(self::CACHE_KEY_ACTIVE);

        return $category;
    }

    public function delete(int $id): bool
    {
        $this->categoryRepository->findByIdOrFail($id);

        $deleted = $this->categoryRepository->delete($id);
        Cache::forget(self::CACHE_KEY_ACTIVE);

        return $deleted;
    }
}
