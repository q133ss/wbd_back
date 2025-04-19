<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Http\Resources\BuybackController\ShowResource;
use App\Models\Buyback;
use App\Models\Message;
use Illuminate\Http\Request;

class BuybackController extends Controller
{
    public function index(Request $request)
    {
        $user     = auth('sanctum')->user();
        $buybacks = $user->buybacks()->withFilter($request)->get();

        return response()->json($buybacks);
    }

    public function show(string $id)
    {
        $userId = auth('sanctum')->id();

        $buyback = Buyback::with(['messages', 'user', 'ad' => function($query) {
            $query->without('reviews');
        }])
            ->leftJoin('users', 'buybacks.user_id', '=', 'users.id')
            ->leftJoin('ads', 'buybacks.ads_id', '=', 'ads.id')
            ->where('buybacks.id', $id)
            ->where(function ($query) use ($userId) {
                $query->where('buybacks.user_id', $userId)
                    ->orWhere('ads.user_id', $userId);
            })
            ->select('buybacks.*')
            ->first();

        if ($buyback === null) {
            abort(404);
        }

        // Определяем ID второй стороны
        $adUserId = $buyback->ad?->user_id;
        $counterpartyId = ($userId == $buyback->user_id) ? $adUserId : $buyback->user_id;

        Message::where('buyback_id', $buyback->id)
            ->where('sender_id', $counterpartyId) // Сообщения от противоположной стороны
            ->where('is_read', false)
            ->update(['is_read' => true]);

        // Добавляем whoSend к каждому сообщению
        $buyback->messages->each(function ($message) use ($buyback) {
            $adUserId = $buyback->ad?->user_id;
            $isBuyer = $buyback->user_id == $adUserId;
            $message->whoSend = ($message->sender_id == $buyback->user_id) == $isBuyer ? 'buyer' : 'seller';
        });

        return new ShowResource($buyback);
    }
}
