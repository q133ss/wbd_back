<?php

namespace App\Services;

use App\Models\Buyback;
use App\Models\Message;
use Pusher\Pusher;

class SocketService
{
    public function send(Message $message, Buyback $buyback, bool $sendNotification = true)
    {
        // Создаем уведомление
        // todo тут наверное надо делать проверку, если в течении 5 минут покупатель не увидиил сообщение, то тогда отправлять!
        // проверка на is_read
        // а сам is_read делать в get messages
        // WebSocket-сообщение

        // Подгружаем все значения из БД
        $message->refresh();

        $data = [
            'buyback_id' => $message->buyback_id,
            'color' => $message->color,
            'created_at' => $message->created_at,
            'id' => $message->id,
            'is_read' => $message->is_read,
            'sender_id' => $message->sender_id,
            'system_type' => $message->system_type,
            'text' => $message->text,
            'type' => $message->type,
            'updated_at' => $message->updated_at
        ];

        $pusher = new Pusher(
            config('services.pusher.key'),
            config('services.pusher.secret'),
            config('services.pusher.id'),
            [
                'cluster' => config('services.pusher.cluster'),
                'useTLS'  => true,
            ]
        );

        $pusher->trigger('chat-'.$buyback->id, 'MessageSent', $data);

        // Отправка уведомления
        if ($sendNotification) {
            (new NotificationService)->send($buyback->user_id, $buyback->id, 'У вас новое сообщение по выкупу');
        }

        return true;
    }
}
