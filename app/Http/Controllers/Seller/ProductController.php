<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Http\Requests\Seller\ProductController\StopRequest;
use App\Models\Ad;
use App\Models\Buyback;
use App\Models\Product;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $ads = auth('sanctum')->user()->shop?->products()?->with('activeAd')->withFilter($request)->paginate();

        if($ads != null) {
            $ads->getCollection()->transform(function ($ad) {
                $activeAd = $ad->activeAd;
                $allRedemptionCount = $activeAd?->redemption_count; // Кол-во выкупов, которое задал продавец
                $completedBuybacksCount = $activeAd?->buybacks()->where('buybacks.status', 'completed')->count();

                $conversion = $ad->views > 0
                    ? round(($ad->completed_buybacks_count / $ad->views) * 100, 2)
                    : 0;

                // Добавляем дополнительные поля
                $ad->buybacks_progress = $completedBuybacksCount . ' шт./ ' . $allRedemptionCount . ' шт.'; // 15 шт. / 25 шт.
                $ad->completed_buybacks_count = $completedBuybacksCount; // кол-во выкупов

                $ad->conversion = $conversion; // Конверсия
                $ad->views = $activeAd?->views_count; // Кол-во просмотров
                $ad->ads_count = $ad->ads?->count(); // Кол-во объявлений

                return $ad;
            });
        }

        // Выкупов 25 из 50 || completed_buybacks_count
        // todo потом перенести их на 1 ступень выше в товар сам!
        return $ads;
    }

    /**
     * Show the form for creating a new resource.
     */
    public function store(Request $request)
    {
        // update cachce
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        // Можно сделать обновление данных товара с ВБ
        dd($id);
    }

    public function startStop(StopRequest $request)
    {
        try {
            DB::beginTransaction();

            $productIds = $request->product_ids;
            $currentStatus = Product::whereIn('id', $productIds)->value('status');
            $newStatus = is_null($currentStatus) ? false : !$currentStatus;

            $missingAds = DB::table('products')
                ->whereIn('id', $productIds)
                ->whereNotExists(function ($query) {
                    $query->select(DB::raw(1))
                        ->from('ads')
                        ->whereRaw('ads.product_id = products.id');
                })
                ->pluck('id');

            if(!$missingAds->isEmpty()){
                return response()->json([
                    'status'  => false,
                    'message' => 'Нельзя изменить статус товарам, которые не имеют объявлений',
                ], 400);
            }

            if ($newStatus) {
                // Активация (только товары)
                Product::whereIn('id', $productIds)->update(['status' => true]);

                $allInactive = DB::table('ads')
                    ->join('products', 'ads.product_id', '=', 'products.id')
                    ->whereIn('products.id', $productIds)
                    ->where('ads.status', true) // Ищем активные объявления
                    ->doesntExist(); // Вернет true, если НЕТ активных объявлений

                if ($allInactive) {
                    return response()->json([
                       'message' => 'Нельзя активировать товары, у которых нет активных объявлений',
                       'status'  => false
                    ], 400);
                }

                $message = 'Товары активированы';
            } else {
                // Деактивация (товары + объявления) — 2 запроса, но в одной транзакции
                Product::whereIn('id', $productIds)->update(['status' => false]);

                // Оптимизированный запрос через JOIN (быстрее, чем whereIn)
                DB::table('ads')
                    ->join('products', 'ads.product_id', '=', 'products.id')
                    ->whereIn('products.id', $productIds)
                    ->update(['ads.status' => false]);

                $message = 'Товары и их объявления остановлены';
            }

            DB::commit();

            return response()->json([
                'status'  => true,
                'message' => $message,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Product status error: ' . $e->getMessage());
            return response()->json([
                'status'  => false,
                'message' => 'Ошибка сервера',
            ], 500);
        }
    }

    public function archive(StopRequest $request)
    {
        try {
            DB::beginTransaction();

            $buybacks = Buyback::whereIn('ads_id', function ($query) use ($request) {
                return $query
                    ->select('id')
                    ->from('ads')
                    ->whereIn('product_id', $request->product_ids);
            })
                ->whereNotIn('status', ['cancelled', 'completed'])
                ->exists();

            if ($buybacks) {
                return Response()->json([
                    'status'  => 'false',
                    'message' => 'Невозможно архивировать товары с активными выкупами',
                ], 403);
            }

            $user = auth('sanctum')->user();

            $totalBalance         = 0;
            $totalRedemptionCount = 0;

            $products = Product::whereIn('id', $request->product_ids);
            $products->update(['is_archived' => true]);

            $ads = Ad::whereIn('product_id', $request->product_ids);

            $totalBalance         += $ads->sum('balance');
            $totalRedemptionCount += $ads->sum('redemption_count');

            $user->update(
                [
                    'balance'          => $user->balance          += $totalBalance,
                    'redemption_count' => $user->redemption_count += $totalRedemptionCount,
                ]
            );

            if ($totalBalance != 0) {
                Transaction::create([
                    'amount'           => $totalBalance,
                    'transaction_type' => 'deposit',
                    'currency_type'    => 'cash',
                    'description'      => 'Возврат средств при архивации: '.$totalBalance.' ₽',
                    'user_id'          => $user->id,
                ]);
            }

            if ($totalRedemptionCount != 0) {
                Transaction::create([
                    'amount'           => $totalRedemptionCount,
                    'transaction_type' => 'deposit',
                    'currency_type'    => 'buyback',
                    'description'      => 'Возврат выкупов при архивации: '.$totalRedemptionCount.' выкупов',
                    'user_id'          => $user->id,
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
                'message' => 'Товары архивированы',
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
        $duplicatedProducts = [];
        try {
            DB::beginTransaction();
            foreach ($request->product_ids as $id) {
                $product = Product::findOrFail($id);

                // Дублируем товар
                $newProduct       = $product->replicate();
                $newProduct->name = $product->name.' (Копия)';
                $newProduct->save();

                // Добавляем в массив дублированных товаров
                $duplicatedProducts[] = $newProduct;
            }

            DB::commit();

            return response()->json([
                'status'              => 'true',
                'message'             => 'Товары дублированы',
                'duplicated_products' => $duplicatedProducts,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status'  => 'false',
                'message' => 'Произошла ошибка, попробуйте еще раз',
            ]);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        // update cachce
    }

    public function list(string $type)
    {
        $user = auth('sanctum')->user();
        if ($type == 'ads') {
            return $user->ads?->select('id', 'name');
        } elseif ($type == 'products') {
            // Логика для обработки списка продуктов
            return $user->shop?->products?->select('id', 'name');
        }
        abort(404);
    }
}
