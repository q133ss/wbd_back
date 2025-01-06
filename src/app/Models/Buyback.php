<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Buyback extends Model
{
    protected $guarded = [];

    public function ad()
    {
        return $this->hasOne(Ad::class, 'id', 'ads_id');
    }

    public function user()
    {
        return $this->hasOne(User::class, 'id', 'user_id');
    }

    public function messages()
    {
        return $this->hasMany(Message::class, 'buyback_id','id')->orderBy('created_at', 'desc');
    }
}
