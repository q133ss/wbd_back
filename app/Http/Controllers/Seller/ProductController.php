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
        $sortBy = $request->get('sort_by', 'created_at');
        $sortDir = $request->get('sort_dir', 'desc');

        $products = auth('sanctum')->user()->shop?->products()
            ->with(['ads.buybacks' => function ($q) {
                $q->select('id', 'ads_id', 'status', 'product_price');
            }, 'ads.stats' => function ($q) {
                $q->select('id', 'ad_id', 'type');
            }])
            ->withFilter($request);

        // сортируем только по "нормальным" полям
        if (in_array($sortBy, ['id', 'name', 'created_at', 'updated_at', 'price', 'rating', 'quantity_available', 'category_id', 'status'])) {
            $products->orderBy($sortBy, $sortDir);
        } else {
            // иначе по умолчанию
            $products->orderBy('created_at', 'desc');
        }

        $products = $products->paginate(30);

        if ($products) {
            $products->getCollection()->transform(function ($product) {
                $ads = $product->ads;

                $views = 0;
                $clicks = 0;
                $redemptionTotal = 0;
                $completedBuybacks = 0;
                $inDeal = 0;
                $processingBuybacks = 0;

                foreach ($ads as $ad) {
                    $redemptionTotal += $ad->redemption_count ?? 0;
                    $adBuybacks = $ad->buybacks ?? collect();
                    $completedBuybacks += $adBuybacks->whereIn('status', ['cashback_received', 'completed'])->count();
                    $processingBuybacks += $adBuybacks->whereIn('status', ['pending', 'awaiting_receipt', 'on_confirmation', 'awaiting_payment_confirmation'])->count();
                    $inDeal += $adBuybacks->sum('product_price');

                    $stats = $ad->stats ?? collect();
                    $views += $stats->where('type', 'view')->count();
                    $clicks += $stats->where('type', 'click')->count();
                }

                $ctr = $views > 0 ? round(($clicks / $views) * 100, 2) : 0;
                $cr_percent = $clicks > 0 ? round(($completedBuybacks / $clicks) * 100, 2) : 0;
                $cr = ceil($completedBuybacks / max($redemptionTotal, 1));

                $product->views = $views;
                $product->clicks = $clicks;
                $product->ctr = $ctr;
                $product->cr_percent = $cr_percent;
                $product->cr = $cr;
                $product->completed_buybacks_count = $completedBuybacks;
                $product->redemption_count = $redemptionTotal;
                $product->buybacks_progress = "$completedBuybacks/$redemptionTotal";
                $product->in_deal = $inDeal;
                $product->ads_count = $ads->count();
                $product->processing_buybacks = $processingBuybacks;

                return $product;
            });

            // сортировка по вычисляемым полям уже после трансформации
            if (!in_array($sortBy, ['id', 'name', 'created_at', 'updated_at', 'price'])) {
                $products->setCollection(
                    $products->getCollection()->sortBy($sortBy, SORT_REGULAR, $sortDir === 'desc')->values()
                );
            }
        }

        return response()->json($products);
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

            $products = Product::whereIn('id', $request->product_ids);
            $products->update(['is_archived' => true]);

            $ads = Ad::whereIn('product_id', $request->product_ids);

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
