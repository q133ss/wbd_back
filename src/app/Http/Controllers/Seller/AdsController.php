<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Http\Requests\Seller\AdsController\StoreRequest;
use App\Http\Requests\Seller\AdsController\UpdateRequest;
use App\Http\Requests\Seller\AdsController\StopRequest;
use App\Models\Ad;
use App\Models\Buyback;
use App\Models\Product;
use App\Models\Transaction;
use App\Services\Seller\AdsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $ads = Ad::where('user_id', auth('sanctum')->id())
            ->withFilter($request)
            ->withCount(['buybacks' => function ($query) {
                $query->where('status', 'completed');
            }])
            ->paginate();

        $ads->getCollection()->transform(function ($ad) {
            $ad->completed_buybacks_count = $ad->buybacks_count; // Кол-во завершённых выкупов
            unset($ad->buybacks_count);
            $ad->balance = '???';
            $ad->in_deal = '???'; // В сделках
            $cr          = ceil($ad->completed_buybacks_count / max($ad->redemption_count, 1)); // Защита от деления на 0
            $ad->cr      = $cr;

            return $ad;
        });

        return response()->json($ads);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreRequest $request)
    {
        return (new AdsService)->create($request->validated());
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        return Ad::where('user_id', auth('sanctum')->id())->findOrFail($id);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateRequest $request, string $id)
    {
        $ad = Ad::withoutArchived()->where('user_id', auth('sanctum')->id())
            ->findOrFail($id);
        $update = $ad->update($request->validated());

        return $ad;
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            DB::beginTransaction();
            $ad = Ad::where('user_id', auth('sanctum')->id())
                ->findOrFail($id);

            $user = auth('sanctum')->user();
            $user->update([
                'redemption_count' => $user->redemption_count + $ad->redemption_count,
            ]);

            $update = $ad->update([
                'is_archived'      => true,
                'redemption_count' => 0,
            ]);
            DB::commit();

            return Response()->json([
                'status'  => 'true',
                'message' => 'Объявление архивировано',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status'  => 'false',
                'message' => 'Произошла ошибка, попробуйте еще раз',
            ]);
        }
    }

    public function stop(StopRequest $request)
    {
        try {
            DB::beginTransaction();
            $ads = Ad::whereIn('id', $request->ad_ids);
            $product_ids = $ads->pluck('product_id')->all();
            Product::whereIn('id', $product_ids)->update(['status' => false]);
            DB::commit();

            return response()->json([
                'status'  => 'true',
                'message' => 'Объявления остановлены',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status'  => 'false',
                'message' => 'Произошла ошибка, попробуйте еще раз',
            ]);
        }
    }

    public function archive(StopRequest $request)
    {
        try {
            DB::beginTransaction();

            $buybacks = Buyback::whereIn('ads_id', $request->ad_ids)
                ->whereNotIn('status', ['cancelled', 'completed'])
                ->exists();

            if ($buybacks) {
                return Response()->json([
                    'status'  => 'false',
                    'message' => 'Невозможно архивировать товары с активными выкупами',
                ], 403);
            }

            $user = auth()->user();

            $totalBalance         = 0;
            $totalRedemptionCount = 0;

            $ads = Ad::whereIn('id', $request->ad_ids);

            $product_ids = $ads->pluck('product_id')->all();

            $products = Product::whereIn('id', $product_ids);
            $products->update(['is_archived' => true]);

            $totalBalance         += $ads->sum('balance');
            $totalRedemptionCount += $ads->sum('redemption_count');

            $user->update(
                [
                    'balance'          => $user->balance          += $totalBalance,
                    'redemption_count' => $user->redemption_count += $totalRedemptionCount,
                ]
            );

            if($totalBalance != 0) {
                Transaction::create([
                    'amount' => $totalBalance,
                    'transaction_type' => 'deposit',
                    'currency_type' => 'cash',
                    'description' => 'Возврат средств при архивации: ' . $totalBalance . ' ₽',
                    'user_id' => $user->id,
                ]);
            }

            if($totalRedemptionCount != 0) {
                Transaction::create([
                    'amount' => $totalRedemptionCount,
                    'transaction_type' => 'deposit',
                    'currency_type' => 'buyback',
                    'description' => 'Возврат выкупов при архивации: ' . $totalRedemptionCount . ' выкупов',
                    'user_id' => $user->id,
                ]);
            }

            $ads->update([
                'is_archived'      => true,
                'balance'          => 0,
                'redemption_count' => 0,
            ]);

            DB::commit();

            return response()->json([
                'status'  => 'true',
                'message' => 'Объявления архивированы',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status'  => 'false',
                'message' => 'Произошла ошибка, попробуйте еще раз',
            ]);
        }
    }

    public function duplicate(StopRequest $request)
    {
        $duplicatedAds = [];
        try {
            DB::beginTransaction();
            foreach ($request->ad_ids as $id) {
                $ad = Ad::findOrFail($id);

                // Дублируем объявление
                $newAd       = $ad->replicate();
                $newAd->name = $ad->name.' (Копия)';
                $newAd->save();

                // Добавляем в массив дублированных объявлений
                $duplicatedAds[] = $newAd;
            }

            DB::commit();

            return response()->json([
                'status'              => 'true',
                'message'             => 'Объявления дублированы',
                'duplicated_products' => $duplicatedAds,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status'  => 'false',
                'message' => 'Произошла ошибка, попробуйте еще раз',
            ]);
        }
    }
}
