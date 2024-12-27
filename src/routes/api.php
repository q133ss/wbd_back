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
        Route::apiResource('ads', \App\Http\Controllers\Seller\AdsController::class);
        Route::get('/archive/ads', [\App\Http\Controllers\Seller\AdsController::class, 'archive']);
        Route::get('/buybacks/process', [\App\Http\Controllers\Seller\AdsController::class, 'process']); // Выкупы в процессе
        Route::get('/tariff', [\App\Http\Controllers\Seller\TariffController::class, 'index']);
        Route::get('/tariff/{baybacks}', [\App\Http\Controllers\Seller\TariffController::class, 'show']);
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
 * 9. Поддержка
 * 10. Список выкупов вывести для продавца
 */

//todo НА ЗАВТРА
/*
 * Сделать сторону покупателя (создание заказа и тд)
 * сделать счетчик просмотров (через мидлвар)
 * Чат
 * Уведомления
 * Платежка
 * Бот
 */
