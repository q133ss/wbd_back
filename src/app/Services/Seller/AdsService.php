<?php

namespace App\Services\Seller;

use App\Models\Ad;
use App\Models\Product;
use App\Services\BaseService;
use Illuminate\Support\Facades\DB;

class AdsService extends BaseService
{
    public function create(array $data)
    {
        try {
            DB::beginTransaction();

            $productPrice = Product::find($data['product_id'])->pluck('price')->first();
            $cashbackPercentage = $data['cashback_percentage'];

            $cashbackAmount = ($productPrice * $cashbackPercentage) / 100;
            $data['price_with_cashback'] = $productPrice - $cashbackAmount;

            $user = Auth('sanctum')->user();
            $redemption = $user->redemption_count - $data['redemption_count'];
            $user->update(['redemption_count' => $redemption]);

            $ad = Ad::create($data);
            DB::commit();
            return Response()->json([
                'ads' => $ad,
                'user_redemption_count' => $redemption // Баланс выкупов юзера
            ], 201);
        }catch (\Exception $e){
            return $e;
            DB::rollBack();
            return $this->sendError('Произошла ошибка, попробуйте еще раз', 500);
        }
    }
}
