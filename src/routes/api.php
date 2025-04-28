<?php

use App\Http\Middleware\GuestOnly;
use Illuminate\Support\Facades\Route;
Route::middleware(['auth:sanctum'])->group(function () {
    Route::prefix('seller')->group(function () {
        Route::apiResource('products', \App\Http\Controllers\Seller\ProductController::class)->except('show');
        Route::post('/products/stop', [\App\Http\Controllers\Seller\ProductController::class, 'startStop']);
        Route::post('/products/archive', [\App\Http\Controllers\Seller\ProductController::class, 'archive']);
        Route::post('/products/duplicate', [\App\Http\Controllers\Seller\ProductController::class, 'duplicate']);
        Route::apiResource('ads', \App\Http\Controllers\Seller\AdsController::class);
        Route::post('/ads/stop', [\App\Http\Controllers\Seller\AdsController::class, 'startStop']);
        Route::post('/ads/archive', [\App\Http\Controllers\Seller\AdsController::class, 'archive']);
        Route::post('/ads/duplicate', [\App\Http\Controllers\Seller\AdsController::class, 'duplicate']);
        Route::get('/tariff/list', [\App\Http\Controllers\Seller\TariffController::class, 'index']);
        Route::get('/tariff/get-by-id/{id}', [\App\Http\Controllers\Seller\TariffController::class, 'detail']);
        Route::get('/tariff/{baybacks}', [\App\Http\Controllers\Seller\TariffController::class, 'show']);
        Route::post('/promocode/apply', [\App\Http\Controllers\Seller\PromocodeController::class, 'apply']);
        Route::get('/buybacks', [\App\Http\Controllers\Seller\BuybackController::class, 'index']);
        Route::get('/buybacks/{id}', [\App\Http\Controllers\Seller\BuybackController::class, 'show']);
        Route::post('/chat/{buyback}/file/{file}/approve', [\App\Http\Controllers\ChatController::class, 'fileApprove']);
        Route::post('/chat/{buyback}/file/{file}/reject', [\App\Http\Controllers\ChatController::class, 'fileReject']);
        Route::post('/chat/{buyback}/complete', [\App\Http\Controllers\ChatController::class, 'complete']);
    });

    Route::get('/chat-list', [\App\Http\Controllers\ChatController::class, 'list']);
    Route::get('/notifications', [\App\Http\Controllers\NotificationController::class, 'index']);
});

Route::middleware([GuestOnly::class])->group(function () {
    Route::post('register/send-code', [\App\Http\Controllers\AuthController::class, 'sendCode']);
    Route::post('register/verify-code', [\App\Http\Controllers\AuthController::class, 'verifyCode']);
    Route::post('password/reset/send-code', [\App\Http\Controllers\AuthController::class, 'resetSendCode']);
    Route::post('password/reset/check-code', [\App\Http\Controllers\AuthController::class, 'resetVerifyCode']);
    Route::post('password/reset', [\App\Http\Controllers\AuthController::class, 'reset']);
    Route::post('login', [\App\Http\Controllers\AuthController::class, 'login']);
});
Route::get('/roles', [\App\Http\Controllers\AuthController::class, 'roles']);
Route::post('register/complete', [\App\Http\Controllers\AuthController::class, 'completeRegistration']);

// Товары
Route::get('/products', [\App\Http\Controllers\Front\ProductController::class, 'index']);
Route::get('/product/{id}', [\App\Http\Controllers\Front\ProductController::class, 'show']);
Route::get('/product/{id}/feedbacks/{page}', [\App\Http\Controllers\Front\ProductController::class, 'showFeedbacks']);
Route::get('/product/related/{id}', [\App\Http\Controllers\Front\ProductController::class, 'related']);

