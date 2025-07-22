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
            $product = Product::where('id', $data['product_id'])->select('price','wb_id')->first();
            $productPrice       = $product?->price;
            $cashbackPercentage = $data['cashback_percentage'];

            $cashbackAmount              = ($productPrice * $cashbackPercentage) / 100; // Это кэшбек юзеру!
            $data['price_with_cashback'] = $productPrice - $cashbackAmount;

            $user = auth('sanctum')->user();

            // если нет тарифа,то false!
            $hasTariff = $user->checkTariff();

            if($hasTariff){
                $data['status']  = true;
            }else{
                // Если подписки нет, то статус false!!
                $data['status']  = false;
            }
            $data['user_id'] = $user->id;

            if(isset($data['keywords'])) {
                $redemptionInstructions = '⚠️ Выкуп по ключевым словам<br>Для участия в акции вам необходимо найти товар через поиск по ключевому слову "{word}", а не по артикулу.<br>Перейдите по ссылке: {search_link}, найдите нужный товар и оформите заказ именно с этой страницы.<br><br>Это важно — так система зафиксирует, что вы пришли по нужному поисковому запросу.';
                $data['redemption_instructions'] = $redemptionInstructions;
            }else{
                $redemptionInstructions = str_replace('{wb_id}', $product->wb_id, $data['redemption_instructions']);
                $data['redemption_instructions'] = $redemptionInstructions;
            }

            $ad = Ad::create($data);

            $product = $ad->product()->update(['status' => true]);

//            Transaction::create([
//                'amount'           => $data['redemption_count'],
//                'transaction_type' => 'withdraw',
//                'currency_type'    => 'buyback',
//                'description'      => 'Создание объявления: '.$data['redemption_count'].' выкупов',
//                'user_id'          => $user->id,
//                'ads_id'           => $ad->id
//            ]);

            DB::commit();

            return Response()->json([
                'ads'  => $ad->load('product', 'shop')
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->sendError('Произошла ошибка, попробуйте еще раз', $e->getCode());
        }
    }
}
