<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Buyback;
use App\Models\Review;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class SellerController extends Controller
{
    public function show(string $id)
    {
        $user = User::findOrFail($id);
        if (! $user->isSeller()) {
            abort(404);
        }

        $completedBuybacks = $user->buybacks?->whereIn('status', ['cashback_received', 'completed']);

        $successBuybacks = Buyback::leftJoin('ads', 'ads.id', '=', 'buybacks.ads_id')
            ->selectRaw('ROUND((SUM(CASE WHEN buybacks.status IN ("cashback_received", "completed") THEN 1 ELSE 0 END) / COUNT(buybacks.id)) * 100, 1) as percentage')
            ->where('ads.user_id', $user->id)
            ->first();

        $cashbackPaid = $completedBuybacks->sum(function ($buyback) {
            return $buyback->product_price - $buyback->price_with_cashback;
        });

        $userRating = Review::where('reviews.reviewable_type', 'App\Models\User')
            ->where('reviews.reviewable_id', $user->id)
            ->selectRaw('AVG(reviews.rating) as rating')
            ->first()->rating ?? 0;

        $averageResponseTime = DB::table('messages as m1')
            ->selectRaw('AVG(TIMESTAMPDIFF(SECOND, m1.created_at, m2.created_at)) as avg_response_time')
            ->join('messages as m2', function($join) use ($user) {
                $join->on('m1.buyback_id', '=', 'm2.buyback_id')
                    ->where('m2.sender_id', '=', $user->id)  // Ответ продавца
                    ->where('m2.created_at', '>', DB::raw('m1.created_at'));
            })
            ->where('m1.sender_id', '!=', $user->id)
            ->whereIn('m1.buyback_id', function($query) use ($user) {
                $query->select('buybacks.id')
                    ->from('buybacks')
                    ->join('ads', 'ads.id', '=', 'buybacks.ads_id')
                    ->where('ads.user_id', $user->id);
            })
            ->first()
            ->avg_response_time ?? 0;

        $userData                     = $user->toArray();


        $userData['success_buybacks'] = round($successBuybacks->percentage, 1); // Процент успешных выкупов
        $userData['user_rating']    = round($userRating, 1); // Рейтинг продавца
        $userData['cashback_paid']    = round($cashbackPaid, 1); // Кол-во выплаченного кешбека
        $userData['average_response_time']   = round($averageResponseTime / 60, 1) ?? 0; // Среднее время ответа в минутах


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
