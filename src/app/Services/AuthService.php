<?php

namespace App\Services;

use App\Models\PhoneVerification;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class AuthService
{
    const CODE_EXPIRATION_TIME = 5; // Время истечения срока действия кода в минутах

    const MAX_ATTEMPTS = 5; // Максимальное число попыток ввода кода в один день

    /**
     * Отправляет код подтверждения регистрации
     *
     * @return JsonResponse
     *
     * @throws \Exception
     */
    public function sendVerificationCode(string $phoneNumber)
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

    public function verifyCode(string $phone, string $code)
    {
        $verification = PhoneVerification::where('phone_number', $phone)
            ->where('verification_code', $code)->exists();
        if ($verification) {
            $verification->delete();

            $user = User::create([
                'phone'    => $phone,
                'password' => '-',
                'name'     => '-',
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
}
