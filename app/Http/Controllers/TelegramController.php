<?php

namespace App\Http\Controllers;

use App\Http\Requests\TelegramController\RegisterRequest;
use App\Models\Role;
use App\Models\User;
use App\Services\TelegramService;
use Illuminate\Http\Request;

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
        $user = User::firstOrCreate(
            [
                'telegram_id' => $request->get('telegram_id') // Уникальное поле для поиска
            ],
            [
                'phone' => $request->get('phone'),
                'role_id' => Role::where('slug', $request->get('role'))->first()->id,
                'name' => $request->get('first_name') . ' ' . $request->get('last_name'),
                'password' => '-'
            ]
        );
        $token = $user->createToken('web');
        return response()->json([
            'user' => $user,
            'token' => $token->plainTextToken,
        ]);
    }
}
