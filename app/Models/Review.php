<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    protected $guarded = [];

    protected $with = ['user'];

    public function user()
    {
        return $this->hasOne(User::class, 'id', 'user_id');
    }

    public function ads()
    {
        return $this->hasOne(Ad::class, 'id', 'ads_id');
    }

    public function toArray()
    {
        return [
            'id'         => $this->id,
            'user_id'    => $this->user_id,
            'ads_id'     => $this->ads_id,
            'ads_name'   => $this->ads?->name,
            'user_name'  => $this->user ? $this->user?->name : null,
            'rating'     => $this->rating,
            'text'       => $this->text,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
