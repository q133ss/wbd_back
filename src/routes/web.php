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

Route::view('/sse', 'sse');
