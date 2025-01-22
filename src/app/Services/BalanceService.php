<?php

namespace App\Services;

use App\Models\Buyback;
use App\Models\FrozenBalance;
use App\Models\Transaction;

class BalanceService extends BaseService
{
    // Все транзакции с балансом производим через этот сервис!
    // ВСЕ ВЫЗЫВЫ ФУНКЦИЙ ДОЛЖНЫ БЫТЬ ВНУТРИ ТРАНЗАКЦИЙ
    /**
     * Перевод денег покупателю за успешный выкуп
     * ВЫЗЫВАЕМ ТОЛЬКО ВНУТРИ ТРАНЗАКЦИИ!!!!
     *
     * @return void
     */
    public function buybackPayment(Buyback $buyback)
    {
        try {
            $frozenBalance = FrozenBalance::where('ad_id', $buyback->ads_id)->first();
            // Снимает деньги за выкуп
            $frozenBalance->update([
                'amount' => $frozenBalance->amount - $buyback->price,
            ]);
            $user = $buyback->user;
            $user->update([
                'balance' => $user->balance + $buyback->price,
            ]);

            Transaction::create([
                'amount'           => $buyback->price,
                'transaction_type' => 'deposit',
                'currency_type'    => 'cash',
                'description'      => 'Кешбек за выкуп #'.$buyback->id.'. Сумма: '.$buyback->price.' ₽',
                'user_id'          => $user->id,
            ]);

            // Делаем 2 уведомления
            (new NotificationService)->send($buyback->user_id, $buyback->id, 'Вы получили кешбек '.$buyback->price.' ₽ за выкуп #'.$buyback->id);
            (new NotificationService)->send($buyback->user_id, $buyback->id, 'Кешбек за выкуп #'.$buyback->id.' выплачен');
        } catch (\Exception $e) {
            \Log::info('ОШИБКА ПРИ НАЧИСЛЕНИИ БАЛАНСА');
            \Log::error($e);

            return $this->sendError('Произошла ошибка, попробуйте еще раз', 500);
        }
    }
}