// Категории
Route::get('/category', [\App\Http\Controllers\CategoryController::class, 'index']);
Route::get('/sub-category/{id}', [\App\Http\Controllers\CategoryController::class, 'indexSubCategory']);

Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/wb/fetch-product/{product_id}', [\App\Http\Controllers\WB\ProductController::class, 'fetchProduct']);
    Route::post('/wb/add-product/{product_id}', [\App\Http\Controllers\WB\ProductController::class, 'addProduct']);
    Route::get('/wb/product/{product_id}', [\App\Http\Controllers\WB\ProductController::class, 'getProduct']);

    // Профиль
    Route::get('profile', [\App\Http\Controllers\ProfileController::class, 'index']);
    Route::post('profile', [\App\Http\Controllers\ProfileController::class, 'update']);
    Route::post('/profile/avatar', [\App\Http\Controllers\ProfileController::class, 'avatar']);
    Route::get('transactions', [\App\Http\Controllers\ProfileController::class, 'transactions']);
    Route::get('/balance', [\App\Http\Controllers\ProfileController::class, 'balance']);
    Route::post('/balance', [\App\Http\Controllers\ProfileController::class, 'topup']);
    Route::post('/balance/buybacks', [\App\Http\Controllers\ProfileController::class, 'topupBuybacks']);
    Route::post('/withdraw', [\App\Http\Controllers\ProfileController::class, 'withdraw']);
    Route::get('/withdraws', [\App\Http\Controllers\ProfileController::class, 'withdraws']);
    Route::post('/withdraw/{id}', [\App\Http\Controllers\ProfileController::class, 'withdrawCancel']);
    Route::get('/profile/statistic', [\App\Http\Controllers\ProfileController::class, 'statistic']);
    Route::get('/referral', [\App\Http\Controllers\ReferralController::class, 'index']);

    // Корзина и избранное
    Route::post('/add-to-{type}', [\App\Http\Controllers\CartFavoriteController::class, 'add']);
    Route::get('/cart', [\App\Http\Controllers\CartFavoriteController::class, 'viewCart']);
    Route::get('/favorites', [\App\Http\Controllers\CartFavoriteController::class, 'viewFavorites']);
    Route::post('/remove-from-{type}', [\App\Http\Controllers\CartFavoriteController::class, 'remove']);

    Route::prefix('buyer')->group(function () {
        Route::post('/create-order/{ad_id}', [\App\Http\Controllers\Buyer\OrderController::class, 'store']);
        Route::get('/orders', [\App\Http\Controllers\Buyer\OrderController::class, 'index']);
        Route::get('/orders/{id}', [\App\Http\Controllers\Buyer\OrderController::class, 'show']);
        Route::post('/orders/{id}', [\App\Http\Controllers\Buyer\OrderController::class, 'send']);
    });

    // Список товаров и объявлений для фильтрации в транзакциях
    Route::get('{type}/transactions-list', [\App\Http\Controllers\Seller\ProductController::class, 'list'])->where('type', 'ads|products');;

    // Чат
    Route::get('/chat/status-list', [\App\Http\Controllers\Buyer\OrderController::class, 'orderStatusList']);
    Route::post('/chat/{buyback_id}/send', [\App\Http\Controllers\ChatController::class, 'send']);
    Route::get('/messages/{buyback_id}', [\App\Http\Controllers\ChatController::class, 'messages']);
    Route::post('/buyback/{id}/cancel', [\App\Http\Controllers\ChatController::class, 'cancel']);
    Route::post('/chat/{id}/photo', [\App\Http\Controllers\ChatController::class, 'photo']);
    Route::post('/chat/{id}/review', [\App\Http\Controllers\ChatController::class, 'review']);
    // Шаблоны
    Route::apiResource('template', \App\Http\Controllers\TemplateController::class)->except('store', 'destroy');

    // Ссылка на ТГ бота
    Route::get('/get-telegram-link', [\App\Http\Controllers\TelegramController::class, 'getTelegramLink']);

    Route::post('/role-switch', [\App\Http\Controllers\SwitchController::class, 'switch']);
});

// Профиль продавца
Route::get('/seller/{id}', [\App\Http\Controllers\Front\SellerController::class, 'show']);
Route::get('/buyer/{id}', [\App\Http\Controllers\Front\BuyerController::class, 'show']);

// SSE route
Route::get('/notifications/sse', [\App\Http\Controllers\SSEController::class, 'stream']);


// Telegram webhook
Route::post('/telegram/webhook', [\App\Http\Controllers\TelegramController::class, 'handle']);

// Реферальная ссылка
Route::post('/referral/{id}', [\App\Http\Controllers\ReferralController::class, 'store']);

# TODO это для тестов, нужно убрать и перенести в крон!
Route::get('/sitemap-generate', [\App\Http\Controllers\SitemapController::class, 'generate']);
Route::get('/sitemap.xml', [\App\Http\Controllers\SitemapController::class, 'show']);
