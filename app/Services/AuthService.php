<?php

namespace App\Services;

use App\Models\PhoneVerification;
use App\Models\ReferralStat;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

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
            if ($verification->updated_at > now()->subMinute()) {
                return Response()->json([
                    'message' => 'Отправлять код можно не чаще 1 раза в минуту',
                ], 429);
            }

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

    public function verifyCode(string $phone, string $code, int $role_id, $ip = null): JsonResponse|array
    {
        try {
            DB::beginTransaction();
            $verification = $this->checkCode($phone, $code);

            if ($verification->exists()) {
                $data = [
                    'phone' => $phone,
                    'password' => '-',
                    'name' => '-',
                    'role_id' => $role_id,
                ];

                $ref = Cache::get("ref_{$ip}");
                if ($ref != null) {
                    $data['referral_id'] = $ref;

                    ReferralStat::updateOrCreate(
                        ['user_id' => $ref]
                    )->increment('registrations_count');
                }

                $user = User::create($data);
                $verification->delete();

                // Создаем пробный тариф для продавца
                if($role_id == Role::where('slug', 'seller')->pluck('id')->first()){
                    $tariff = \App\Models\Tariff::where('name', 'Пробный')->first();
                    DB::table('user_tariff')->insert([
                        'user_id' => $user->id,
                        'tariff_id' => $tariff->id,
                        'end_date' => now()->addDays(3),
                        'products_count' => 10,
                        'variant_name' => '3 дня',
                        'duration_days' => 3,
                        'price_paid' => 0
                    ]);
                }

                $token = $user->createToken('web');

                DB::commit();

                return [
                    'user' => $user,
                    'token' => $token->plainTextToken,
                ];
            } else {
                return Response()->json(['message' => 'Неверный код'], 401);
            }
        }catch (\Exception $e) {
            DB::rollBack();
            return Response()->json(['message' => 'Ошибка сервера'], 500);
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
            $user   = User::where('phone', $phone)->first();
            $update = $user->update(['password' => Hash::make($password)]);

            return Response()->json([
                'message' => 'Пароль успешно сброшен',
            ]);
        } else {
            return Response()->json(['message' => 'Неверный код'], 401);
        }
    }

    public function resetVerifyCode(string $phone, string $code)
    {
        if ($this->checkCode($phone, $code)->exists()) {
            return response()->json([
                'status'  => 'true',
                'message' => 'Код верный',
            ]);
        }

        return response()->json([
            'status'  => 'false',
            'message' => 'Неверный код',
        ], 400);
    }
}
