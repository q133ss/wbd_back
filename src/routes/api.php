<?php

use App\Http\Middleware\GuestOnly;
use Illuminate\Support\Facades\Route;

Route::middleware([GuestOnly::class])->group(function () {
    Route::post('register/send-code', [\App\Http\Controllers\AuthController::class, 'sendCode']);
    Route::post('register/verify-code', [\App\Http\Controllers\AuthController::class, 'verifyCode']);
    Route::post('password/reset/send-code', [\App\Http\Controllers\AuthController::class, 'resetSendCode']);
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

    Route::get('profile', [\App\Http\Controllers\ProfileController::class, 'index']);
    Route::post('profile', [\App\Http\Controllers\ProfileController::class, 'update']);

    Route::prefix('seller')->group(function () {
        Route::apiResource('product', \App\Http\Controllers\Seller\ProductController::class)->except('store');
        Route::post('/products/stop', [\App\Http\Controllers\Seller\ProductController::class, 'stop']);
        Route::post('/products/archive', [\App\Http\Controllers\Seller\ProductController::class, 'archive']);
        Route::post('/products/duplicate', [\App\Http\Controllers\Seller\ProductController::class, 'duplicate']);
        // ПОЛЕ balance В ОБЪЯВЛЕНИИ1!!!!1!
        // todo ПРОДАВЕЦ СОЗДАЕТ ОБЪЯВЛЕНИЕ ЗНАЧИТ ДЕНЬГИ БУДУТ ЗАРЕЗЕРВИРОВАННЫ У НЕГО!
        Route::apiResource('ads', \App\Http\Controllers\Seller\AdsController::class);
        Route::post('/ads/stop', [\App\Http\Controllers\Seller\AdsController::class, 'stop']);
        Route::post('/ads/archive', [\App\Http\Controllers\Seller\AdsController::class, 'archive']);
        Route::post('/ads/duplicate', [\App\Http\Controllers\Seller\AdsController::class, 'duplicate']);
        Route::get('/tariff', [\App\Http\Controllers\Seller\TariffController::class, 'index']);
        Route::get('/tariff/{baybacks}', [\App\Http\Controllers\Seller\TariffController::class, 'show']);
        Route::post('/promocode/apply', [\App\Http\Controllers\Seller\PromocodeController::class, 'apply']);
        Route::get('/buybacks', [\App\Http\Controllers\Seller\BuybackController::class, 'index']);
    });

    // Корзина и избранное
    Route::post('/add-to-{type}', [\App\Http\Controllers\CartFavoriteController::class, 'add']);
    Route::get('/cart', [\App\Http\Controllers\CartFavoriteController::class, 'viewCart']);
    Route::get('/favorites', [\App\Http\Controllers\CartFavoriteController::class, 'viewFavorites']);
    Route::post('/remove-from-{type}', [\App\Http\Controllers\CartFavoriteController::class, 'remove']);

    Route::prefix('buyer')->group(function () {
        // todo при запуске  art websocket:serve ошибка!!!
        // todo проверить отправку сообщения при создании заказа и обычную отправку сообщения
        // todo ПРИ СОЗДАНИИ ВЫКУПА РЕЗЕРВИРУЕМ БАЛАНС У ЮЕЗРА!
        Route::post('/create-order/{ad_id}', [\App\Http\Controllers\Buyer\OrderController::class, 'store']);
        Route::get('/orders', [\App\Http\Controllers\Buyer\OrderController::class, 'index']);
        Route::get('/orders/{id}', [\App\Http\Controllers\Buyer\OrderController::class, 'show']);
        Route::post('/orders/{id}', [\App\Http\Controllers\Buyer\OrderController::class, 'send']);
    });
});

// SSE route
Route::get('/notifications/sse', [\App\Http\Controllers\SSEController::class, 'stream']);

// TODO на сегодня
/*
 * // Все проверяем с фигмой!
 * 7. Тесты обязательно
 */

//todo НА СЕГОДНЯ!!!!
/*
 * Сделать ИЗБРАНООЕ И КОРЗИНУ!!!!!!!!+++++
 * Сделать сторону покупателя (создание заказа и тд)+
 * сделать счетчик просмотров (через мидлвар)
 * СДЕЛАТЬ БАЛАНС (ДОСТУПНО К ВЫВОДУ И НАПОТВЕРЖДЕНИИ!!!!) у юзера и продавца
 * СДЕЛАТЬ СПИСОК ТРАНЗАКЦИЙ В ПРОФИЛЕ У ЮЗЕРА + ПРОВЕРИТЬ ФИГМУ ПРОДАВЦА ПРОФИЛЬ!
 * Платежка
 * Бот
 */
