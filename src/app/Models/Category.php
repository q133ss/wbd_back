<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Category extends Model
{
    protected $guarded = [];

    public function parent()
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    /**
     * Возвращает ид "Без категории"
     * @return mixed
     */
    public function getDefaultCategory(): mixed
    {
        // Проверяем, есть ли категория в кеше
        $defaultCategory = Cache::get('default_category');

        if (!$defaultCategory) {
            $defaultCategory = Category::where('name', 'Без категории')->pluck('id')->first();
            // Если категория найдена, кешируем её на 10 минут
            if ($defaultCategory) {
                Cache::put('default_category', $defaultCategory, now()->addDays(15));
            }
        }

        return $defaultCategory;
    }
}
