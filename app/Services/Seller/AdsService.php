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
                $this->sendError('У вас недостаточно выкупов', 400);
            }

            $user->update(['redemption_count' => $redemption]);

            $ad = Ad::create($data);

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
