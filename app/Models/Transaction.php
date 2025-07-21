<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class Transaction extends Model
{
    protected $guarded = [];

    protected $casts = ['variant' => 'array'];

    public function user()
    {
        return $this->hasOne(User::class);
    }

    public function scopeWithFilter($query, Request $request)
    {
        return $query
            ->when(
                $request->query('type'),
                function (Builder $query, $type) {
                    return $query->where('transaction_type', $type);
                }
            )->when(
                $request->query('buyback_id'),
                function (Builder $query, $buyback_id) {
                    return $query
                        ->leftJoin('buybacks', 'buybacks.ads_id', 'transactions.ads_id')
                        ->where('buybacks.id', $buyback_id);
                }
            )->when(
                $request->query('ads_id'),
                function (Builder $query, $ads_id) {
                    return $query
                        ->where('transactions.ads_id', $ads_id);
                }
            )->when(
                $request->query(' '),
                function (Builder $query, $product_id) {
                    return $query
                        ->leftJoin('ads', 'ads.id', 'transactions.ads_id')
                        ->leftJoin('products', 'products.id', 'ads.product_id')
                        ->where('products.id', $product_id);
                }
            );
    }
}
