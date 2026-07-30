<?php

namespace App\Services;

use App\Models\Coupon;
use App\Repositories\CouponRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class CouponService
{
    public function __construct(
        protected CouponRepository $couponRepository
    ) {}

    public function getAll(int $perPage = 15): LengthAwarePaginator
    {
        return $this->couponRepository->paginate($perPage);
    }

    public function getById(int $id): Coupon
    {
        return $this->couponRepository->findByIdOrFail($id);
    }

    public function create(array $data): Coupon
    {
        $data['code'] = strtoupper($data['code']);
        $data['used_count'] = 0;

        return $this->couponRepository->create($data);
    }

    public function update(int $id, array $data): Coupon
    {
        $this->couponRepository->findByIdOrFail($id);

        if (isset($data['code'])) {
            $data['code'] = strtoupper($data['code']);
        }

        return $this->couponRepository->update($id, $data);
    }

    public function delete(int $id): bool
    {
        $this->couponRepository->findByIdOrFail($id);

        return $this->couponRepository->delete($id);
    }

    public function validateAndApply(string $code, float $subtotal): array
    {
        $coupon = $this->couponRepository->findByCode($code);

        if (!$coupon || !$coupon->isValid()) {
            return [
                'valid' => false,
                'message' => 'Invalid or expired coupon code.',
            ];
        }

        if ($subtotal < $coupon->min_order_amount) {
            return [
                'valid' => false,
                'message' => "Minimum order amount is {$coupon->min_order_amount}.",
            ];
        }

        $discount = $coupon->calculateDiscount($subtotal);

        return [
            'valid' => true,
            'coupon' => $coupon,
            'discount' => $discount,
            'message' => 'Coupon applied successfully.',
        ];
    }
}
