<?php

namespace App\Http\Controllers;

use App\Http\Requests\AuthController\CompleteRequest;
use App\Http\Requests\AuthController\LoginRequest;
use App\Http\Requests\AuthController\ResetCheckCodeRequest;
use App\Http\Requests\AuthController\ResetRequest;
use App\Http\Requests\AuthController\ResetSendCodeRequest;
use App\Http\Requests\AuthController\SendCodeRequest;
use App\Http\Requests\AuthController\VerifyCodeRequest;
use App\Models\Role;
use App\Models\Template;
use App\Models\User;
use App\Services\AuthService;

class AuthController extends Controller
{
    protected $authService;

    public function __construct(AuthService $phoneAuthService)
    {
        $this->authService = $phoneAuthService;
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

        $template = new Template();
        $template->createDefault($user->id);

        return Response()->json([
            'message' => 'true',
            'user'    => $user,
        ]);
    }

    public function reset(ResetRequest $request)
    {
        return $this->authService->reset($request->phone, $request->code, $request->password);
    }

    public function login(LoginRequest $request)
    {
        $user  = User::where('phone', $request->phone)->first();

        if($request->role_id != null){
            $updated = $user->update(['role_id' => $request->role_id]);
        }

        $token = $user->createToken('web');

        return [
            'user'  => $user,
            'token' => $token->plainTextToken,
        ];
    }

    public function resetSendCode(ResetSendCodeRequest $request)
    {
        $this->authService->sendVerificationCode($request->phone);

        return response()->json(['message' => 'Код успешно отправлен']);
    }

    public function resetVerifyCode(ResetCheckCodeRequest $request)
    {
        return $this->authService->resetVerifyCode($request->phone, $request->code);
    }

    public function roles()
    {
        return Role::where('slug', '!=', 'admin')->get();
    }
}
