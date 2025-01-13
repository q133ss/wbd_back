<?php

namespace App\Services;

use App\Models\PhoneVerification;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AuthService
{
    const CODE_EXPIRATION_TIME = 5; // Время истечения срока действия кода в минутах

    const MAX_ATTEMPTS = 5; // Максимальное число попыток ввода кода в один день

    /**
     * Отправляет код подтверждения регистрации
     *
     *
     * @throws \Exception
     */
    public function sendVerificationCode(string $phoneNumber): JsonResponse
    {
        $verification = PhoneVerification::where('phone_number', $phoneNumber)->first();
        $code         = random_int(1000, 9999);

        if ($verification) {
            $verification->update([
                'verification_code' => $code,
                'expires_at'        => now()->addMinutes(self::CODE_EXPIRATION_TIME),
            ]);
        } else {
            PhoneVerification::create([
                'phone_number'      => $phoneNumber,
                'verification_code' => $code,
                'expires_at'        => now()->addMinutes(self::CODE_EXPIRATION_TIME),
            ]);
        }

        $smsService = new SmsService;
        $send       = $smsService->send($phoneNumber, $code);

        if (! $send) {
            return Response()->json([
                'message' => 'При отправке СМС произошла ошибка',
            ], 500);
        }

        return Response()->json([
            'message' => 'Код успешно отправлен',
        ]);
    }

    private function checkCode($phone, $code): mixed
    {
        return PhoneVerification::where('phone_number', $phone)
            ->where('verification_code', $code);
    }

    public function verifyCode(string $phone, string $code, int $role_id): JsonResponse|array
    {
        $verification = $this->checkCode($phone, $code);
        if ($verification->exists()) {
            $user = User::create([
                'phone'    => $phone,
                'password' => '-',
                'name'     => '-',
                'role_id'  => $role_id,
            ]);

            $token = $user->createToken('web');

            return [
                'user'  => $user,
                'token' => $token->plainTextToken,
            ];
        } else {
            return Response()->json(['message' => 'Неверный код'], 401);
        }
    }

    /**
     * Восстановление пароля
     */
    public function reset(string $phone, string $code, string $password): JsonResponse
    {
        $verification = $this->checkCode($phone, $code);
        if ($verification->exists()) {
            $verification->delete();
            $user     = User::where('phone', $phone)->first();
            $update   = $user->update(['password' => Hash::make($password)]);

            return Response()->json([
                'message'  => 'Пароль успешно сброшен'
            ]);
        } else {
            return Response()->json(['message' => 'Неверный код'], 401);
        }
    }

    public function resetVerifyCode(string $phone, string $code)
    {
        if($this->checkCode($phone, $code)->exists()){
            return response()->json([
                'status' => 'true',
                'message' => 'Код верный'
            ]);
        }

        return response()->json([
            'status' => 'false',
            'message' => 'Неверный код'
        ], 400);
    }
}
