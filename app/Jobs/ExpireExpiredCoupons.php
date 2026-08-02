<?php

namespace App\Jobs;

use App\Repositories\CouponRepository;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ExpireExpiredCoupons implements ShouldQueue
{
    use Queueable;

    public function handle(CouponRepository $couponRepository): void
    {
        $expired = $couponRepository->getExpiredCoupons();

        foreach ($expired as $coupon) {
            $coupon->update(['is_active' => false]);
        }
    }
}
