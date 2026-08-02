<?php

namespace App\Services;

use App\Models\User;
use App\Repositories\UserRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class UserService
{
    public function __construct(
        protected UserRepository $userRepository
    ) {}

    public function getAll(?string $search = null, ?string $role = null, int $perPage = 15): LengthAwarePaginator
    {
        return $this->userRepository->getAllFiltered($search, $role, $perPage);
    }

    public function getPendingSellers(): Collection
    {
        return $this->userRepository->getPendingSellers();
    }

    public function getById(int $id): User
    {
        return $this->userRepository->findByIdOrFail($id);
    }

    public function create(array $data): User
    {
        $data['password'] = $data['password'] ?? 'password';
        $data['is_active'] = $data['is_active'] ?? true;
        $data['role'] = $data['role'] ?? 'customer';

        return $this->userRepository->create($data);
    }

    public function update(int $id, array $data): User
    {
        if (isset($data['password'])) {
            $data['password'] = $data['password'];
        } else {
            unset($data['password']);
        }

        return $this->userRepository->update($id, $data);
    }

    public function delete(int $id): bool
    {
        return $this->userRepository->delete($id);
    }

    public function toggleActive(int $id): User
    {
        return $this->userRepository->toggleActive($id);
    }

    public function approveSeller(int $id): User
    {
        return $this->userRepository->approveSeller($id);
    }

    public function rejectSeller(int $id): User
    {
        return $this->userRepository->rejectSeller($id);
    }
}
