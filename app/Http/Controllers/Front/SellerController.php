<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Buyback;
use App\Models\Review;
use App\Models\User;
use Carbon\Carbon;

class SellerController extends Controller
{
    public function show(string $id)
    {
        $user = User::findOrFail($id);
        if (! $user->isSeller()) {
            abort(404);
        }

        $successBuybacks = Buyback::leftJoin('ads', 'ads.id', '=', 'buybacks.ads_id')
            ->selectRaw('
        ROUND((SUM(CASE WHEN buybacks.status = "completed" THEN 1 ELSE 0 END) / COUNT(buybacks.id)) * 100, 1) as percentage
    ')
            ->where('ads.user_id', $user->id)
            ->first();

        $cashbackPaid = $user->buybacks()->sum('buybacks.price');

        $sellerRating = Review::where('reviewable_id', $user->id)
            ->where('reviewable_type', 'App\Models\User');

        $productRating = Review::join('products', 'products.id', '=', 'reviews.reviewable_id')
            ->where('products.shop_id', function ($query) use ($user) {
                return $query->select('id')
                    ->from('shops')
                    ->where('shops.user_id', $user->id)
                    ->limit(1);
            })
            ->where('reviews.reviewable_type', 'App\Models\Product');

        $userData                     = $user->toArray();
        $userData['success_buybacks'] = round($successBuybacks->percentage, 1); // Процент успешных выкупов
        $userData['seller_rating']    = round($sellerRating->avg('reviews.rating'), 1); // Рейтинг продавца
        $userData['product_rating']   = round($productRating->avg('reviews.rating'), 1); // Рейтинг товаров
        $userData['cashback_paid']    = round($cashbackPaid, 1); // Кол-во выплаченного кешбека
        $userData['total_reviews']    = round($productRating->count(), 1); // Кол-во оценок товаров
        $userData['products']         = $user->shop?->products; // Товары
        $userData['reviews_count']    = $user->reviews?->count(); // Кол-во отзывов
        // Получаем список адс и по ним отзывы
        $reviews = [];
        foreach ($user->ads as $ad) {
            foreach ($ad->reviews as $review) {
                $reviewArray = $review->toArray();
                // Форматируем дату
                $reviewArray['formatted_created_at'] = Carbon::parse($review['created_at'])->translatedFormat('j F, Y');
                $reviews[]                           = $reviewArray;

            }
        }
        $userData['reviews'] = $reviews; // Отзывы

        return $userData;
    }
}
