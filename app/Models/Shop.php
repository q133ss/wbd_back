<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Shop extends Model
{
    protected $guarded = [];

    protected $hidden = [
        'laravel_through_key',
    ];

    public function products(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Product::class, 'shop_id', 'id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
