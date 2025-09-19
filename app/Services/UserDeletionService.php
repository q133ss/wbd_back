<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class UserDeletionService
{
    public function delete(User $user): void
    {
        DB::transaction(function () use ($user) {
            DB::table('user_tariff')->where('user_id', $user->id)->delete();

            $user->loadMissing('shop.products');

            $shop = $user->shop;

            if ($shop) {
                $productIds = $shop->products->pluck('id')->all();

                if (!empty($productIds)) {
                    DB::table('ads')->whereIn('product_id', $productIds)->delete();
                    DB::table('products')->whereIn('id', $productIds)->delete();
                }

                $shop->delete();
            }

            DB::table('ads')->where('user_id', $user->id)->delete();

            $user->delete();
        });
    }
}
