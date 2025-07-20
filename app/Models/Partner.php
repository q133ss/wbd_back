<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Partner extends Model
{
    protected $guarded = [];

    public function category(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(PartnerCategory::class, 'id', 'category_id');
    }

    public function img()
    {
        return $this->morphOne(File::class, 'fileable')->where('category', 'img');
    }
}
