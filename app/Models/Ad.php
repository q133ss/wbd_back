<?php

namespace App\Models;

use App\Models\Scopes\NotArchiveScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class Ad extends Model
{
    protected $guarded = [];

    protected $with = ['product', 'shop'];

    protected $casts = [
        'keywords' => 'array',
        'color' => 'array',
        'size' => 'array'
    ];

    // Указываем глобальный скоуп в методе `booted`
    protected static function booted()
    {
        static::addGlobalScope(new NotArchiveScope);
    }

    // Метод для отключения глобального скоупа
    public static function withoutArchived()
    {
        return static::withoutGlobalScope(NotArchiveScope::class);
    }

    public function joined($query, $table)
    {
        $joins = $query->getQuery()->joins;
        if ($joins == null) {
            return false;
        }
        foreach ($joins as $join) {
            if ($join->table == $table) {
                return true;
            }
        }

        return false;
    }

    public function scopeWithFilter($query, Request $request)
    {
        return $query
            ->when(
                $request->has('status'),
                function (Builder $query) use ($request) {
                    return $query->where('ads.status', $request->get('status'));
                }
            )
            ->when(
                $request->has('is_archived'),
                function (Builder $query, $isArchived) use ($request) {
                    return static::withoutArchived()->where('ads.is_archived', true);
                }
            )
            ->when(
                $request->has('price_from'),
                function (Builder $query) use ($request) {
                    return $query->where('ads.price_with_cashback', '>=', $request->get('price_from'));
                }
            )
            ->when(
                $request->has('price_to'),
                function (Builder $query) use ($request) {
                    return $query->where('ads.price_with_cashback', '<=', $request->get('price_to'));
                }
            )
            ->when(
                $request->has('cashback_from'),
                function (Builder $query, $cashbackFrom) use ($request) {
                    return $query->where('ads.cashback_percentage', '>=', $request->get('cashback_from'));
                }
            )
            ->when(
                $request->has('cashback_to'),
                function (Builder $query, $cashbackTo) use ($request) {
                    return $query->where('ads.cashback_percentage', '<=', $request->get('cashback_to'));
                }
            )
            ->when(
                $request->has('category_id'),
                function (Builder $query, $categoryId) use ($request) {
                    if (! $this->joined($query, 'products')) {
                        $query->join('products', 'products.id', '=', 'ads.product_id');
                    }

                    return $query->where('products.category_id', '=', $request->get('category_id'));
                }
            )
            ->when(
                $request->has('product_id'),
                function (Builder $query, $productId) use ($request) {
                    return $query->where('ads.product_id', '=', $request->get('product_id'));
                }
            )
            ->when(
                $request->has('search'),
                function (Builder $query) use ($request) {
                    return $query->whereAny(['ads.name', 'ads.price_with_cashback'], 'LIKE', '%'.$request->get('search').'%');
                }
            );
    }

    public function scopeWithSorting($query, Request $request)
    {
        // Получаем параметры сортировки
        $sortField = $request->input('sort');
        $sortOrder = $request->input('order');

        // Проверка допустимых значений
        $validColumns = ['created_at', 'price_with_cashback', 'rating_product', 'rating_seller', 'popular', 'cashback_percentage', 'price', 'discount'];
        $validOrders = ['asc', 'desc'];

        if (!in_array($sortField, $validColumns)) {
            $sortField = 'created_at';
        }

        if (!in_array($sortOrder, $validOrders)) {
            $sortOrder = 'desc';
        }

        // Применяем сортировку
        if ($sortField === 'rating_product') {
            // Рейтинг товара
            $query->leftJoin('products', 'ads.product_id', '=', 'products.id')
                  ->orderBy('products.rating', $sortOrder);
        } elseif ($sortField === 'rating_seller') {
            // Рейтинг продавца
            $query->leftJoin('users', 'ads.user_id', '=', 'users.id')
                ->leftJoin('reviews', function ($join) {
                    $join->on('users.id', '=', 'reviews.reviewable_id')
                        ->where('reviews.reviewable_type', '=', \App\Models\User::class);
                })
                ->select('ads.*', DB::raw('AVG(reviews.rating) as avg_rating'))
                ->groupBy('ads.id', 'ads.product_id', 'ads.name', 'ads.cashback_percentage', 'ads.price_with_cashback', 'ads.status', 'ads.created_at', 'ads.user_id', 'ads.order_conditions', 'ads.is_archived', 'ads.keywords', 'ads.views_count', 'ads.redemption_instructions', 'wbd.ads.review_criteria', 'ads.redemption_count', 'ads.one_per_user', 'ads.balance', 'ads.in_favorite', 'ads.color', 'ads.size', 'ads.updated_at', 'ads.created_at')
                ->orderBy('avg_rating', $sortOrder);
        } elseif ($sortField === 'popular') {
            // По кол-ву заказов
            $query->leftJoin('buybacks', 'ads.id', '=', 'buybacks.ads_id')
                  ->select('ads.*', DB::raw('COUNT(buybacks.id) as buyback_count'))
                  ->groupBy('ads.id', 'ads.product_id', 'ads.name', 'ads.cashback_percentage', 'ads.price_with_cashback', 'ads.status', 'ads.created_at', 'ads.user_id', 'ads.order_conditions', 'ads.is_archived', 'ads.keywords', 'ads.views_count', 'ads.redemption_instructions', 'wbd.ads.review_criteria', 'ads.redemption_count', 'ads.one_per_user', 'ads.balance', 'ads.in_favorite', 'ads.color', 'ads.size', 'ads.updated_at', 'ads.created_at')
                  ->orderBy('buyback_count', $sortOrder);
        }elseif ($sortField === 'cashback_percentage') {
            $query->orderBy('ads.cashback_percentage', $sortOrder);
        } elseif ($sortField === 'price') {
            // Сортировка по цене без кэшбэка
            $query->select('ads.*', DB::raw('ROUND(ads.price_with_cashback / (1 - ads.cashback_percentage / 100), 2) as price_without_cashback'))
                  ->orderBy('price_without_cashback', $sortOrder);
        } elseif ($sortField === 'discount') {
//            $query->select('ads.*', DB::raw('ROUND(ads.price_with_cashback * (1 - ads.cashback_percentage / 100), 2) as discounted_price'))
//                  ->orderBy('discounted_price', $sortOrder);
            $query->orderBy('ads.cashback_percentage', $sortOrder);
        } else {
            $query->orderBy('ads.'.$sortField, $sortOrder);
        }

        return $query;
    }

    public function product(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Product::class, 'id', 'product_id');
    }

    /**
     * Байбеки
     *
     * @return HasMany
     */
    public function buybacks()
    {
        return $this->hasMany(Buyback::class, 'ads_id', 'id');
    }

    public function user()
    {
        return $this->hasOne(User::class, 'id', 'user_id');
    }

    public function reviews(): \Illuminate\Database\Eloquent\Relations\MorphMany
    {
        return $this->morphMany(Review::class, 'reviewable');
    }

    public function getAvgRating()
    {
        return $this->reviews()->avg('rating');
    }

    public function shop()
    {
        return $this->hasOneThrough(Shop::class, User::class, 'id', 'user_id', 'user_id', 'id');
    }

    public function getPriceWithoutCashback()
    {
        if ($this->cashback_percentage && $this->price_with_cashback) {
            return round($this->price_with_cashback / (1 - $this->cashback_percentage / 100), 2);
        }

        return null;
    }

    public function stats()
    {
        return $this->hasMany(AdStat::class);
    }

    /**
     * Логирование статистики по просмотрам и кликам
     * @param string $type
     * @return void
     */
    public function logStat(string $type): void
    {
        $user = auth()->user();
        $ip = request()->ip();

        $exists = AdStat::where('ad_id', $this->id)
            ->where('type', $type)
            ->where(function ($q) use ($user, $ip) {
                if ($user) {
                    $q->where('user_id', $user->id);
                } else {
                    $q->where('ip_address', $ip);
                }
            })
            ->exists();

        if (!$exists) {
            AdStat::create([
                'ad_id'     => $this->id,
                'user_id'   => $user?->id,
                'ip_address'=> $user ? null : $ip,
                'type'      => $type,
            ]);
        }
    }

    public function toArray()
    {
        $data                           = parent::toArray();
        $data['price_without_cashback'] = $this->getPriceWithoutCashback();
        $data['seller_rating']          = $this->user?->getRating();
        $data['buybacks_count'] = $this->buybacks()->whereIn('status', ['cashback_received', 'completed'])->count();
        return $data;
    }
}
