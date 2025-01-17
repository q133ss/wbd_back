<?php

namespace App\Models;

use App\Models\Scopes\NotArchiveScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Http\Request;

class Product extends Model
{
    protected $guarded = [];

    protected $casts = [
        'images' => 'array',
    ];

    protected $with = ['category'];

    /**
     * Категория
     */
    public function category(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Category::class, 'id', 'category_id');
    }

    /**
     * При создании проверяем категорию
     * если она пустая, ставим "без категории"
     *
     * @return void
     */
    protected static function booted()
    {
        static::creating(function ($product) {
            // Проверка категории
            if (! $product->category_id) {
                $product->category_id = (new Category)->getDefaultCategory();
            }
        });
        static::addGlobalScope(new NotArchiveScope);
    }

    /**
     * Объявления
     */
    public function ads(): HasMany
    {
        return $this->hasMany(Ad::class, 'product_id', 'id');
    }

    public function activeAd(): HasOne
    {
        return $this->hasOne(Ad::class, 'product_id', 'id')->where('status', true);
    }

    public function shop(): HasOne
    {
        return $this->hasOne(Shop::class, 'id', 'shop_id');
    }

    // Метод для отключения глобального скоупа
    public static function withoutArchived()
    {
        return static::withoutGlobalScope(NotArchiveScope::class);
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
            );
    }

    public function toArray(): array
    {
        $data             = parent::toArray();
        $data['category'] = $this->category;

        return $data;
    }
}
