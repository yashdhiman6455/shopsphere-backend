<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CartController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\CouponController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\EmailVerificationController;
use App\Http\Controllers\Api\InvoiceController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\RefundController;
use App\Http\Controllers\Api\ReviewController;
use App\Http\Controllers\Api\Seller\SellerDashboardController;
use App\Http\Controllers\Api\Seller\SellerNotificationController;
use App\Http\Controllers\Api\Seller\SellerOrderController;
use App\Http\Controllers\Api\Seller\SellerProductController;
use App\Http\Controllers\Api\Seller\SellerRevenueController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\WishlistController;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/webhook/stripe', [PaymentController::class, 'webhook']);

Route::get('/products', [ProductController::class, 'index']);
Route::get('/products/{id}', [ProductController::class, 'show']);
Route::get('/products/{id}/reviews', [ReviewController::class, 'index']);
Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/categories/{id}', [CategoryController::class, 'show']);

Route::get('/email/verify/{id}/{hash}', [EmailVerificationController::class, 'verify']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);

    Route::post('/email/verification-notification', [EmailVerificationController::class, 'sendNotification']);

    Route::put('/profile', [UserController::class, 'updateProfile']);

    Route::middleware('role:customer')->group(function () {
        Route::post('/cart/add', [CartController::class, 'add']);
        Route::get('/cart', [CartController::class, 'index']);
        Route::put('/cart/{productId}', [CartController::class, 'update']);
        Route::delete('/cart/{productId}', [CartController::class, 'remove']);
        Route::delete('/cart', [CartController::class, 'clear']);

        Route::get('/wishlist', [WishlistController::class, 'index']);
        Route::post('/wishlist/{productId}', [WishlistController::class, 'store']);
        Route::post('/wishlist/{productId}/toggle', [WishlistController::class, 'toggle']);
        Route::delete('/wishlist/{productId}', [WishlistController::class, 'destroy']);

        Route::post('/products/{id}/reviews', [ReviewController::class, 'store']);
        Route::delete('/products/{id}/reviews', [ReviewController::class, 'destroy']);

        Route::post('/orders', [OrderController::class, 'store']);
        Route::get('/orders/user', [OrderController::class, 'userOrders']);
        Route::get('/orders/{id}', [OrderController::class, 'show']);
        Route::put('/orders/{id}/cancel', [OrderController::class, 'cancel']);

        Route::post('/coupons/apply', [CouponController::class, 'apply']);

        Route::post('/payment/checkout-session', [PaymentController::class, 'createCheckoutSession']);
        Route::get('/payment/session-status', [PaymentController::class, 'sessionStatus']);

        Route::get('/orders/{id}/invoice', [InvoiceController::class, 'download']);
    });

    Route::middleware('seller')->prefix('seller')->group(function () {
        Route::get('/dashboard', [SellerDashboardController::class, 'index']);
        Route::get('/products', [SellerProductController::class, 'index']);
        Route::post('/products', [SellerProductController::class, 'store']);
        Route::post('/products/upload', [SellerProductController::class, 'upload']);
        Route::get('/products/{id}', [SellerProductController::class, 'show']);
        Route::put('/products/{id}', [SellerProductController::class, 'update']);
        Route::delete('/products/{id}', [SellerProductController::class, 'destroy']);
        Route::get('/orders', [SellerOrderController::class, 'index']);
        Route::put('/orders/{id}/status', [SellerOrderController::class, 'updateStatus']);
        Route::get('/revenue', [SellerRevenueController::class, 'index']);
        Route::get('/notifications', [SellerNotificationController::class, 'index']);
        Route::post('/notifications/read-all', [SellerNotificationController::class, 'markAllRead']);
        Route::post('/notifications/{id}/read', [SellerNotificationController::class, 'markRead']);
    });

    Route::middleware('admin')->prefix('admin')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index']);
        Route::get('/pending-sellers', [UserController::class, 'pendingSellers']);
        Route::put('/users/{id}/approve-seller', [UserController::class, 'approveSeller']);
        Route::put('/users/{id}/reject-seller', [UserController::class, 'rejectSeller']);
        Route::post('/orders/{id}/refund', [RefundController::class, 'store']);

        Route::apiResource('users', UserController::class);
        Route::put('/users/{id}/toggle-active', [UserController::class, 'toggleActive']);

        Route::apiResource('categories', CategoryController::class);

        Route::apiResource('products', ProductController::class);

        Route::apiResource('orders', OrderController::class)->only(['index', 'show', 'update']);
        Route::put('/orders/{id}/status', [OrderController::class, 'updateStatus']);

        Route::apiResource('coupons', CouponController::class);
    });
});
