<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Review;
use App\Models\User;

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
        $completedBuybacks = $buybacks->where('status', 'completed')->count();

        // Процент успешных выкупов (если есть выкупы)
        $successPercentage = $totalBuybacks > 0
            ? round(($completedBuybacks / $totalBuybacks) * 100, 2)
            : 0;

        $userArr = $user->toArray();
        $userArr['reviews'] = Review::where('user_id', $user->id)->get();

        // Добавляем статистику по выкупам
        $userArr['buybacks_stats'] = [
            'total' => $totalBuybacks,
            'success_percentage' => $successPercentage
        ];

        return $userArr;
    }
}
