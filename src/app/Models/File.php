<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class File extends Model
{
    protected $guarded = [];

    public function getSrcAttribute($value)
    {
        return config('app.url').'/storage/'.$value;
    }
}
