<?php

namespace App\Services;

use App\Models\Buyback;
use App\Models\Message;
use App\Models\Notification;
use Workerman\Worker;
use Workerman\Connection\AsyncTcpConnection;

class SocketService
{
    public function send(Message $message, Buyback $buyback, bool $sendNotification = true)
    {
        // Создаем уведомление
        Notification::create([
            'user_id' => $buyback->user_id, // Покупатель
            'buyback_id' => $buyback->id,
            'text' => 'У вас новое сообщение по выкупу',
        ]);

        // WebSocket-сообщение
        $ws_url = "ws://wbd_websocket:8088";
        $ws_connection = new AsyncTcpConnection($ws_url);

        if ($ws_connection->getStatus() !== 0) {
            \Log::error('WebSocket connection is not in a valid state');
            return false;
        }
        \Log::info('WebSocket status: ' . $ws_connection->getStatus());

        $ws_connection->onError = function ($connection, $code, $msg) {
            \Log::error('WebSocket error: ' . $code . ' - ' . $msg);
        };

        $ws_connection->onConnect = function ($connection) use ($message, $buyback, $sendNotification) {
            if ($connection !== null) {
                $data = [
                    'type' => 'message', // Для сообщений
                    'buyback_id' => $buyback->id,
                    'recipients' => [
                        $buyback->user_id, // Покупатель
                        $message->sender_id, // Продавец
                    ],
                    'message' => [
                        'text' => $message->text,
                        'sender_id' => $message->sender_id,
                        'type' => $message->type,
                        'color' => $message->color,
                    ],
                ];

                // Отправка уведомления
                if ($sendNotification) {
                    $notification = [
                        'type' => 'notification',
                        'buyback_id' => $buyback->id,
                        'text' => 'У вас новое сообщение по выкупу #' . $buyback->id
                    ];
                    $connection->send(json_encode($notification));
                }

                $connection->send(json_encode($data));
                $connection->close();
            }else{
                \Log::error('WebSocket connection is null| Нужно запустить сервер');
            }
        };

        $ws_connection->connect();
        return true;
    }
}
