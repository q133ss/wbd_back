<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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

        // Последняя метка времени для проверки новых данных
        $lastEventId = $request->header('Last-Event-ID', now()->subMinute()->toDateTimeString());

        while (true) {
            // Проверяем новые уведомления из базы данных
            $notifications = DB::table('notifications')
                ->where('user_id', $userId)
                ->where('created_at', '>', $lastEventId)
                ->get();

            // Если есть новые уведомления, отправляем их клиенту
            if ($notifications->isNotEmpty()) {
                foreach ($notifications as $notification) {
                    echo "id: {$notification->id}\n";
                    echo "data: " . json_encode([
                            'type' => 'notification',
                            'text' => $notification->text,
                            'timestamp' => $notification->created_at,
                        ]) . "\n\n";

                    // Обновляем метку времени
                    $lastEventId = $notification->created_at;
                }
                ob_flush();
                flush();
            }

            // Задержка перед следующей проверкой (1 секунда)
            sleep(1);
        }
    }
}
