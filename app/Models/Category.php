<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Category extends Model
{
    protected $guarded = [];

    public function parent(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    public function products(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Product::class, 'category_id', 'id');
    }

    /**
     * Возвращает ид "Без категории"
     */
    public function getDefaultCategory(): mixed
    {
        // Проверяем, есть ли категория в кеше
        $defaultCategory = Cache::get('default_category');

        if (! $defaultCategory) {
            $defaultCategory = Category::where('name', 'Без категории')->pluck('id')->first();
            if ($defaultCategory) {
                Cache::put('default_category', $defaultCategory, now()->addDays(15));
            }
        }

        return $defaultCategory;
    }

    public function img()
    {
        return $this->morphOne(File::class, 'fileable')
            ->where('category', 'img')
            ->withDefault([
                'src' => 'images/no_image.svg', // Путь к заглушке
                'id' => null, // Чтобы не было путаницы с реальными записями
                'fileable_type' => null,
                'fileable_id' => null,
                'category' => 'img',
                'status' => null,
                'status_comment' => null,
                'created_at' => null,
                'updated_at' => null,
            ]);
    }

    public function allChildren()
    {
        return $this->children()->with('allChildren');
    }

    public function getAllDescendantIds()
    {
        $ids = collect();

        foreach ($this->children as $child) {
            $ids->push($child->id);
            $ids = $ids->merge($child->getAllDescendantIds());
        }

        return $ids;
    }
}
