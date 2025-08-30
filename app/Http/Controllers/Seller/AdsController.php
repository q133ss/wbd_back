<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Http\Requests\Seller\AdsController\StopRequest;
use App\Http\Requests\Seller\AdsController\StoreRequest;
use App\Http\Requests\Seller\AdsController\UpdateRequest;
use App\Models\Ad;
use App\Models\AdStat;
use App\Models\Buyback;
use App\Models\Product;
use App\Models\Transaction;
use App\Services\NotificationService;
use App\Services\Seller\AdsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
//    public function index(Request $request)
//    {
//        $userId = auth('sanctum')->id();
//
//        $ads = Ad::where('user_id', $userId)
//            ->withFilter($request)
//            ->withCount(['buybacks' => function ($query) {
//                $query->where('status', 'completed');
//            }])
//            ->orderBy('created_at', 'desc')
//            ->paginate(30);
//
//        $ads->getCollection()->transform(function ($ad) {
//            // Buybacks
//            $ad->completed_buybacks_count = $ad->buybacks()
//                ->whereIn('status', ['completed', 'cashback_received'])
//                ->count();
//
//            $ad->process_buybacks_count = $ad->buybacks()
//                ->whereIn('status', ['pending', 'awaiting_receipt', 'on_confirmation', 'awaiting_payment_confirmation'])
//                ->count();
//
//            unset($ad->buybacks_count);
//
//            // Сумма сделок
//            $ad->in_deal = Buyback::where('ads_id', $ad->id)->sum('product_price');
//
//            // CR (Conversion Rate)
//            $ad->cr = ceil($ad->completed_buybacks_count / max($ad->redemption_count, 1));
//            $ad->format_buybacks = $ad->completed_buybacks_count . ' шт. / ' . $ad->redemption_count . ' шт.';
//
//            // Статистика из ad_stats
//            $views = AdStat::where('ad_id', $ad->id)->where('type', 'view')->count();
//            $clicks = AdStat::where('ad_id', $ad->id)->where('type', 'click')->count();
//
//            $ad->views_count = $views;
//            $ad->clicks_count = $clicks;
//
//            $ad->ctr = $views > 0 ? round(($clicks / $views) * 100, 2) : 0;
//            $ad->cr_percent = $clicks > 0 ? round(($ad->completed_buybacks_count / $clicks) * 100, 2) : 0;
//
//            return $ad;
//        });
//
//        return response()->json($ads);
//    }

    public function index(Request $request)
    {
        $userId = auth('sanctum')->id();

        $sortBy = $request->get('sort_by', 'created_at');
        $sortDir = $request->get('sort_dir', 'desc');

        $dbSortable = ['id', 'name', 'created_at', 'updated_at', 'cashback_percentage', 'price_with_cashback', 'views_count', 'status', 'product_id'];

        $ads = Ad::where('user_id', $userId)
            ->withFilter($request)
            ->withCount(['buybacks' => function ($query) {
                $query->where('status', 'completed');
            }]);

        if (in_array($sortBy, $dbSortable)) {
            $ads->orderBy($sortBy, $sortDir);
        } else {
            $ads->orderBy('created_at', 'desc');
        }

        $ads = $ads->paginate(30);

        $ads->getCollection()->transform(function ($ad) {
            $ad->completed_buybacks_count = $ad->buybacks()
                ->whereIn('status', ['completed', 'cashback_received'])
                ->count();

            $ad->process_buybacks_count = $ad->buybacks()
                ->whereIn('status', ['pending', 'awaiting_receipt', 'on_confirmation', 'awaiting_payment_confirmation'])
                ->count();

            unset($ad->buybacks_count);

            $ad->in_deal = Buyback::where('ads_id', $ad->id)->sum('product_price');

            $ad->cr = ceil($ad->completed_buybacks_count / max($ad->redemption_count, 1));
            $ad->format_buybacks = $ad->completed_buybacks_count . ' шт. / ' . $ad->redemption_count . ' шт.';

            $views = AdStat::where('ad_id', $ad->id)->where('type', 'view')->count();
            $clicks = AdStat::where('ad_id', $ad->id)->where('type', 'click')->count();

            $ad->views_count = $views;
            $ad->clicks_count = $clicks;
            $ad->ctr = $views > 0 ? round(($clicks / $views) * 100, 2) : 0;
            $ad->cr_percent = $clicks > 0 ? round(($ad->completed_buybacks_count / $clicks) * 100, 2) : 0;

            return $ad;
        });

        // сортировка по вычисляемым полям
        if (!in_array($sortBy, $dbSortable)) {
            $ads->setCollection(
                $ads->getCollection()->sortBy($sortBy, SORT_REGULAR, $sortDir === 'desc')->values()
            );
        }

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

    public function startStop(StopRequest $request)
    {
        $user = auth('sanctum')->user();
        $hasTariff = $user->checkTariff();
        if(!$hasTariff){
            return response()->json([
                'status' => 'false',
                'message' => 'Что бы активировать объявление купите тариф',
                'hasTariff' => 'false'
            ], 403);
        }
        try {
            DB::beginTransaction();

            $adsData = Ad::whereIn('id', $request->ad_ids)
                ->select(['id', 'product_id', 'status'])
                ->orderBy('id')
                ->get();

            if ($adsData->isEmpty()) {
                return response()->json(['status' => false, 'message' => 'Объявления не найдены'], 404);
            }

            $newStatus = !$adsData->first()->status;
            $productIds = $adsData->pluck('product_id')->unique();

            if ($newStatus) {
                // Тарифы
                $tariff = $user->tariffs()
                    ->wherePivot('status', true)
                    ->wherePivot('end_date', '>', now())
                    ->first();

                $productIdsFromPivot = json_decode($tariff->pivot->product_ids ?? '[]', true);
                $newProductIds = $productIdsFromPivot;
                $exceeded = false;

                // Проверяем каждый товар из запроса
                foreach ($productIds as $productId) {
                    if (!in_array($productId, $productIdsFromPivot)) {
                        if ($tariff->pivot->products_count <= 0) {
                            $exceeded = true;
                            break;
                        }
                        // Добавляем новый товар и уменьшаем лимит
                        $newProductIds[] = $productId;
                        $tariff->pivot->products_count--;
                    }
                }

                if ($exceeded) {
                    DB::rollBack();
                    return response()->json([
                        'status' => false,
                        'message' => 'Превышен лимит активаций по тарифу. Доступно товаров: ' . $tariff->pivot->products_count,
                    ], 403);
                }

                // Обновляем пивот: новый список товаров и количество
                $user->tariffs()->updateExistingPivot($tariff->id, [
                    'products_count' => $tariff->pivot->products_count,
                    'product_ids' => $newProductIds
                ]);
                // Готово



                // Определяем ID объявлений для активации (по одному на товар)
                $adsToActivate = $adsData->groupBy('product_id')
                    ->map->first()
                    ->pluck('id');

                // Один запрос для обновления всех статусов
                DB::statement("
                UPDATE ads
                SET status = CASE
                    WHEN id IN (".$adsToActivate->join(',').") THEN TRUE
                    WHEN product_id IN (".$productIds->join(',').") THEN FALSE
                    ELSE status
                END
                WHERE product_id IN (".$productIds->join(',').")
            ");

                // Активируем товары
                Product::whereIn('id', $productIds)->update(['status' => true]);

                $message = 'Активировано по одному объявлению для каждого товара';
            } else {
                // Деактивация
                Ad::whereIn('id', $request->ad_ids)->update(['status' => false]);

                // Деактивируем товары без активных объявлений
                $inactiveProducts = DB::table('products')
                    ->whereIn('id', $productIds)
                    ->whereNotExists(function ($query) {
                        $query->select(DB::raw(1))
                            ->from('ads')
                            ->whereColumn('ads.product_id', 'products.id')
                            ->where('ads.status', true);
                    })
                    ->pluck('id');

                if ($inactiveProducts->isNotEmpty()) {
                    Product::whereIn('id', $inactiveProducts)->update(['status' => false]);
                }

                $message = 'Объявления остановлены';
            }

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => $message,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Ad status change error: '.$e->getMessage());
            return response()->json(['status' => false, 'message' => 'Ошибка сервера'], 500);
        }
    }
    public function archive(StopRequest $request)
    {
        try {
            DB::beginTransaction();

            $buybacks = Buyback::whereIn('ads_id', $request->ad_ids)
                ->whereNotIn('status', ['cancelled', 'completed', 'cashback_received'])
                ->exists();

            if ($buybacks) {
                return Response()->json([
                    'status'  => 'false',
                    'message' => 'Невозможно архивировать товары с активными выкупами',
                ], 403);
            }

            $ads = Ad::whereIn('id', $request->ad_ids);

            $hasActive = (clone $ads)->where('status', true)->exists();
            if ($hasActive) {
                return response()->json([
                    'status'  => 'false',
                    'message' => 'Невозможно архивировать активные объявления',
                ], 403);
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
