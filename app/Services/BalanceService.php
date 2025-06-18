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
            $cashback = (($buyback->price * $buyback->ad?->cashback_percentage) / 100);
            // Снимает деньги за выкуп
            $frozenBalance->update([
                'amount' => $frozenBalance->amount - $cashback,
            ]);
            $user = $buyback->user;
            $user->update([
                'balance' => $user->balance + $cashback,
            ]);

            Transaction::create([
                'amount'           => $cashback,
                'transaction_type' => 'deposit',
                'currency_type'    => 'cash',
                'description'      => 'Кешбек за выкуп #'.$buyback->id.'. Сумма: '.$cashback.' ₽',
                'user_id'          => $user->id,
            ]);

            // Делаем 2 уведомления
            (new NotificationService)->send($buyback->user_id, $buyback->id, 'Вы получили кешбек '.$cashback.' ₽ за выкуп #'.$buyback->id, true);
            (new NotificationService)->send($buyback->user_id, $buyback->id, 'Кешбек за выкуп #'.$buyback->id.' выплачен', true);
        } catch (\Exception $e) {
            \Log::info('ОШИБКА ПРИ НАЧИСЛЕНИИ БАЛАНСА');
            \Log::error($e);

            return $this->sendError('Произошла ошибка, попробуйте еще раз', 500);
        }
    }
}
