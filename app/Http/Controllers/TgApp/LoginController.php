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

    public function conditions(string $role)
    {
        // Отправляем сообщение в ТГ, затем
    }
}
