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

Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/wb/fetch-product/{product_id}', [\App\Http\Controllers\WB\ProductController::class, 'fetchProduct']);
    Route::post('/wb/add-product/{product_id}', [\App\Http\Controllers\WB\ProductController::class, 'addProduct']);

    Route::prefix('seller')->group(function () {
        Route::apiResource('product', \App\Http\Controllers\Seller\ProductController::class)->except('store');
        Route::post('/products/stop', [\App\Http\Controllers\Seller\ProductController::class, 'stop']);
        Route::post('/products/archive', [\App\Http\Controllers\Seller\ProductController::class, 'archive']);
        Route::post('/products/duplicate', [\App\Http\Controllers\Seller\ProductController::class, 'duplicate']);
        Route::apiResource('ads', \App\Http\Controllers\Seller\AdsController::class);
        Route::post('/ads/stop', [\App\Http\Controllers\Seller\AdsController::class, 'stop']);
        Route::post('/ads/archive', [\App\Http\Controllers\Seller\AdsController::class, 'archive']);
        Route::post('/ads/duplicate', [\App\Http\Controllers\Seller\AdsController::class, 'duplicate']);
        Route::get('/tariff', [\App\Http\Controllers\Seller\TariffController::class, 'index']);
        Route::get('/tariff/{baybacks}', [\App\Http\Controllers\Seller\TariffController::class, 'show']);
        Route::post('/promocode/apply', [\App\Http\Controllers\Seller\PromocodeController::class, 'apply']);
    });
});

// TODO на сегодня
/*
 * // Все проверяем с фигмой!
 * 1. Установить Pint+
 * 2. Сделать регистрацию и авторицию+
 * 3. Сделать товары и категории ++
 * 4. Сделать объявления ++
 * 5. Сделать подгрузку магазина и товаров из ВБ+
 * 6. Сделать импорт категорий из ВБ +
 * 7. Тесты обязательно
 * 8. Промокоды (Название, дата начала и конца, кол-во выкупов, кол-во активаций, список, кто воспользовался)
 * 10. Список выкупов вывести для продавца! (Buybacks) и можно потом чат делать
 */

//todo НА ЗАВТРА
// todo ПРИ СОЗДАНИИ ВЫКУПА РЕЗЕРВИРУЕМ БАЛАНС У ЮЕЗРА!
// ПОЛЕ balance В ОБЪЯВЛЕНИИ1!!!!1!
/*
 * Сделать сторону покупателя (создание заказа и тд)
 * сделать счетчик просмотров (через мидлвар)
 * Чат
 * Уведомления
 * Платежка
 * Бот
 */
