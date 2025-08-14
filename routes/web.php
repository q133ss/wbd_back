<?php

use Illuminate\Support\Facades\Route;

Route::get('/login', function () {
    return view('admin.login');
})->name('login');
Route::post('/login', [\App\Http\Controllers\Admin\LoginController::class, 'login']);

Route::get('/logout', function (){
    auth()->logout();
    return redirect()->route('login');
})->name('logout');

Route::view('chat', 'chattest');
Route::middleware(['auth', 'is.admin'])->name('admin.')->group(function () {
    Route::get('/', [\App\Http\Controllers\Admin\IndexController::class, 'index'])->name('index');
    Route::get('/sellers', [\App\Http\Controllers\Admin\SellerController::class, 'index'])->name('sellers.index');
});
