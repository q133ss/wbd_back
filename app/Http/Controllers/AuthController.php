<?php

namespace App\Http\Controllers;

use App\Http\Requests\AuthController\CompleteRequest;
use App\Http\Requests\AuthController\LoginRequest;
use App\Http\Requests\AuthController\RegisterRequest;
use App\Http\Requests\AuthController\ResetCheckCodeRequest;
use App\Http\Requests\AuthController\ResetPasswordRequest;
use App\Http\Requests\AuthController\ResetRequest;
use App\Http\Requests\AuthController\ResetSendCodeRequest;
use App\Http\Requests\AuthController\ResetSendLinkRequest;
use App\Http\Requests\AuthController\SendCodeRequest;
use App\Http\Requests\AuthController\VerifyCodeRequest;
use App\Models\Role;
use App\Models\Template;
use App\Models\User;
use App\Services\AuthService;
use App\Services\TelegramService;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    protected $authService;
    protected $telegramService;

    public function __construct(AuthService $phoneAuthService, TelegramService $telegramService)
    {
        $this->authService = $phoneAuthService;
        $this->telegramService = $telegramService;
    }

    public function sendCode(SendCodeRequest $request)
    {
        return $this->authService->sendVerificationCode($request->phone);
    }

    public function verifyCode(VerifyCodeRequest $request)
    {
        $ip = $request->ip();
        return $this->authService->verifyCode($request->phone, $request->code, $request->role_id, $ip);
    }

    public function completeRegistration(CompleteRequest $request)
    {
        $user                  = Auth('sanctum')->user();
        $data                  = $request->validated();
        $data['is_configured'] = true;
        $updated               = $user->update($data);

        if($user->role?->slug === 'seller') {
            $template = new Template();
            $template->createDefault($user->id);
        }

        return Response()->json([
            'message' => 'true',
            'user'    => $user,
        ]);
    }

    public function login(LoginRequest $request)
    {
        $user  = User::where('phone', $request->phone)->where('role_id', $request->role_id)->first();
        $token = $user->createToken('web');

        return [
            'user'  => $user,
            'token' => $token->plainTextToken,
        ];
    }

    public function roles()
    {
        return Role::where('slug', '!=', 'admin')->get();
    }

    public function userByTelegramId(string $telegramId)
    {
        $user = User::where('telegram_id', $telegramId)->first();
        if(!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }
        return response()->json([
            'user' => $user,
            'token' => $user->createToken('web')->plainTextToken,
        ]);
    }

    public function register(RegisterRequest $request)
    {
        $data = $request->validated();
        $data['is_configured'] = true;
        $user = User::create($data);
        $token = $user->createToken('web');

        return response()->json([
            'user'  => $user,
            'token' => $token->plainTextToken,
        ]);
    }

    // Отправляет ссылку в ТГ
    public function resetSendLink(ResetSendLinkRequest $request)
    {
        $role = $request->for_seller ? 'seller' : 'buyer';
        $user = User::where('phone', $request->phone)
            ->whereHas('role', function ($query) use ($role) {
                $query->where('slug', $role);
            })
            ->first();

        $updated = $user->update([
            'reset_token' => Str::random(10),
        ]);

        $this->telegramService->sendMessage($user->telegram_id, "Для сброса пароля перейдите по ссылке: " . env('FRONTEND_URL') . "/forgot-password?role=$role&token=" . $user->reset_token, [], $request->for_seller);
        return response()->json(['message' => 'Ссылка отправлена в Telegram.']);
    }

    // Смена пароля!!!
    public function resetPassword(ResetPasswordRequest $request): \Illuminate\Http\JsonResponse
    {
        $user = User::where('reset_token', $request->reset_token)->first();
        $user->update([
            'password' => bcrypt($request->password),
            'reset_token' => null,
        ]);

        return response()->json([
            'token' => $user->createToken('web')->plainTextToken,
            'user'  => $user
        ]);
    }
}
