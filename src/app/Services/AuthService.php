<?php

namespace App\Services;

use App\Models\PhoneVerification;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;

class AuthService
{
    const CODE_EXPIRATION_TIME = 5; // Время истечения срока действия кода в минутах
    const MAX_ATTEMPTS = 5; // Максимальное число попыток ввода кода в один день

    /**
     * Отправляет код подтверждения регистрации
     * @param string $phoneNumber
     * @return JsonResponse
     * @throws \Exception
     */
    public function sendVerificationCode(string $phoneNumber)
    {
        $verification = PhoneVerification::where('phone_number' , $phoneNumber)->first();

        if($verification)
        {
            $time = Carbon::parse($verification->expires_at);

            if($time->isBefore(now())) {
                # TODO сюда не попадает почему-то
                // Хотя DD работает
                return Response()->json([
                    'message' => 'Код уже отправлен'
                ]);
            }
        }

        $code = random_int(1000,9999);

        if($verification)
        {
            $verification->update([
                'verification_code' => $code,
                'expires_at' => now()->addMinutes(self::CODE_EXPIRATION_TIME),
            ]);
        }else{
            PhoneVerification::create([
                'phone_number' => $phoneNumber,
                'verification_code' => $code,
                'expires_at' => now()->addMinutes(self::CODE_EXPIRATION_TIME)
            ]);
        }

        $smsService = new SmsService();
        //$send = $smsService->send($phoneNumber, $code);
        $send = true;

        if(!$send)
        {
            return Response()->json([
                'message' => 'При отправке СМС произошла ошибка'
            ], 500);
        }

        return Response()->json([
            'message' => 'Код успешно отправлен'
        ]);
    }
}
