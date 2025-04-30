<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tariff extends Model
{
    protected $guarded = [];

    protected $casts = [
        'advantages' => 'array'
    ];
}
