<?php

namespace App\Repositories;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class UserRepository extends BaseRepository
{
    protected function model(): string
    {
        return User::class;
    }

    public function findByEmail(string $email): ?User
    {
        return $this->getBuilder()
            ->where('email', $email)
            ->first();
    }

    public function getActiveUsers(): Collection
    {
        return $this->getBuilder()
            ->where('is_active', true)
            ->get();
    }

    public function getCustomers(): Collection
    {
        return $this->getBuilder()
            ->where('role', 'customer')
            ->get();
    }

    public function getCustomersPaginated(int $perPage = 15): LengthAwarePaginator
    {
        return $this->getBuilder()
            ->where('role', 'customer')
            ->paginate($perPage);
    }

    public function getAdmins(): Collection
    {
        return $this->getBuilder()
            ->where('role', 'admin')
            ->get();
    }

    public function adminsPaginated(int $perPage = 15): LengthAwarePaginator
    {
        return $this->getBuilder()
            ->where('role', 'admin')
            ->paginate($perPage);
    }

    public function search(string $term): Collection
    {
        return $this->getBuilder()
            ->where(function ($query) use ($term) {
                $query->where('name', 'LIKE', "%{$term}%")
                    ->orWhere('email', 'LIKE', "%{$term}%")
                    ->orWhere('phone', 'LIKE', "%{$term}%");
            })
            ->get();
    }

    public function toggleActive(int $id): User
    {
        $user = $this->findByIdOrFail($id);
        $user->update(['is_active' => !$user->is_active]);

        return $user->fresh();
    }
}
