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

Route::prefix('telegram')->group(function () {
    // login
    Route::get('/login/select', [\App\Http\Controllers\TgApp\LoginController::class, 'select']);
    Route::get('/login/conditions', [\App\Http\Controllers\TgApp\LoginController::class, 'select']);
    Route::get('/login/get-contact', [\App\Http\Controllers\TgApp\LoginController::class, 'getContact']);
    Route::get('/login/complete', [\App\Http\Controllers\TgApp\LoginController::class, 'complete']);
    // dashboard
});
