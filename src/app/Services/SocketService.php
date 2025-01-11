<?php

namespace App\Services;

use App\Jobs\SendMessage;
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
        $data = [
            'type' => 'message', // Для сообщений
            'buyback_id' => $buyback->id,
            'buyer_id' => $buyback->user_id,
            'seller_id' => $message->sender_id,
            'message' => [
                'text' => $message->text,
                'sender_id' => $message->sender_id,
                'type' => $message->type,
                'color' => $message->color,
            ],
        ];

        SendMessage::dispatch($data);

        // Отправка уведомления
        if ($sendNotification) {
            $notification = [
                'type' => 'notification',
                'buyback_id' => $buyback->id,
                'text' => 'У вас новое сообщение по выкупу #' . $buyback->id
            ];
            // todo SSE тут
        }
        return true;
    }
}
