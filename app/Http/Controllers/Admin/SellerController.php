<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Ad;
use App\Models\Buyback;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SellerController extends Controller
{
    public function index(Request $request)
    {
        $sellers = User::with([
                'shop.products.ads', // подгружаем продукты и их объявления
                'buybacks' => fn($q) => $q->whereIn('buybacks.status', ['cashback_received','completed'])
            ])
            ->withFilter($request)
            ->whereHas('role', fn($q) => $q->where('slug', 'seller'))
            ->paginate();

        $sellers->getCollection()->transform(function ($seller) {
            $seller->products_count = $seller->shop ? $seller->shop->products->count() : 0;
            $seller->ads_count = $seller->shop
                ? $seller->shop->products->sum(fn($product) => $product->ads->count())
                : 0;
            $seller->active_ads_count = $seller->shop
                ? $seller->shop->products->sum(fn($product) => $product->ads->where('status', true)->count())
                : 0;
            $seller->completed_buybacks_count = $seller->buybacks->count();

            return $seller;
        });

        return view('admin.seller.index', compact('sellers'));
    }

    public function show(string $id)
    {
        $user = User::with([
                'shop'
            ])
            ->whereHas('role', fn($q) => $q->where('slug', 'seller'))
            ->findOrFail($id);

        $adsIds = Ad::whereIn('product_id', Product::where('shop_id', $user->shop->id)->pluck('id')->all())
            ->pluck('id')
            ->all();

        $buybacksProccess = Buyback::whereIn('ads_id', $adsIds)
            ->whereIn('status', ['pending', 'awaiting_receipt', 'on_confirmation', 'awaiting_payment_confirmation'])
            ->count();
        $buybackSuccess = Buyback::whereIn('ads_id', $adsIds)
            ->whereIn('status', ['cashback_received', 'completed'])
            ->count();

        return view('admin.seller.show', compact('user', 'buybacksProccess', 'buybackSuccess'));
    }

    public function delete(string $id)
    {
        DB::beginTransaction();
        try {
            $user = User::whereHas('role', fn($q) => $q->where('slug', 'seller'))
                ->findOrFail($id);

            DB::table('user_tariff')->where('user_id', $user->id)->delete();

            if ($user->shop) {
                $user->shop->products()->each(function ($product) {
                    $product->ads()->delete();
                    $product->delete();
                });
                $user->shop->delete();
            }

            $user->delete();
            DB::commit();

            return redirect()->route('admin.sellers.index')->with('success', 'Продавец удален успешно!');
        }catch (\Throwable $e) {
            DB::rollBack();
            return redirect()->route('admin.sellers.index')->with('error', 'Ошибка при удалении продавца: ' . $e->getMessage());
        }

    }
}
