<?php

namespace App\Http\Controllers;

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

    public function getTelegramLink()
    {
        $user = auth('sanctum')->user();
        $telegramService = app(TelegramService::class);
        return response()->json([
            'link' => $telegramService->generateAuthLink($user)
        ]);
    }
}
