<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Promocode extends Model
{
    //// 1) Скидка на тариф (например, 20%)
    //{
    //    "type": "discount",
    //    "tariff_name": "Премиум",
    //    "discount_percent": 20
    //}
    //
    //// 2) Дополнительные дни при покупке тарифа (+15 дней)
    //{
    //    "type": "extra_days",
    //    "tariff_name": "Премиум",
    //    "extra_days": 15
    //}
    //
    //// 3) Полностью бесплатный тариф
    //{
    //    "type": "free_tariff",
    //    "tariff_name": "Бизнес"
    //}
    //
    //// 4) Кастомный тариф (14 дней, 50 выкупов)
    //{
    //    "type": "custom_tariff",
    //    "tariff_name": "Superstar 1 месяц",
    //    "duration_days": 14,
    //    "products_count": 50,
    //    "price_paid": 0
    //}

    protected $guarded = [];

    protected $casts = [
        'data' => 'array'
    ];

    public function users()
    {
        return $this->belongsToMany(User::class, 'promocode_user')->withTimestamps();
    }
}
