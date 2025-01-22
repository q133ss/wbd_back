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
        header('X-Accel-Buffering: no'); // Для отключения буферизации в Nginx

        // Получаем идентификатор текущего пользователя
        $userId = auth('sanctum')->id();

        while (true) {
            // Получаем уведомления из Redis
            $notifications = Redis::lrange('user_notifications:' . $userId, 0, -1);

            // Если есть новые уведомления, отправляем их клиенту
            if (!empty($notifications)) {
                foreach ($notifications as $notification) {
                    echo "data: $notification\n\n";
                }

                // Удаляем уведомления из Redis после отправки
                Redis::del('user_notifications:' . $userId);
            }

            // Отправляем пустое событие для поддержания соединения
            echo ":\n\n";
            ob_flush();
            flush();

            // Задержка перед следующей проверкой
            sleep(1);
        }
    }
}
