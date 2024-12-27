<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

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
    }

    /**
     * Объявления
     *
     * @return HasMany
     */
    public function ads(): HasMany
    {
        return $this->hasMany(Ad::class, 'product_id', 'id');
    }

    public function shop(): HasOne
    {
        return $this->hasOne(Shop::class, 'id', 'shop_id');
    }

    public function toArray(): array
    {
        $data             = parent::toArray();
        $data['category'] = $this->category;

        return $data;
    }
}
