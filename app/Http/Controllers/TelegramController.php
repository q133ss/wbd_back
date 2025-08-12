<?php

namespace App\Http\Controllers;

use App\Http\Requests\TelegramController\RegisterRequest;
use App\Models\ReferralStat;
use App\Models\Role;
use App\Models\User;
use App\Services\TelegramService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class TelegramController extends Controller
{
    public function handle(Request $request)
    {
        // Получаем данные из запроса
        $update = json_decode($request->getContent(), true);

        (new TelegramService())->handleWebhook($update);

        return response('OK', 200);
    }

    public function handleClient(Request $request)
    {
        // Получаем данные из запроса
        $update = json_decode($request->getContent(), true);

        (new TelegramService())->handleWebhookClient($update);

        return response('OK', 200);
    }

    public function getTelegramLink()
    {
        $user = auth('sanctum')->user();
        $telegramService = app(TelegramService::class);
        return response()->json([
            'link' => $telegramService->generateAuthLink($user)
        ]);
    }

    public function policy(string $chat_id)
    {
        $service = new TelegramService();

        $service->sendFile($chat_id, base_path('public/conditions.docx'));
        $service->sendFile($chat_id, base_path('public/policy.docx'));

        return response('OK', 200);
    }

    public function register(RegisterRequest $request)
    {
        $telegramId = $request->get('telegram_id');
        $roleSlug = $request->get('role');

        $user = User::firstOrCreate(
            [
                'telegram_id' => $telegramId
            ],
            [
                'phone' => $request->get('phone'),
                'role_id' => Role::where('slug', $roleSlug)->first()->id,
                'name' => $request->get('first_name') . ' ' . $request->get('last_name'),
                'password' => '-'
            ]
        );

        // стата: проверяем кеш по telegram_id
        $refUserId = Cache::pull("ref_tg_{$telegramId}");
        if ($refUserId) {
            // Тип статистики в зависимости от роли
            ReferralStat::updateOrCreate(
                ['user_id' => $refUserId, 'type' => 'telegram'],
                ['registrations_count' => DB::raw('registrations_count + 1')]
            );
        }

        $token = $user->createToken('web');

        return response()->json([
            'user' => $user,
            'token' => $token->plainTextToken,
        ]);
    }

}
