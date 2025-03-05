<?php

namespace App\Http\Controllers;

use App\Models\ReferralStat;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class ReferralController extends Controller
{
    public function index()
    {
        $stat = auth('sanctum')->user()->referralStat;
        $statistic = [
            'clicks_count' => $stat->clicks_count ?? 0,
            'registrations_count' => $stat->registrations_count ?? 0,
            'topup_count' => $stat->topup_count ?? 0
        ];
        return response()->json($statistic);
    }
    public function store(Request $request, string $id): \Illuminate\Http\JsonResponse
    {
        // Обновляем или создаем запись в таблице referral_stats
        if(User::where('id', $id)->exists()) {
            $ip = $request->header('X-Forwarded-For') ?? $request->ip(); // Получаем IP-адрес пользователя
            ReferralStat::updateOrCreate(
                ['user_id' => $id]
            )->increment('clicks_count'); // Увеличиваем счетчик clicks_count
            // Сохраняем ref в кеш на 24 часа
            Cache::put("ref_{$ip}", $id, now()->addDay());
        }

        return response()->json(['message' => 'true']);
    }
}
