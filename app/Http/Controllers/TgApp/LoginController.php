<?php

namespace App\Http\Controllers\TgApp;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tg\LoginController\CompleteRequest;
use App\Models\Role;
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
        $check = auth('tg')->check();
        if($check){
            return 'все ок';
        }else{
            return redirect()->route('tg.select', $request->chat_id);
        }

        /////
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
        return view('app.auth.contact', compact('role', 'user_id', 'chat_id'));
    }

    public function complete(string $user_id, string $phone_number, string $role, string $chatId, string $first_name = null, string $last_name = null)
    {
        // Ищем юзера по ид, либо создаем нового!
        $user = User::where('telegram_id', $user_id)->first();
        if(!$user){
            // create new
            $user = User::create([
                'name' => $first_name . ' ' . $last_name,
                'phone' => $phone_number,
                'password' => '-',
                'role_id' => Role::where('slug', $role)->pluck('id')->first(),
                'telegram_id' => $user_id
            ]);
            // Записываем в сессию!
            session(['telegram_user_id' => $user_id]);
        }else{
            session(['telegram_user_id' => $user_id]);
            if($role == 'seller'){
                return to_route('tg.dashboard');
            }
            return to_route('tg.index');
        }
        return view('app.auth.complete', compact('user'));
    }

    public function completeSave(CompleteRequest $request)
    {
        $user = auth('tg')->user()->first();
        $update = $user->update($request->validated());
        if($user->role?->slug == 'seller'){
            return to_route('tg.dashboard');
        }else{
            return to_route('tg.index');
        }
    }
}
