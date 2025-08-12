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
        $user = auth('sanctum')->user();
        $stats = $user->referralStat()->whereIn('type', ['telegram', 'site'])->get()->keyBy('type');

        $telegram = $stats->get('telegram');
        $site = $stats->get('site');

        return response()->json([
            'telegram' => [
                'clicks_count' => $telegram->clicks_count ?? 0,
                'registrations_count' => $telegram->registrations_count ?? 0,
            ],
            'site' => [
                'clicks_count' => $site->clicks_count ?? 0,
                'registrations_count' => $site->registrations_count ?? 0,
                'topup_count' => ($telegram->topup_count ?? 0) + ($site->topup_count ?? 0),
                'earnings' => ($telegram->earnings ?? 0) + ($site->earnings ?? 0),
            ],
        ]);
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
