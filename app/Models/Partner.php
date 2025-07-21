<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

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

    public function scopeWithFilter($query, Request $request)
    {
        return $query
            ->when(
                $request->has('category_id'),
                function (Builder $query) use ($request) {
                    $category = $request->get('category_id');
                    if($category != 0){
                        return $query->where('partners.category_id', $request->get('category_id'));
                    }
                }
            );
    }
}
