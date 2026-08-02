<?php

namespace App\Repositories;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class UserRepository extends BaseRepository
{
    protected function model(): string
    {
        return User::class;
    }

    public function getAllFiltered(?string $search = null, ?string $role = null, int $perPage = 15): LengthAwarePaginator
    {
        return $this->getBuilder()
            ->when($search, function (Builder $query) use ($search) {
                $query->where(function (Builder $q) use ($search) {
                    $q->where('name', 'LIKE', "%{$search}%")
                        ->orWhere('email', 'LIKE', "%{$search}%")
                        ->orWhere('store_name', 'LIKE', "%{$search}%");
                });
            })
            ->when($role && $role !== 'all', function (Builder $query) use ($role) {
                $query->where('role', $role);
            })
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    public function getPendingSellers(): Collection
    {
        return $this->getBuilder()
            ->where('role', 'seller')
            ->whereNull('seller_approved_at')
            ->where('is_active', true)
            ->get();
    }

    public function countActiveSellers(): int
    {
        return $this->getBuilder()
            ->where('role', 'seller')
            ->whereNotNull('seller_approved_at')
            ->where('is_active', true)
            ->count();
    }

    public function countPendingSellers(): int
    {
        return $this->getBuilder()
            ->where('role', 'seller')
            ->whereNull('seller_approved_at')
            ->where('is_active', true)
            ->count();
    }

    public function approveSeller(int $id): User
    {
        $user = $this->findByIdOrFail($id);

        if (!$user->isSeller()) {
            throw new \InvalidArgumentException('User is not a seller.');
        }

        $user->update(['seller_approved_at' => now()]);

        $user->notify(new \App\Notifications\SellerApprovedNotification);

        return $user->fresh();
    }

    public function rejectSeller(int $id): User
    {
        $user = $this->findByIdOrFail($id);

        if (!$user->isSeller()) {
            throw new \InvalidArgumentException('User is not a seller.');
        }

        $user->update(['seller_approved_at' => null, 'is_active' => false]);

        $user->notify(new \App\Notifications\SellerRejectedNotification);

        return $user->fresh();
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
