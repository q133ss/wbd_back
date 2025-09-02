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

    # TODO реализовать каким-то образом авторизацию для продавцов! Например сформировтьа ссылку с токеном и отправвить на фронт ссылку
    Route::get('/user/{id}/login', [\App\Http\Controllers\Admin\SellerController::class, 'loginAs'])->name('loginAs');
    Route::get('/user/{id}/unfrozen', [\App\Http\Controllers\Admin\SellerController::class, 'unfrozen'])->name('user.unfrozen');
    Route::get('/user/{id}/frozen', [\App\Http\Controllers\Admin\SellerController::class, 'frozen'])->name('user.frozen');

    Route::get('/', [\App\Http\Controllers\Admin\IndexController::class, 'index'])->name('index');
    Route::get('/sellers', [\App\Http\Controllers\Admin\SellerController::class, 'index'])->name('sellers.index');
    Route::get('/sellers/{id}', [\App\Http\Controllers\Admin\SellerController::class, 'show'])->name('sellers.show');
    Route::delete('/sellers/{id}', [\App\Http\Controllers\Admin\SellerController::class, 'delete'])->name('sellers.destroy');
    Route::patch('/sellers/{id}', [\App\Http\Controllers\Admin\SellerController::class, 'update'])->name('sellers.update');

    Route::group(['prefix' => 'seller', 'as' => 'sellers.'], function () {
        Route::get('/payments', [\App\Http\Controllers\Admin\PaymentsController::class, 'index'])->name('payments.index');

        Route::get('/seller/products', [\App\Http\Controllers\Admin\SellerProductController::class, 'index'])->name('products.index');
        Route::delete('/seller/products/{id}', [\App\Http\Controllers\Admin\SellerProductController::class, 'delete'])->name('products.destroy');
        Route::get('/seller/ads', [\App\Http\Controllers\Admin\SellerAdController::class, 'index'])->name('ads.index');
        Route::delete('/seller/ads/{id}', [\App\Http\Controllers\Admin\SellerAdController::class, 'delete'])->name('ads.destroy');
        Route::get('/seller/buybacks', [\App\Http\Controllers\Admin\SellerBuybackController::class, 'index'])->name('buybacks.index');
    });

    Route::group(['prefix' => 'buyer', 'as' => 'buyer.'], function () {
        Route::get('/', [\App\Http\Controllers\Admin\BuyerController::class, 'index'])->name('index');
        Route::get('/{id}', [\App\Http\Controllers\Admin\BuyerController::class, 'show'])->name('show');
        Route::patch('/{id}', [\App\Http\Controllers\Admin\BuyerController::class, 'update'])->name('update');
        Route::delete('/{id}', [\App\Http\Controllers\Admin\BuyerController::class, 'destroy'])->name('destroy');
        Route::get('/buybacks/list', [\App\Http\Controllers\Admin\BuyerController::class, 'buybacks'])->name('buybacks.index');
    });

    Route::resource('roles', App\Http\Controllers\Admin\RoleController::class);
    Route::resource('promocodes', App\Http\Controllers\Admin\PromoCodeController::class);
    Route::get('/products', [App\Http\Controllers\Admin\ProductController::class, 'index'])->name('products.index');
});
