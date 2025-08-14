<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

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
}
