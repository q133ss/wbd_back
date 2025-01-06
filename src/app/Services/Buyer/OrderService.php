<?php

namespace App\Services\Buyer;

use App\Jobs\OrderPendingCheck;
use App\Models\Ad;
use App\Models\Buyback;
use App\Models\Message;
use App\Services\BaseService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class OrderService extends BaseService
{
    public function createOrder(string $ad_id)
    {
        DB::beginTransaction();
        $ad = Ad::findOrFail($ad_id);
        try {
            $buyback = Buyback::create([
                'ads_id' => $ad_id,
                'user_id' => auth('sanctum')->id(),
                'status' => 'pending'
            ]);

            // Отправляем автоматическое сообщение от продавца
            Message::create([
                'text' => $ad->redemption_instructions,
                'sender_id' => $ad->user_id,
                'buyback_id' => $buyback->id,
                'type' => 'system',
                'color' => Message::VIOLET_COLOR
            ]);

            // Таймер
            OrderPendingCheck::dispatch($buyback->id)->delay(Carbon::now()->addMinutes(30));

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->sendError('Произошла ошибка, попробуйте еще раз', 500);
        }
    }
}
