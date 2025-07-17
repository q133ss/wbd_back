<?php

namespace App\Http\Controllers\TgApp;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class LoginController extends Controller
{
    public function select()
    {
        return view('app.auth.select');
    }

    public function conditions(string $role, string $chat_id)
    {
        auth('sanctum')->loginUsingId(1);
        // Отправляем сообщение в ТГ, затем
        return view('app.auth.conditions');
    }
}
