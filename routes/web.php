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
    Route::get('/sellers/{id}', [\App\Http\Controllers\Admin\SellerController::class, 'show'])->name('sellers.show');
    # TODO реализовать каким-то образом авторизацию для продавцов! Например сформировтьа ссылку с токеном и отправвить на фронт ссылку
    Route::get('/sellers/{id}/login', [\App\Http\Controllers\Admin\SellerController::class, 'loginAs'])->name('sellers.loginAs');
    Route::delete('/sellers/{id}', [\App\Http\Controllers\Admin\SellerController::class, 'delete'])->name('sellers.destroy');

    Route::group(['prefix' => 'seller', 'as' => 'sellers.'], function () {
        Route::get('/payments', [\App\Http\Controllers\Admin\PaymentsController::class, 'index'])->name('payments.index');

        Route::get('/seller/products', [\App\Http\Controllers\Admin\SellerProductController::class, 'index'])->name('products.index');
        # TODO
        Route::delete('/seller/products/{id}', [\App\Http\Controllers\Admin\SellerProductController::class, 'delete'])->name('products.destroy');
        Route::get('/seller/ads', [\App\Http\Controllers\Admin\SellerAdController::class, 'index'])->name('ads.index');
        Route::get('/seller/buybacks', [\App\Http\Controllers\Admin\SellerBuybackController::class, 'index'])->name('buybacks.index');
    });
});
