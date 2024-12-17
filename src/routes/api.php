<?php

use App\Http\Middleware\GuestOnly;
use Illuminate\Support\Facades\Route;

Route::middleware([GuestOnly::class])->group(function () {
    Route::post('register/send-code', [\App\Http\Controllers\AuthController::class, 'sendCode']);
    Route::post('register/verify-code', [\App\Http\Controllers\AuthController::class, 'verifyCode']);
    // Делаем восстановление пароля и логин!
});
Route::post('register/complete', [\App\Http\Controllers\AuthController::class, 'completeRegistration']);

// TODO на сегодня
/*
 * 1. Установить Pint+
 * 2. Сделать регистрацию и авторицию
 * 3. Сделать товары и категории
 * 4. Сделать объявления
 * 5. Сделать подгрузку магазина и товаров из ВБ
 * 6. Сделать импорт категорий из ВБ +
 * 7. Тесты обязательно
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
