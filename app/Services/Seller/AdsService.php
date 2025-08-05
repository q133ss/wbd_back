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

            if($user->is_frozen){
                return response()->json([
                    'status'  => 'false',
                    'message' => 'Ваш аккаунт заморожен, вы не можете создавать объявления'
                ]);
            }

            // если нет тарифа,то false!
            $hasTariff = $user->checkTariff();

            if($hasTariff){
                $tariff = $user->tariffs()
                    ->wherePivot('status', true)
                    ->wherePivot('end_date', '>', now())
                    ->first();


                $newCount = $tariff->pivot?->products_count - 1;

                $productIdsRaw = $tariff->pivot?->product_ids ?? '[]';
                $productIds = is_array($productIdsRaw) ? $productIdsRaw : json_decode($productIdsRaw, true);

                if(!in_array($data['product_id'], $productIds)){
                    if ($tariff && $tariff->pivot?->products_count > 0) {
                        $productIds[] = $data['product_id'];
                        $user->tariffs()->updateExistingPivot($tariff->id, [
                            'products_count' => $newCount,
                            'product_ids' => $productIds
                        ]);
                    }else{
                        return response()->json([
                            'status' => 'false',
                            'message' => 'Вы не можете создать объявлений более чем для '.$tariff->pivot?->product_ids.' товаров'
                        ]);
                    }
                }
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
            dd($e);
            return $this->sendError('Произошла ошибка, попробуйте еще раз', $e->getCode());
        }
    }
}
