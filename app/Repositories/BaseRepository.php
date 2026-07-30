<?php

namespace App\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;

abstract class BaseRepository
{
    protected Model $model;

    abstract protected function model(): string;

    public function __construct()
    {
        $this->model = new ($this->model());
    }

    public function getBuilder(): Builder
    {
        return $this->model->newQuery();
    }

    public function paginate(int $perPage = 15, array $columns = ['*']): LengthAwarePaginator
    {
        return $this->getBuilder()->paginate($perPage, $columns);
    }

    public function findById(int $id, array $columns = ['*']): ?Model
    {
        return $this->getBuilder()->find($id, $columns);
    }

    public function findByIdOrFail(int $id, array $columns = ['*']): Model
    {
        $model = $this->findById($id, $columns);

        if (!$model) {
            throw new ModelNotFoundException("Model not found with ID: {$id}");
        }

        return $model;
    }

    public function create(array $data): Model
    {
        return $this->model->create($data);
    }

    public function update(int $id, array $data): Model
    {
        $model = $this->findByIdOrFail($id);
        $model->update($data);

        return $model->fresh();
    }

    public function delete(int $id): bool
    {
        $model = $this->findByIdOrFail($id);

        return $model->delete();
    }

    public function withQuery(callable $callback): Builder
    {
        $query = $this->getBuilder();

        return $callback($query);
    }

    public function count(array $conditions = []): int
    {
        return $this->getBuilder()
            ->when(!empty($conditions), function (Builder $query) use ($conditions) {
                foreach ($conditions as $column => $value) {
                    $query->where($column, $value);
                }
            })
            ->count();
    }

    public function exists(array $conditions): bool
    {
        return $this->getBuilder()
            ->where($conditions)
            ->exists();
    }
}
