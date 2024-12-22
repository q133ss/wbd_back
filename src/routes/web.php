<?php

use Illuminate\Support\Facades\Route;

Route::get('/login', function () {
    return view('admin.login');
})->name('login');
Route::post('/login', [\App\Http\Controllers\Admin\LoginController::class, 'login']);

Route::middleware(['auth','is.admin'])->group(function () {
    Route::get('/', function (){
        return 'Ты в админке!';
    })->name('index');
});
