<?php

namespace App\Http\Controllers\TgApp;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\TelegramService;
use Illuminate\Http\Request;

class LoginController extends Controller
{
    private TelegramService $tgService;

    public function __construct()
    {
        $this->tgService = new TelegramService();
    }

    public function index(Request $request)
    {
        $user = User::where('telegram_id', $request->chat_id)->first();

        if($user){
            auth()->guard('sanctum')->setUser($user);
            return 'auth!!!';
        }else{
            return redirect()->route('tg.select', $request->chat_id);
        }
    }

    public function select(string $chatId)
    {
        return view('app.auth.select', compact('chatId'));
    }

    public function conditions(string $role, string $user_id, string $chat_id)
    {
        // Отправляем сообщение с файлами политики и отображаем view
        $this->tgService->sendFile($chat_id, base_path('public/conditions.docx'));
        $this->tgService->sendFile($chat_id, base_path('public/policy.docx'));

        // Все ок!
        return view('app.auth.conditions', compact('role', 'user_id', 'chat_id'));
    }

    public function getContact(string $role, string $user_id, string $chat_id)
    {
        // Ищем юзера по ид, либо создаем нового!
        // Тут юзер еще не нужен вроде!
        //        $user = User::where('telegram_id', $user_id)->first();
        //        if(!$user){
        //            // create new
        //        }else{
        //            auth()->guard('sanctum')->setUser($user);
        //        }

        return view('app.auth.contact', compact('role', 'user_id', 'chat_id'));
    }
}
