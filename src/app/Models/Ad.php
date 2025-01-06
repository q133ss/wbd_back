<?php

namespace App\Models;

use App\Models\Scopes\NotArchiveScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\Request;

class Ad extends Model
{
    protected $guarded = [];

    protected $with = ['product'];

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

    function joined($query, $table) {
        $joins = $query->getQuery()->joins;
        if($joins == null) {
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
            );
    }

    public function scopeWithSorting($query, Request $request)
    {
        // Получаем все параметры запроса
        $sortParams = $request->query();

        // Поля для groupBy
        $adsFields = [
            'ads.id',
            'ads.product_id',
            'ads.name',
            'ads.cashback_percentage',
            'ads.price_with_cashback',
            'ads.order_conditions',
            'ads.redemption_instructions',
            'ads.review_criteria',
            'ads.redemption_count',
            'ads.views_count',
            'ads.one_per_user',
            'ads.is_archived',
            'ads.status',
            'ads.balance',
            'ads.user_id',
            'ads.created_at',
            'ads.updated_at'
        ];

        $productsFields = [
            'products.id',
            'products.wb_id',
            'products.name',
            'products.price',
            'products.brand',
            'products.discount',
            'products.rating',
            'products.quantity_available',
            'products.supplier_id',
            'products.category_id',
            'products.description',
            'products.supplier_rating',
            'products.is_archived',
            'products.shop_id',
            'products.images',
            'products.status',
            'products.created_at',
            'products.updated_at'
        ];

        $reviewsFields = [
            'reviews.id',
            'reviews.user_id',
            'reviews.ads_id',
            'reviews.rating',
            'reviews.text',
            'reviews.reviewable_type',
            'reviews.reviewable_id',
            'reviews.created_at',
            'reviews.updated_at'
        ];

        // Проверка и применение сортировки
        foreach ($sortParams as $field => $order) {
            $validColumns = [
                'created_at',
                'price_with_cashback',
                'rating_product',
                'rating_seller',
                'popular'
            ];

            // Проверка на допустимые значения столбцов и порядка сортировки
            if (in_array($field, $validColumns) && in_array($order, ['asc', 'desc'])) {
                // Изначально включаем только поля из ads
                $groupByFields = $adsFields;
                if ($field === 'rating_product') {
                    if (!$this->joined($query, 'products')) {
                        $query->join('products', 'products.id', '=', 'ads.product_id');
                    }

                    if (!$this->joined($query, 'reviews')) {
                        $query->join('reviews', 'products.id', '=', 'reviews.reviewable_id');
                    }
                    $query->where('reviews.reviewable_type', 'App\Models\Product');
                    $groupByFields = array_merge($groupByFields, $productsFields, $reviewsFields);
                    $query->groupBy($groupByFields);
                    $query->orderByRaw('AVG(reviews.rating) ' . $order);
                } elseif ($field === 'rating_seller') {
                    // Сортировка по рейтингу продавца
                    if (!$this->joined($query, 'reviews')) {
                        $query->join('reviews', 'ads.user_id', '=', 'reviews.reviewable_id');
                    }
                    $query->where('reviews.reviewable_type', 'App\Models\User');
                    $groupByFields = array_merge($groupByFields, $reviewsFields);
                    $query->groupBy($groupByFields);
                    $query->orderByRaw('AVG(reviews.rating) ' . $order);
                } elseif ($field === 'popular') {
                    // Сортировка по популярности (например, по количеству покупок)
                    $query->orderBy('ads.number_of_purchases', $order);
                } else {
                    // Сортировка по другим полям
                    $query->orderBy($field, $order);
                }
            }
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
}
