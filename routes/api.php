<?php

use App\Http\Controllers\Api\CartController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\CouponController;
use App\Http\Controllers\Api\InvoiceController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\PaymentController;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/webhook/stripe', [PaymentController::class, 'webhook']);

Route::get('/products', [ProductController::class, 'index']);
Route::get('/products/{id}', [ProductController::class, 'show']);
Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/categories/{id}', [CategoryController::class, 'show']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);

    Route::post('/cart/add', [CartController::class, 'add']);
    Route::get('/cart', [CartController::class, 'index']);
    Route::put('/cart/{productId}', [CartController::class, 'update']);
    Route::delete('/cart/{productId}', [CartController::class, 'remove']);
    Route::delete('/cart', [CartController::class, 'clear']);

    Route::post('/orders', [OrderController::class, 'store']);
    Route::get('/orders/user', [OrderController::class, 'userOrders']);
    Route::get('/orders/{id}', [OrderController::class, 'show']);
    Route::put('/orders/{id}/cancel', [OrderController::class, 'cancel']);

    Route::post('/coupons/apply', [CouponController::class, 'apply']);

    Route::post('/payment/checkout-session', [PaymentController::class, 'createCheckoutSession']);
    Route::get('/payment/session-status', [PaymentController::class, 'sessionStatus']);

    Route::put('/profile', [UserController::class, 'updateProfile']);

    Route::get('/orders/{id}/invoice', [InvoiceController::class, 'download']);

    Route::middleware('admin')->prefix('admin')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index']);

        Route::apiResource('users', UserController::class);
        Route::put('/users/{id}/toggle-active', [UserController::class, 'toggleActive']);

        Route::apiResource('categories', CategoryController::class);

        Route::apiResource('products', ProductController::class);

        Route::apiResource('orders', OrderController::class)->only(['index', 'show', 'update']);
        Route::put('/orders/{id}/status', [OrderController::class, 'updateStatus']);

        Route::apiResource('coupons', CouponController::class);
    });
});
