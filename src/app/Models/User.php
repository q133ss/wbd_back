<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'is_configured',
        'role_id',
        'redemption_count',
        'balance',
    ];

    protected $with = ['shop'];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
        ];
    }

    public function role(): HasOne
    {
        return $this->hasOne(Role::class, 'id', 'role_id');
    }

    public function avatar()
    {
        return $this->morphOne(File::class, 'fileable')
            ->where('category', 'avatar');
    }

    public function ads()
    {
        return $this->hasMany(Ad::class, 'user_id', 'id');
    }

    public function getRating()
    {
        $totalRating = 0;
        $adCount = 0;
        foreach ($this->ads()->get() as $ad){
            $totalRating += $ad->getAvgRating();
            $adCount++;
        }

        if ($adCount === 0) {
            return 0;
        }

        return round($totalRating, 1);
    }

    public function toArray(): array
    {
        return [
            'id'            => $this->id,
            'avatar'        => $this->avatar?->src,
            'rating'        => $this->getRating(),
            'name'          => $this->name,
            'phone'         => $this->phone,
            'email'         => $this->email,
            'created_at'    => $this->created_at,
            'is_configured' => $this->is_configured,
            'shop'          => $this->shop,
            'role'          => $this->role,
        ];
    }

    /**
     * Возвращает магазин юзера
     */
    public function shop(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Shop::class, 'user_id', 'id');
    }

    /**
     * Проверяет, принадлежит-ли товар юзеру
     */
    public function checkProduct(int $productId): bool
    {
        $id = $this->id;

        return Product::where('id', $productId)
            ->where('shop_id', function ($query) use ($id) {
                return $query
                    ->select('id')
                    ->from('shops')
                    ->where('user_id', $id);
            })
            ->exists();
    }

    /**
     * Проверяет массив товаров
     *
     * @return mixed
     */
    public function checkProducts(array $productIds): bool
    {
        $userId     = $this->id;
        $foundCount = Product::whereIn('id', $productIds)
            ->whereHas('shop', function ($query) use ($userId) {
                $query->where('user_id', $userId);
            })
            ->count();

        return $foundCount === count($productIds);
    }

    /**
     * Проверяет массив объявлений
     * @param array $adsIds
     * @return bool
     */
    public function checkAd(array $adsIds): bool
    {
        $userId = $this->id;

        $foundCount = Ad::select('ads.id')
            ->leftJoin('products', 'ads.product_id', '=', 'products.id')
            ->leftJoin('shops', 'products.shop_id', '=', 'shops.id')
            ->leftJoin('users', 'shops.user_id', '=', 'users.id')
            ->where('users.id', $userId)
            ->whereIn('ads.id', $adsIds)
            ->count();

        return $foundCount === count($adsIds);
    }

    public function promocodes()
    {
        return $this->belongsToMany(Promocode::class, 'promocode_user')->withTimestamps();
    }

    /**
     * Список выкупов юзера
     *
     * @return HasManyThrough
     */
    public function buybacks(): HasManyThrough
    {
        return $this->hasManyThrough(Buyback::class, Ad::class, 'user_id', 'ads_id', 'id', 'id')
            ->orWhere('buybacks.user_id', $this->id)
            ->with([
                'ad' => function ($query) {
                    $query->with(['user']);
                },
                'user'
            ]);
    }

    public function transactions(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Transaction::class, 'user_id', 'id')->orderBy('created_at', 'desc');
    }

    public function isSeller(): bool
    {
        $seller = Role::where('slug', 'seller')->first();
        return $this->role()->is($seller);
    }

    public function reviews(): \Illuminate\Database\Eloquent\Relations\MorphMany
    {
        return $this->morphMany(Review::class, 'reviewable');
    }

    public function frozenBalance()
    {
        return $this->hasMany(FrozenBalance::class, 'user_id', 'id');
    }
}
