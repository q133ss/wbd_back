<?php

namespace App\Http\Controllers;

use App\Jobs\ReadNotificationsJob;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index()
    {
        $notifications = auth('sanctum')->user()->notifications->where('is_read', 0);
        ReadNotificationsJob::dispatch($notifications->pluck('id')->all())->delay(now()->addSeconds(5));
        return $notifications;
    }
}
