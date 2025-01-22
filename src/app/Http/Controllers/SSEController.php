<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class SSEController extends Controller
{
    public function stream(Request $request)
    {
        // Устанавливаем заголовки для SSE
        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');
        header('Connection: keep-alive');

        // Получаем идентификатор текущего пользователя
        $userId = auth('sanctum')->id();

        // Подписываемся на канал Redis для этого пользователя
        Redis::connection()->subscribe(['user_notifications:' . $userId], function ($message) {
            echo "data: $message\n\n";
            ob_flush();
            flush();
        });
    }
}
