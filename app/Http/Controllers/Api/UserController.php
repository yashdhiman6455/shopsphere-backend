<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class UserController extends Controller
{
    public function __construct(
        private UserService $userService
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $perPage = $request->query('per_page', 15);
        $users = $this->userService->getAll($request->query('search'), $request->query('role'), $perPage);

        return UserResource::collection($users);
    }

    public function show($id): JsonResponse
    {
        $user = $this->userService->getById($id);

        return response()->json([
            'success' => true,
            'data' => [
                'user' => new UserResource($user),
            ],
        ]);
    }

    public function store(StoreUserRequest $request): JsonResponse
    {
        $user = $this->userService->create($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'User created successfully.',
            'data' => [
                'user' => new UserResource($user),
            ],
        ], 201);
    }

    public function update(UpdateUserRequest $request, $id): JsonResponse
    {
        $user = $this->userService->update($id, $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'User updated successfully.',
            'data' => [
                'user' => new UserResource($user),
            ],
        ]);
    }

    public function updateProfile(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string'],
            'city' => ['nullable', 'string', 'max:255'],
            'state' => ['nullable', 'string', 'max:255'],
            'zip_code' => ['nullable', 'string', 'max:20'],
        ]);

        $user = $this->userService->update($request->user()->id, $validated);

        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully.',
            'data' => [
                'user' => new UserResource($user),
            ],
        ]);
    }

    public function destroy($id): JsonResponse
    {
        $this->userService->delete($id);

        return response()->json([
            'success' => true,
            'message' => 'User deleted successfully.',
        ]);
    }

    public function toggleActive($id): JsonResponse
    {
        $user = $this->userService->toggleActive($id);

        return response()->json([
            'success' => true,
            'message' => 'User status toggled successfully.',
            'data' => [
                'user' => new UserResource($user),
            ],
        ]);
    }

    public function approveSeller(int $id): JsonResponse
    {
        try {
            $user = $this->userService->approveSeller($id);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Seller approved successfully.',
            'data' => [
                'user' => new UserResource($user),
            ],
        ]);
    }

    public function rejectSeller(int $id): JsonResponse
    {
        try {
            $user = $this->userService->rejectSeller($id);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Seller rejected.',
            'data' => [
                'user' => new UserResource($user),
            ],
        ]);
    }

    public function pendingSellers(): JsonResponse
    {
        $sellers = $this->userService->getPendingSellers();

        return response()->json([
            'success' => true,
            'data' => [
                'sellers' => UserResource::collection($sellers),
            ],
        ]);
    }
}
