<?php

namespace App\Repositories;

use App\Models\Coupon;
use Illuminate\Database\Eloquent\Collection;

class CouponRepository extends BaseRepository
{
    protected function model(): string
    {
        return Coupon::class;
    }

    public function findByCode(string $code): ?Coupon
    {
        return $this->getBuilder()
            ->where('code', $code)
            ->first();
    }

    public function findByCodeOrFail(string $code): Coupon
    {
        return $this->getBuilder()
            ->where('code', $code)
            ->firstOrFail();
    }

    public function getActiveCoupons(): Collection
    {
        return $this->getBuilder()
            ->where('is_active', true)
            ->where(function ($query) {
                $query->whereNull('starts_at')
                    ->orWhere('starts_at', '<=', now());
            })
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>=', now());
            })
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function getValidCoupons(): Collection
    {
        return $this->getBuilder()
            ->where('is_active', true)
            ->where(function ($query) {
                $query->whereNull('starts_at')
                    ->orWhere('starts_at', '<=', now());
            })
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>=', now());
            })
            ->where(function ($query) {
                $query->whereNull('max_uses')
                    ->orWhereColumn('used_count', '<', 'max_uses');
            })
            ->get();
    }

    public function incrementUsage(int $couponId): Coupon
    {
        $coupon = $this->findByIdOrFail($couponId);
        $coupon->increment('used_count');

        return $coupon->fresh();
    }

    public function codeExists(string $code, ?int $excludeId = null): bool
    {
        $query = $this->getBuilder()->where('code', $code);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }

    public function toggleActive(int $id): Coupon
    {
        $coupon = $this->findByIdOrFail($id);
        $coupon->update(['is_active' => !$coupon->is_active]);

        return $coupon->fresh();
    }

    public function getExpiredCoupons(): Collection
    {
        return $this->getBuilder()
            ->where('is_active', true)
            ->where('expires_at', '<', now())
            ->get();
    }

    public function getFullyUsedCoupons(): Collection
    {
        return $this->getBuilder()
            ->where('is_active', true)
            ->whereNotNull('max_uses')
            ->whereColumn('used_count', '>=', 'max_uses')
            ->get();
    }
}
