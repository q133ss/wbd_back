<?php

use App\Http\Middleware\GuestOnly;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum'])->group(function () {
    Route::prefix('seller')->group(function () {
        Route::apiResource('products', \App\Http\Controllers\Seller\ProductController::class);
        Route::post('/product/stop', [\App\Http\Controllers\Seller\ProductController::class, 'stop']);
        Route::post('/product/archive', [\App\Http\Controllers\Seller\ProductController::class, 'archive']);
        Route::post('/product/duplicate', [\App\Http\Controllers\Seller\ProductController::class, 'duplicate']);
        Route::apiResource('ads', \App\Http\Controllers\Seller\AdsController::class);
        Route::post('/ads/stop', [\App\Http\Controllers\Seller\AdsController::class, 'stop']);
        Route::post('/ads/archive', [\App\Http\Controllers\Seller\AdsController::class, 'archive']);
        Route::post('/ads/duplicate', [\App\Http\Controllers\Seller\AdsController::class, 'duplicate']);
        Route::get('/tariff/list', [\App\Http\Controllers\Seller\TariffController::class, 'index']);
        Route::get('/tariff/get-by-id/{id}', [\App\Http\Controllers\Seller\TariffController::class, 'detail']);
        Route::get('/tariff/{baybacks}', [\App\Http\Controllers\Seller\TariffController::class, 'show']);
        Route::post('/promocode/apply', [\App\Http\Controllers\Seller\PromocodeController::class, 'apply']);
        Route::get('/buybacks', [\App\Http\Controllers\Seller\BuybackController::class, 'index']);
        Route::post('/chat/{buyback}/file/{file}/approve', [\App\Http\Controllers\ChatController::class, 'fileApprove']);
    });
});

Route::middleware([GuestOnly::class])->group(function () {
    Route::post('register/send-code', [\App\Http\Controllers\AuthController::class, 'sendCode']);
    Route::post('register/verify-code', [\App\Http\Controllers\AuthController::class, 'verifyCode']);
    Route::post('password/reset/send-code', [\App\Http\Controllers\AuthController::class, 'resetSendCode']);
    Route::post('password/reset/check-code', [\App\Http\Controllers\AuthController::class, 'resetVerifyCode']);
    Route::post('password/reset', [\App\Http\Controllers\AuthController::class, 'reset']);
    Route::post('login', [\App\Http\Controllers\AuthController::class, 'login']);
});
Route::get('/roles', [\App\Http\Controllers\AuthController::class, 'roles']);
Route::post('register/complete', [\App\Http\Controllers\AuthController::class, 'completeRegistration']);

// Товары
Route::get('/products', [\App\Http\Controllers\Front\ProductController::class, 'index']);
Route::get('/product/{id}', [\App\Http\Controllers\Front\ProductController::class, 'show']);
Route::get('/product/related/{id}', [\App\Http\Controllers\Front\ProductController::class, 'related']);

// Категории
Route::get('/category', [\App\Http\Controllers\CategoryController::class, 'index']);
Route::get('/sub-category/{id}', [\App\Http\Controllers\CategoryController::class, 'indexSubCategory']);

Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/wb/fetch-product/{product_id}', [\App\Http\Controllers\WB\ProductController::class, 'fetchProduct']);
    Route::post('/wb/add-product/{product_id}', [\App\Http\Controllers\WB\ProductController::class, 'addProduct']);

    // Профиль
    Route::get('profile', [\App\Http\Controllers\ProfileController::class, 'index']);
    Route::post('profile', [\App\Http\Controllers\ProfileController::class, 'update']);
    Route::post('/profile/avatar', [\App\Http\Controllers\ProfileController::class, 'avatar']);
    Route::get('transactions', [\App\Http\Controllers\ProfileController::class, 'transactions']);
    Route::get('/balance', [\App\Http\Controllers\ProfileController::class, 'balance']);
    Route::post('/withdraw', [\App\Http\Controllers\ProfileController::class, 'withdraw']);
    Route::get('/withdraws', [\App\Http\Controllers\ProfileController::class, 'withdraws']);
    Route::post('/withdraw/{id}', [\App\Http\Controllers\ProfileController::class, 'withdrawCancel']);
    Route::get('/profile/statistic', [\App\Http\Controllers\ProfileController::class, 'statistic']);

    // Корзина и избранное
    Route::post('/add-to-{type}', [\App\Http\Controllers\CartFavoriteController::class, 'add']);
    Route::get('/cart', [\App\Http\Controllers\CartFavoriteController::class, 'viewCart']);
    Route::get('/favorites', [\App\Http\Controllers\CartFavoriteController::class, 'viewFavorites']);
    Route::post('/remove-from-{type}', [\App\Http\Controllers\CartFavoriteController::class, 'remove']);

    Route::prefix('buyer')->group(function () {
        Route::post('/create-order/{ad_id}', [\App\Http\Controllers\Buyer\OrderController::class, 'store']);
        Route::get('/orders', [\App\Http\Controllers\Buyer\OrderController::class, 'index']);
        Route::get('/orders/{id}', [\App\Http\Controllers\Buyer\OrderController::class, 'show']);
        Route::post('/orders/{id}', [\App\Http\Controllers\Buyer\OrderController::class, 'send']);
    });
    Route::get('/chat/status-list', [\App\Http\Controllers\Buyer\OrderController::class, 'orderStatusList']);
    Route::post('/chat/{buyback_id}/send', [\App\Http\Controllers\ChatController::class, 'send']);
    Route::get('/messages/{buyback_id}', [\App\Http\Controllers\ChatController::class, 'messages']);
    Route::post('/buyback/{id}/cancel', [\App\Http\Controllers\ChatController::class, 'cancel']);
    Route::post('/chat/{id}/photo', [\App\Http\Controllers\ChatController::class, 'photo']);
});

// Профиль продавца
Route::get('/seller/{id}', [\App\Http\Controllers\Front\SellerController::class, 'show']);
Route::get('/buyer/{id}', [\App\Http\Controllers\Front\BuyerController::class, 'show']);

// SSE route
Route::get('/notifications/sse', [\App\Http\Controllers\SSEController::class, 'stream']);

// todo

/*
 * 3) Доделать сторону продавца в чате + написать доку для Степы
 */

//todo НА СЕГОДНЯ!!!!
// SSE проверить. Просто отправив сообщение
// * Чат + отзыв о продавце!!!!!!!! сегодня!
/*
 * сделать счетчик просмотров (через мидлвар)
 * Платежка
 * Бот
 */
