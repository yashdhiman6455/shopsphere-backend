<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CouponResource;
use App\Services\CouponService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CouponController extends Controller
{
    public function __construct(
        private readonly CouponService $couponService
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $perPage = $request->query('per_page', 15);
        $coupons = $this->couponService->getAll($perPage);

        return CouponResource::collection($coupons);
    }

    public function show($id): JsonResponse
    {
        $coupon = $this->couponService->getById($id);

        return response()->json([
            'success' => true,
            'data' => [
                'coupon' => new CouponResource($coupon),
            ],
        ]);
    }

    public function store(\App\Http\Requests\Admin\StoreCouponRequest $request): JsonResponse
    {
        $coupon = $this->couponService->create($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Coupon created successfully.',
            'data' => [
                'coupon' => new CouponResource($coupon),
            ],
        ], 201);
    }

    public function update(\App\Http\Requests\Admin\UpdateCouponRequest $request, $id): JsonResponse
    {
        $coupon = $this->couponService->update($id, $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Coupon updated successfully.',
            'data' => [
                'coupon' => new CouponResource($coupon),
            ],
        ]);
    }

    public function destroy($id): JsonResponse
    {
        $this->couponService->delete($id);

        return response()->json([
            'success' => true,
            'message' => 'Coupon deleted successfully.',
        ]);
    }

    public function apply(Request $request): JsonResponse
    {
        $request->validate([
            'code' => ['required', 'string'],
            'subtotal' => ['required', 'numeric', 'min:0'],
        ]);

        $result = $this->couponService->validateAndApply($request->code, $request->subtotal);

        if (!$result['valid']) {
            return response()->json([
                'success' => false,
                'message' => $result['message'],
            ], 400);
        }

        return response()->json([
            'success' => true,
            'message' => $result['message'],
            'data' => [
                'discount' => $result['discount'],
                'coupon' => new CouponResource($result['coupon']),
            ],
        ]);
    }
}
