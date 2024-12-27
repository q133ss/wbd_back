<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ad extends Model
{
    protected $guarded = [];

    // Указываем глобальный скоуп в методе `booted`
    protected static function booted()
    {
        static::addGlobalScope('not_archived', function ($query) {
            $query->where('is_archived', false);
        });
    }

    // Метод для отключения глобального скоупа
    public static function withoutArchived()
    {
        return static::withoutGlobalScope('not_archived');
    }
}
