<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TelegramSeller extends Model
{
    use HasFactory;

    protected $fillable = [
        'group_slug',
        'message_id',
        'author',
        'message_text',
        'posted_at',
        'message_link',
    ];

    protected $casts = [
        'posted_at' => 'datetime',
    ];
}
