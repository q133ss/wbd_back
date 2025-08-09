<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tariff extends Model
{
    protected $guarded = [];

    const TRIAL_PLAN = 'Пробный'; // Название пробного тарифа
    const TRIAL_PLAN_COUNT = 10; // Максимальное количество выкупов в пробном тарифе
    protected $casts = [
        'data' => 'array',
        'product_ids' => 'array'
    ];
}
