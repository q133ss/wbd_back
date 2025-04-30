<?php

use Illuminate\Support\Facades\Broadcast;
Broadcast::channel('chat-{id}', function ($user, $id) {
    return true;
    //return $user->checkBuyback($id);
});

Broadcast::channel('notification-{user_id}', function ($user, $id) {
    return true;
    //return $user->checkBuyback($id);
});

