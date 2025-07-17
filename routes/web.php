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
    Route::get('/', [\App\Http\Controllers\TgApp\LoginController::class, 'index'])->name('index');
    // login
    Route::get('/login/select/{chat_id}', [\App\Http\Controllers\TgApp\LoginController::class, 'select'])->name('select');
    Route::get('/login/conditions/{role}/{chat_id}', [\App\Http\Controllers\TgApp\LoginController::class, 'conditions'])->where('role', 'seller|buyer')->name('conditions');
    Route::get('/login/get-contact', [\App\Http\Controllers\TgApp\LoginController::class, 'getContact']);
    Route::get('/login/complete', [\App\Http\Controllers\TgApp\LoginController::class, 'complete']);
    // dashboard
});


