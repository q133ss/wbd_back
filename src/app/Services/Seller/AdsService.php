<?php

namespace App\Services\Seller;

use App\Models\Ad;
use App\Models\FrozenBalance;
use App\Models\Product;
use App\Models\Tariff;
use App\Models\Transaction;
use App\Services\BaseService;
use Illuminate\Support\Facades\DB;

class AdsService extends BaseService
{
    public function create(array $data)
    {
        try {
            DB::beginTransaction();

            $productPrice       = Product::find($data['product_id'])->pluck('price')->first();
            $cashbackPercentage = $data['cashback_percentage'];

            $cashbackAmount              = ($productPrice * $cashbackPercentage) / 100;
            $data['price_with_cashback'] = $productPrice - $cashbackAmount;

            $user = Auth('sanctum')->user();

            $data['status']  = true;
            $data['user_id'] = $user->id;

            if ($data['redemption_count'] > $user->redemption_count) {
                $tariff = Tariff::where('buybacks_count', '>=', $data['redemption_count'])
                    ->orderBy('buybacks_count', 'desc')
                    ->first();

                $userData            = [];
                $userData['balance'] = $user->balance - $tariff->price;

                if ($tariff->buybacks_count > $data['redemption_count']) {
                    $difference                   = $tariff->buybacks_count - $data['redemption_count'];
                    $userData['redemption_count'] = $user->redemption_count + $difference;
                    $depositTransaction           = Transaction::create([
                        'amount'           => $tariff->buybacks_count,
                        'transaction_type' => 'deposit',
                        'currency_type'    => 'buyback',
                        'description'      => 'Начисление пакета выкупов (оплата балансом): '.$tariff->buybacks_count.' выкупов',
                        'user_id'          => $user->id,
                    ]);
                }

                $user->update($userData);

                $withdrawTransaction = Transaction::create([
                    'amount'           => $tariff->price,
                    'transaction_type' => 'withdraw',
                    'currency_type'    => 'buyback',
                    'description'      => 'Создание объявления: '.$data['redemption_count'].' выкупов',
                    'user_id'          => $user->id,
                ]);
            } else {
                $redemption = $user->redemption_count - $data['redemption_count'];
                $user->update(['redemption_count' => $redemption]);
                $withdrawTransaction = Transaction::create([
                    'amount'           => $data['redemption_count'],
                    'transaction_type' => 'withdraw',
                    'currency_type'    => 'buyback',
                    'description'      => 'Создание объявления: '.$data['redemption_count'].' выкупов',
                    'user_id'          => $user->id,
                ]);
            }

            // Заморозка баланса
            // цену для юзера умножаем ее на кол-во выкупов
            $priceForUser = $data['price_with_cashback'] * $data['redemption_count'];
            $newBalance = $user->balance - $priceForUser;
            if($newBalance <= 0)
            {
                $this->sendError('У вас недостаточно средств', 400);
            }
            $user->update(['balance' => $newBalance]);

            $ad = Ad::create($data);

            FrozenBalance::create([
                'user_id' => $user->id,
                'ad_id' => $ad->id,
                'amount' => $priceForUser,
                'reason' => 'Создание объявления #'.$ad->id,
            ]);

            if (isset($depositTransaction)) {
                $depositTransaction->update(['ads_id' => $ad->id]);
            }
            $withdrawTransaction->update(['ads_id' => $ad->id]);

            DB::commit();

            return Response()->json([
                'ads'  => $ad,
                'user' => [
                    'balance'          => $user->balance,
                    'redemption_count' => $user->redemption_count,
                ],
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();

            return $this->sendError('Произошла ошибка, попробуйте еще раз', 500);
        }
    }
}
