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
        $data = [
            'type'       => 'message', // Для сообщений
            'buyback_id' => $buyback->id,
            'buyer_id'   => $buyback->user_id,
            'seller_id'  => $message->sender_id,
            'message'    => [
                'text'      => $message->text,
                'sender_id' => $message->sender_id,
                'type'      => $message->type,
                'color'     => $message->color,
                'files'     => $message->files->pluck('src')->all(),
                'file_type' => $message->system_type,
            ],
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
