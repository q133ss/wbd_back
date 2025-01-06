<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    protected $guarded = [];
    protected $with = ['user'];

    public function user()
    {
        return $this->hasOne(User::class, 'id', 'user_id');
    }

    public function toArray()
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'ads_id' => $this->ads_id,
            'user_name' => $this->user ? $this->user?->name : null,
            'rating' => $this->rating,
            'text' => $this->text,
            'created_at' => $this->created_at ? Carbon::parse($this->created_at)->translatedFormat('j F, Y') : null,
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s')
        ];
    }
}
