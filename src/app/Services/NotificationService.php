<?php

namespace App\Services;

use App\Models\Notification;
use Illuminate\Support\Facades\Redis;

class NotificationService
{

    public function send(string $user_id, string $buyback_id, string $text)
    {
        $notification = Notification::create([
            'user_id' => $user_id,
            'buyback_id' => $buyback_id,
            'text' => $text
        ]);

        Redis::rpush('user_notifications:' . $user_id, json_encode([
            'user_id' => $user_id,
            'type' => 'notification',
            'buyback_id' => $buyback_id,
            'text' => $text,
            'timestamp' => $notification->created_at->toIso8601String()
        ]));

        return true;
    }
}
