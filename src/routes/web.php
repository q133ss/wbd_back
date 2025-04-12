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

Route::get('/qq', function(){
    \App\Models\Message::create([
        'text' => 'Привет!',
        'sender_id' => 1,
        'buyback_id' => 1
    ]);

    \App\Models\Message::create([
        'text' => 'Привет!!!',
        'sender_id' => 2,
        'buyback_id' => 1
    ]);
//    \App\Models\Buyback::create([
//        'ads_id' => 1,
//        'user_id' => 1,
//        'price' => 1
//    ]);
});
