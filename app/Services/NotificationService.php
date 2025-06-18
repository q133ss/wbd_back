<?php

namespace App\Services;

use App\Models\Notification;
use Illuminate\Support\Facades\Redis;
use Pusher\Pusher;

class NotificationService
{
    public function send(string $user_id, string $buyback_id, string $text, $sendTelegram = false)
    {
        $notification = Notification::create([
            'user_id'    => $user_id,
            'buyback_id' => $buyback_id,
            'text'       => $text,
        ]);

        $data = [
            'id' => $notification->id,
            'title' => 'Уведомление',
            'subtitle' => $notification->text,
            'date' => $notification->created_at?->toIso8601String()
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

        $pusher->trigger('notification-'.$user_id, 'MessageSent', $data);

        // Отправка уведомления в телеграм
        if($sendTelegram){
            (new TelegramService())->sendNotification($user_id, $text);
        }

        return true;
    }
}
