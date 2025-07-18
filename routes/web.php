<?php

use Illuminate\Support\Facades\Route;

Route::get('/login', function () {
    return view('admin.login');
})->name('login');
Route::post('/login', [\App\Http\Controllers\Admin\LoginController::class, 'login']);

Route::view('chat', 'chattest');
Route::middleware(['auth', 'is.admin'])->group(function () {
    Route::get('/', function () {
        return view('admin.index');
    })->name('index');

    Route::get('/sellers', [\App\Http\Controllers\Admin\SellerController::class, 'index'])->name('seller.index');
});

Route::prefix('telegram')->name('tg.')->group(function () {
    Route::get('/', [\App\Http\Controllers\TgApp\LoginController::class, 'index'])->name('main');
    // login
    Route::get('/login/select/{chat_id}', [\App\Http\Controllers\TgApp\LoginController::class, 'select'])->name('select');
    Route::get('/login/conditions/{role}/{chat_id}/{user_id}', [\App\Http\Controllers\TgApp\LoginController::class, 'conditions'])->where('role', 'seller|buyer')->name('conditions');
    Route::get('/login/get-contact/{role}/{chat_id}/{user_id}', [\App\Http\Controllers\TgApp\LoginController::class, 'getContact']);
    Route::get('/login/complete/{user_id}/{phone_number}/{role}/{chatId}/{first_name?}/{last_name?}', [\App\Http\Controllers\TgApp\LoginController::class, 'complete']);
    Route::post('/auth/complete1', [\App\Http\Controllers\TgApp\LoginController::class, 'completeSave'])->name('complete');
    // dashboard
    Route::get('/dashboard', [\App\Http\Controllers\TgApp\DashboardController::class, 'index'])->name('dashboard');
    // disable for seller!
    Route::view('/index', 'app.index')->name('index');
});

// TODO осталось сделать страницу продавца и покупателя и все! Проблема с авторизацией ушла!
