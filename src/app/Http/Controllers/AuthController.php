<?php

namespace App\Http\Controllers;

use App\Http\Requests\AuthController\CompleteRequest;
use App\Http\Requests\AuthController\SendCodeRequest;
use App\Http\Requests\AuthController\VerifyCodeRequest;
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
        $this->authService->sendVerificationCode($request->phone);

        return response()->json(['message' => 'Код успешно отправлен']);
    }

    public function verifyCode(VerifyCodeRequest $request)
    {
        return $this->authService->verifyCode($request->phone, $request->code);
    }

    public function completeRegistration(CompleteRequest $request)
    {
        $user                  = Auth('sanctum')->user();
        $data                  = $request->validated();
        $data['is_configured'] = true;
        $updated               = $user->update($data);

        return Response()->json([
            'message' => 'true',
            'user'    => $user,
        ]);
    }
}
