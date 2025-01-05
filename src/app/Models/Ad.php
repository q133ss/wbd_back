<?php

namespace App\Models;

use App\Models\Scopes\NotArchiveScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\Request;

class Ad extends Model
{
    protected $guarded = [];

    protected $with = ['product'];

    // Указываем глобальный скоуп в методе `booted`
    protected static function booted()
    {
        static::addGlobalScope(new NotArchiveScope);
    }

    // Метод для отключения глобального скоупа
    public static function withoutArchived()
    {
        return static::withoutGlobalScope(NotArchiveScope::class);
    }

    public function scopeWithFilter($query, Request $request)
    {
        return $query
            ->when(
                $request->query('status'),
                function (Builder $query, $status) {
                    return $query->where('status', $status);
                }
            )
            ->when(
                $request->query('is_archived'),
                function (Builder $query, $isArchived) {
                    return static::withoutArchived()->where('is_archived', true);
                }
            );
    }

    public function product(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Product::class, 'id', 'product_id');
    }

    /**
     * Байбеки
     *
     * @return HasMany
     */
    public function buybacks()
    {
        return $this->hasMany(Buyback::class, 'ads_id', 'id');
    }

    public function user()
    {
        return $this->hasOne(User::class, 'id', 'user_id');
    }
}
