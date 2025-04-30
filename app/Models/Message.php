<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    protected $guarded = [];

    const VIOLET_COLOR = '#6941C6';

    protected static function booted()
    {
        // Дефолтная сортировка
        static::addGlobalScope('order', function (Builder $builder) {
            $builder->orderBy('messages.created_at', 'asc');
        });
    }

    public function file()
    {
        return $this->morphOne(File::class, 'fileable');
    }

    public function files()
    {
        return $this->morphMany(File::class, 'fileable');
    }
}
