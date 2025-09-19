<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AutopostSetting extends Model
{
    protected $guarded = [];

    protected $casts = [
        'is_enabled' => 'boolean',
        'show_price' => 'boolean',
        'show_cashback' => 'boolean',
        'show_conditions' => 'boolean',
        'show_photo' => 'boolean',
        'show_link' => 'boolean',
    ];
}
