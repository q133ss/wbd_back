<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SmsService
{
    private $email;

    private $apiKey;

    public function __construct()
    {
        $this->email  = config('sms.email');
        $this->apiKey = config('sms.api_key');
    }

    public function send(string $phone, string $message)
    {
        // Создаем заголовок для авторизации
        $authHeader = 'Basic '.base64_encode("{$this->email}:{$this->apiKey}");

        // Отправка запроса через Http фасад
        $response = Http::withHeaders([
            'Authorization' => $authHeader,
            'Content-Type'  => 'application/json',
        ])->post('https://gate.smsaero.ru/v2/sms/send', [
            'sign'   => 'SMS Aero',
            'number' => $phone,
            'text'   => $message,
        ]);

        // Проверка ответа
        if ($response->successful()) {
            return true;
        } else {
            Log::error('Ошибка отправки SMS', [
                'phone'    => $phone,
                'message'  => $message,
                'response' => $response->body(),
                'status'   => $response->status(),
            ]);

            return false;
        }
    }
}
