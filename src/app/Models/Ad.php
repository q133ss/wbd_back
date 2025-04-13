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

    protected $with = ['product', 'shop', 'reviews'];

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
                $request->query('status'),
                function (Builder $query, $status) {
                    return $query->where('status', $status);
                }
            )
            ->when(
                $request->query('is_archived'),
                function (Builder $query, $isArchived) {
                    return static::withoutArchived()->where('is_archived', true);
                }
            )
            ->when(
                $request->query('price_from'),
                function (Builder $query, $priceFrom) {
                    return $query->where('price_with_cashback', '>=', $priceFrom);
                }
            )
            ->when(
                $request->query('price_to'),
                function (Builder $query, $priceTo) {
                    return $query->where('price_with_cashback', '<=', $priceTo);
                }
            )
            ->when(
                $request->query('cashback_from'),
                function (Builder $query, $cashbackFrom) {
                    return $query->where('cashback_percentage', '>=', $cashbackFrom);
                }
            )
            ->when(
                $request->query('cashback_to'),
                function (Builder $query, $cashbackTo) {
                    return $query->where('cashback_percentage', '<=', $cashbackTo);
                }
            )
            ->when(
                $request->query('category_id'),
                function (Builder $query, $categoryId) {
                    if (! $this->joined($query, 'products')) {
                        $query->join('products', 'products.id', '=', 'ads.product_id');
                    }

                    return $query->where('products.category_id', '=', $categoryId);
                }
            );
    }

    public function scopeWithSorting($query, Request $request)
    {
        // Получаем параметры сортировки
        $sortField = $request->input('sort');
        $sortOrder = $request->input('order');

        // Проверка допустимых значений
        $validColumns = ['created_at', 'price_with_cashback', 'rating_product', 'rating_seller', 'popular', 'cashback_percentage'];
        $validOrders = ['asc', 'desc'];

        if (!in_array($sortField, $validColumns)) {
            throw new \InvalidArgumentException("Неверное поле сортировки: $sortField");
        }

        if (!in_array($sortOrder, $validOrders)) {
            throw new \InvalidArgumentException("Неверное направление сортировки: $sortOrder");
        }

        // Применяем сортировку
        if ($sortField === 'rating_product') {
            $subQuery = DB::table('ads')
                ->leftJoin('reviews', function ($join) {
                    $join->on('ads.id', '=', 'reviews.reviewable_id')
                        ->where('reviews.reviewable_type', 'App\Models\Ad');
                })
                ->select('ads.id', DB::raw('COALESCE(AVG(reviews.rating), 0) as avg_rating'))
                ->groupBy('ads.id');

            $query->joinSub($subQuery, 'sub', function ($join) {
                $join->on('ads.id', '=', 'sub.id');
            })->orderBy('sub.avg_rating', $sortOrder);
        } elseif ($sortField === 'rating_seller') {
            $subQuery = DB::table('ads')
                ->leftJoin('reviews', function ($join) {
                    $join->on('ads.user_id', '=', 'reviews.reviewable_id')
                        ->where('reviews.reviewable_type', 'App\Models\User');
                })
                ->select('ads.id', DB::raw('COALESCE(AVG(reviews.rating), 0) as avg_rating'))
                ->groupBy('ads.id');

            $query->joinSub($subQuery, 'sub', function ($join) {
                $join->on('ads.id', '=', 'sub.id');
            })->orderBy('sub.avg_rating', $sortOrder);
        } elseif ($sortField === 'popular') {
            $subQuery = DB::table('buybacks')
                ->select('ads_id', DB::raw('COUNT(*) as buyback_count'))
                ->groupBy('ads_id');

            $query->leftJoinSub($subQuery, 'buyback_counts', function ($join) {
                $join->on('ads.id', '=', 'buyback_counts.ads_id');
            })
                ->orderBy(DB::raw('COALESCE(buyback_counts.buyback_count, 0)'), $sortOrder);
        }elseif ($sortField === 'cashback_percentage') {
            $query->orderBy('ads.cashback_percentage', $sortOrder);
        } else {
            $query->orderBy($sortField, $sortOrder);
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

    public function toArray()
    {
        $data                           = parent::toArray();
        $data['price_without_cashback'] = $this->getPriceWithoutCashback();

        return $data;
    }
}
