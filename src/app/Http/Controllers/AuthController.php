<?php

namespace App\Http\Controllers;

use App\Http\Requests\AuthController\SendCodeRequest;
use App\Models\User;
use App\Services\AuthService;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    protected $authService;

    public function __construct(AuthService $phoneAuthService)
    {
        $this->authService = $phoneAuthService;
    }

    public function sendCode(SendCodeRequest $request)
    {
        $this->authService->sendVerificationCode($request->phone);

        return response()->json(['message' => 'Код успешно отправлен']);
    }

    public function verifyCode($request)
    {
        $isValid = $this->authService->verifyCode($request->phone_number, $request->verification_code);

        if (!$isValid) {
            return response()->json(['message' => 'Недействительный или истекший код'], 400);
        }

        // Создаём пользователя
        $user = User::create([
            'phone_number' => $request->phone_number,
            'name' => $request->name,
            'surname' => $request->surname,
            // другие поля
        ]);

        return response()->json(['message' => 'Registration completed successfully', 'user' => $user]);
    }
}
