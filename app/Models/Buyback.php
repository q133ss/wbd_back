<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Buyback extends Model
{
    protected $guarded = [];

    public function ad()
    {
        return $this->hasOne(Ad::class, 'id', 'ads_id');
    }

    public function user()
    {
        return $this->hasOne(User::class, 'id', 'user_id');
    }

    public function messages()
    {
        return $this->hasMany(Message::class, 'buyback_id', 'id');
    }

    public function scopeWithFilter($query, $request)
    {
        return $query
            ->when(
                $request->query('status'),
                function (Builder $query, $status) {
                    if($status != 'all') {
                        return $query->where('buybacks.status', $status);
                    }
                }
            )->when(
                $request->query('search'),
                function (Builder $query, $q) {
                    return $query->whereAny([
                        'buybacks.id',
                        'buybacks.status',
                        'buybacks.product_price',
                        'buybacks.price_with_cashback',
                        'buybacks.cashback_percentage',
                        'buybacks.created_at',
                    ], 'LIKE', '%'.$q.'%');
                }
            )->when(
                $request->query('product_id'),
                function (Builder $query, $productId) {
                    return $query->whereHas('ad', function (Builder $query) use ($productId) {
                        $query->where('product_id', $productId);
                    });
                }
            );
    }
}
