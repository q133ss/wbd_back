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
            $productPrice       = Product::find($data['product_id'])?->price;
            $cashbackPercentage = $data['cashback_percentage'];

            $cashbackAmount              = ($productPrice * $cashbackPercentage) / 100; // Это кэшбек юзеру!
            $data['price_with_cashback'] = $productPrice - $cashbackAmount;

            $user = auth('sanctum')->user();

            $data['status']  = true;
            $data['user_id'] = $user->id;
            $redemption = $user->redemption_count - $data['redemption_count'];

            if ($data['redemption_count'] > $user->redemption_count) {
                // если выкупов не хватает!
                $buybacks_count = $data['redemption_count'] - $user->redemption_count;
                $userData            = [];
                $price = $buybacks_count * config('price.buyback_price');
                $userData['balance'] = $user->balance - $price;

                if($userData['balance'] < 0){
                    $this->sendError('У вас недостаточно выкупов', 400);
                }

                $depositTransaction = Transaction::create([
                    'amount'           => $price,
                    'transaction_type' => 'deposit',
                    'currency_type'    => 'buyback',
                    'description'      => 'Начисление выкупов (оплата балансом): '.$buybacks_count.' выкупов',
                    'user_id'          => $user->id,
                ]);
                $user->update($userData);
                $redemption = $redemption + $buybacks_count;
            }

            // Заморозка баланса
            // цену для юзера умножаем ее на кол-во выкупов
            //$priceForUser = $data['price_with_cashback'] * $data['redemption_count'];
            $priceForUser = $cashbackAmount * $data['redemption_count'];

            $newBalance   = $user->balance - $priceForUser;
            if ($newBalance < 0) {
                $this->sendError('У вас недостаточно средств', 400);
            }
            $user->update(['balance' => $newBalance, 'redemption_count' => $redemption]);

            $ad = Ad::create($data);

            FrozenBalance::create([
                'user_id' => $user->id,
                'ad_id'   => $ad->id,
                'amount'  => $priceForUser,
                'reason'  => 'Создание объявления #'.$ad->id,
            ]);

            if (isset($depositTransaction)) {
                $depositTransaction->update(['ads_id' => $ad->id]);
            }


            Transaction::create([
                'amount'           => $data['redemption_count'],
                'transaction_type' => 'withdraw',
                'currency_type'    => 'buyback',
                'description'      => 'Создание объявления: '.$data['redemption_count'].' выкупов',
                'user_id'          => $user->id,
                'ads_id'           => $ad->id
            ]);

            DB::commit();

            return Response()->json([
                'ads'  => $ad->load('product', 'shop'),
                'user' => [
                    'balance'          => $user->balance,
                    'redemption_count' => $user->redemption_count,
                ],
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->sendError('Произошла ошибка, попробуйте еще раз', $e->getCode());
        }
    }
}
