<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Review;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class BuyerController extends Controller
{
    public function show(string $id)
    {
        $user = User::findOrFail($id);

        // Получаем все выкупы пользователя
        $buybacks = $user->buybacks;

        // Общее количество выкупов
        $totalBuybacks = $buybacks->count();

        // Количество успешных выкупов
        $completedBuybacks = $buybacks->whereIn('status', ['cashback_received','completed'])->count();

        $averageResponseTime = DB::table('messages as m1')
            ->selectRaw('AVG(TIMESTAMPDIFF(SECOND, m1.created_at, m2.created_at)) as avg_response_time')
            ->join('messages as m2', function($join) use ($user) {
                $join->on('m1.buyback_id', '=', 'm2.buyback_id')
                    ->where('m2.sender_id', '=', $user->id)  // Ответ покупателя
                    ->where('m2.created_at', '>', DB::raw('m1.created_at'));
            })
            ->where('m1.sender_id', '!=', $user->id)
            ->whereIn('m1.buyback_id', function($query) use ($user) {
                $query->select('id')
                    ->from('buybacks')
                    ->where('user_id', $user->id);
            })
            ->first()
            ->avg_response_time ?? 0;

        // Процент успешных выкупов (если есть выкупы)
        $successPercentage = $totalBuybacks > 0
            ? round(($completedBuybacks / $totalBuybacks) * 100, 2)
            : 0;

        $userArr = $user->toArray();
        $userArr['reviews'] = Review::where('user_id', $user->id)->get();

        $userArr['average_response_time']   = round($averageResponseTime / 60, 1) ?? 0; // Среднее время ответа в минутах

        // Добавляем статистику по выкупам
        $userArr['buybacks_stats'] = [
            'total' => $totalBuybacks,
            'success_percentage' => $successPercentage
        ];

        return $userArr;
    }
}
