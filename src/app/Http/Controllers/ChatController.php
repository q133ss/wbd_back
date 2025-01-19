<?php

namespace App\Http\Controllers;

use App\Models\Buyback;
use App\Models\Message;
use App\Services\SocketService;
use Illuminate\Http\Request;

class ChatController extends Controller
{
    public function send(string $buyback_id)
    {
        $buyback = Buyback::findOrFail($buyback_id);
        $message = Message::create([
            'buyback_id' => $buyback_id,
            'sender_id' => auth('sanctum')->user()->id,
            'text' => 'Привет мир!'
        ]);
        $msg = (new SocketService())->send($message, $buyback);
        if($msg){
            return response()->json(['success' => true]);
        }
        return response()->json(['success' => false]);
    }
}
