<?php

namespace App\Models;

use App\Models\Scopes\NotArchiveScope;
use Illuminate\Database\Eloquent\Model;

class Cashout extends Model
{
    protected $guarded = [];

    protected static function booted()
    {
        static::addGlobalScope(new NotArchiveScope);
    }

    public function user(): void
    {
        $this->belongsTo(User::class, 'id', 'user_id');
    }
}
