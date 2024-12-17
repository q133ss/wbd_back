<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::group(['guest'],function(){
    /*
     * 1) Сначала юзер вводит номер телефона, затем ему отправляется код
        2) Затем проверяем код, если все ок, то создаем юзера и указываем ему "is_configurated = 0"
        3) Далее пользователь вводит логин, пароль и email и делаем ему is_configurated = 1 и завершаем регистрацию
     */
    Route::post('register/send-code', [\App\Http\Controllers\AuthController::class, 'sendCode']);
    Route::post('register/verify-code', [RegistrationController::class, 'verifyCode']);
    Route::post('register/complete', [RegistrationController::class, 'completeRegistration']);
});

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
