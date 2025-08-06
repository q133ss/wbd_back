<?php

namespace App\Services\Buyer;

use App\Jobs\OrderPendingCheck;
use App\Models\Ad;
use App\Models\Buyback;
use App\Models\Message;
use App\Services\BaseService;
use App\Services\NotificationService;
use App\Services\SocketService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class OrderService extends BaseService
{
    public function createOrder(string $ad_id)
    {
        $payMethod = auth('sanctum')->user()->paymentMethod;
        if($payMethod == null){
            abort(400, 'У вас не указаны платежные данные. Укажите данные в настройках профиля.');
        }

        DB::beginTransaction();
        $ad = Ad::findOrFail($ad_id);
        if ($ad->user_id == auth('sanctum')->id()) {
            abort(403, 'Вы не можете купить товар у самого себя');
        }

        $hasBuyback = Buyback::where('ads_id', $ad_id)
            ->where('user_id', auth('sanctum')->id())
            ->whereNotIn('status', ['cancelled', 'order_expired', 'archive'])
            ->exists();

        if($hasBuyback) {
            abort(403, 'У вас уже есть активный выкуп по этому объявлению');
        }

        try {
            $buybackData = [
                'ads_id'  => $ad_id,
                'user_id' => auth('sanctum')->id(),
                'status'  => 'pending',
                'product_price'   => $ad->product?->price,
                'cashback_percentage' => $ad->cashback_percentage,
                'price_with_cashback' => $ad->price_with_cashback
            ];

            if(!empty($ad->keywords)){
                // 1. Выбрать случайное ключевое слово
                $randomKeyword = collect($ad->keywords)->random();
                $word = $randomKeyword['word'];

                // 2. Сформировать ссылку поиска на Wildberries
                $encodedWord = urlencode($word);
                $searchLink = "https://www.wildberries.ru/catalog/0/search.aspx?search={$encodedWord}";

                // 3. Заменить плейсхолдеры в инструкции
                $ad->redemption_instructions = str_replace(
                    ['{word}', '{search_link}'],
                    [$word, $searchLink],
                    $ad->redemption_instructions
                );

                $buybackData['keyword'] = $word; // Сохраняем выбранное ключевое слово
            }

            $buyback = Buyback::create($buybackData);

            // Плашка "у покупателя есть 30 мин.." делается на фронте по статусу заказа!
            // В зависимости от статуса меняется текст

            // Отправляем автоматическое сообщение от продавца
            $message = Message::create([
                'text'       => $ad->redemption_instructions,
                'sender_id'  => $ad->user_id,
                'buyback_id' => $buyback->id,
                'type'       => 'text',
                'color'      => Message::VIOLET_COLOR,
            ]);

            // Отправляем сообщение по веб сокетам покупателю
            (new SocketService)->send($message, $buyback, false);
            (new NotificationService())->send($ad->user_id,$buyback->id, 'Новый выкуп по объявлению #'.$ad->id, true);

            // Таймер
            OrderPendingCheck::dispatch($buyback->id)->delay(Carbon::now()->addMinutes(30));
            DB::commit();
            $response = $this->formatResponse('true', $buyback, '201');

            return $this->sendResponse($response);
        } catch (\Exception $e) {
            \Log::error($e);
            DB::rollBack();

            return $this->sendError('Произошла ошибка, попробуйте еще раз', 500);
        }
    }
}
