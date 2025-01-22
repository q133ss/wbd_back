<?php

namespace App\Http\Controllers;

use App\Models\Buyback;
use App\Models\Message;
use App\Services\SocketService;
use Illuminate\Http\Request;

class ChatController extends Controller
{
    public function messages(string $buyback_id)
    {
        $buyback = Buyback::findOrFail($buyback_id);

        $user = auth('sanctum')->user();
        $user->checkBuyback($buyback);

        return Message::with('file')->where('buyback_id', $buyback_id)->get();
    }
    public function send(string $buyback_id)
    {
        $buyback = Buyback::findOrFail($buyback_id);
        auth('sanctum')->user()->checkBuyback($buyback);

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

    public function cancel(string $id)
    {
        $user = auth('sanctum')->user();

        $buyback = Buyback::findOrFail($id);
        $user->checkBuyback($buyback);
        if($buyback->status == 'cancelled'){
            abort(403, 'Заказ уже отменен');
        }
        $buyback->update(['status' => 'cancelled']);
        $isSeller = $user->isSeller();
        $text = '';
        if($isSeller){
            $text = 'Выкуп отменен по инициативе продавца';
        }else{
            $text = 'Выкуп отменен по инициативе покупателя';
        }
        $message = Message::create([
            'buyback_id' => $id,
            'sender_id' => $user->id,
            'text' => $text,
            'type' => 'system',
            'system_type' => 'cancel'
        ]);
        return response()->json([
            'message' => $message
        ], 201);
    }
}
