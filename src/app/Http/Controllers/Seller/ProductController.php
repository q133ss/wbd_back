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
        $ads = auth('sanctum')->user()->shop?->products()?->with('ads')->withFilter($request)->paginate();

        $ads->getCollection()->transform(function ($product) {
            // Трансформируем объявления внутри каждого товара
            $product->ads->transform(function ($ad) {
                // Добавляем количество завершённых выкупов
                $ad->completed_buybacks_count = $ad->buybacks->where('status', 'completed')->count();

                unset($ad->buybacks); // Убираем buybacks, если они больше не нужны

                // Добавляем расчёты
                $ad->balance = '???'; // Пример: доработать расчёт
                $ad->in_deal = '???'; // Пример: доработать расчёт
                $ad->cr      = ceil($ad->completed_buybacks_count / max($ad->redemption_count ?? 1, 1)); // CR с защитой от деления на 0

                return $ad;
            });
            $product->ads_count = $product->ads->count();

            return $product;
        });

        return $ads;
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
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
        // price
        // status,
        // is archived!!
        // !!! TODO добавить обновление статуса цены и архивации
    }

    public function stop(StopRequest $request)
    {
        try {
            DB::beginTransaction();
            Product::whereIn('id', $request->product_ids)->update(['status' => false]);
            Ad::whereIn('product_id', $request->product_ids)->update(['status' => false]);
            DB::commit();

            return response()->json([
                'status'  => 'true',
                'message' => 'Товары остановлен',
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
}
