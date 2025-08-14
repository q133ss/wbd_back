<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Http\Requests\Seller\PromocodeController\ApplyRequest;
use App\Models\Promocode;
use App\Models\Tariff;
use Illuminate\Support\Facades\DB;

class PromocodeController extends Controller
{
    public function apply(ApplyRequest $request)
    {
        DB::beginTransaction();
        try {
            $promocode = Promocode::where('promocode', $request->promocode)->first();
            $user      = auth('sanctum')->user();
            $user->promocodes()->attach($promocode->id);

            $data = json_decode($promocode->data, true);
            switch ($data['type']) {
                case 'custom_tariff':
                    // Логика кастомного тарифа
                    break;

                case 'discount':
                    // Логика скидки
                    //{
                    //    "type": "discount",
                    //    "tariff_name": "Премиум",
                    //    "discount_percent": 20
                    //}
                    break;

                case 'extra_days':
                    // Логика добавления дней
                    //{
                    //    "type": "extra_days",
                    //    "tariff_name": "Премиум",
                    //    "extra_days": 15
                    //}
                    break;

                case 'free_tariff':
                    // Логика бесплатного тарифа
                    $checkTariff = $user->tariffs()->where('end_date', '>', now())->where('name', '!=', 'Пробный')->exists();
                    if($checkTariff) {
                        DB::rollBack();
                        return response()->json(['message' => 'У вас уже есть активный тариф'], 400);
                    }

                    // Удаляем пробный тариф, если он есть
                    $user->tariffs()
                        ->where('name', 'Пробный')
                        ->detach();

                    $tariff = Tariff::where('name', $data['tariff_name'])->first();

                    if(!$tariff) {
                        DB::rollBack();
                        \Log::error('Тариф не найден: ' . $data['tariff_name']. ' для промокода: ' . $promocode->promocode);
                        return response()->json(['message' => 'Ошибка, обратитесь к администрации'], 404);
                    }

                    $tariffData = collect($tariff->data)->where('name', $data['variant_name'])->first();
                    $durationDays = 1;
                    if($tariffData != null){
                        $durationDays = $tariffData['duration_days'] ?? 1;
                    }

                    DB::table('user_tariff')->insert([
                        'user_id' => $user->id,
                        'tariff_id' => $tariff->id,
                        'end_date' => now()->addDays((int)$durationDays),
                        'products_count' => $tariff->products_count,
                        'variant_name' => $data['variant_name'],
                        'duration_days' => (int)$durationDays,
                        'price_paid' => 0
                    ]);
                    break;
            }

            DB::commit();

            return response()->json(['message' => 'Промокод применен']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Произошла ошибка при применении промокода'], 500);
        }
    }
}
