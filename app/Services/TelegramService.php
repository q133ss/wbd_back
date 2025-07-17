<?php

namespace App\Services;

use App\Models\User;
use CURLFile;
use Illuminate\Support\Facades\Http;

class TelegramService
{
    private string $token;
    private string $clientToken;

    public function __construct()
    {
        $this->token = config('services.telegram.token');
        $this->clientToken = config('services.telegram.client_token');
    }

    // Метод для обработки входящих сообщений (вебхук)
    public function handleWebhook($update)
    {
        if(isset($update['message'])) {
            $chatId = $update['message']['chat']['id'];
            $text = $update['message']['text'] ?? '';

            // Проверяем, является ли текст командой /start с параметром
            if (strpos($text, '/start') === 0) {
                $startPayload = trim(str_replace('/start', '', $text));
                $this->startCommand($chatId, $startPayload);
            } else {
                $this->sendMessage($chatId, 'Неизвестная команда');
            }
        }
    }

    // Метод для отправки сообщения
    public function sendMessage($chatId, $text, array $keyboard = []): void {
        // Экранируем текст MarkdownV2 (если используете Markdown)
        $escaped = preg_replace_callback(
            '/[_\*\[\]\(\)~`>#\+\-=|{}\.\!]/',
            fn($m) => '\\' . $m[0],
            $text
        );

        $data = [
            'chat_id'    => $chatId,
            'text'       => $escaped,
            'parse_mode' => 'MarkdownV2',
        ];

        if (!empty($keyboard)) {
            $data['reply_markup'] = $keyboard; // Уже передаем готовую структуру клавиатуры
        }

        $ch = curl_init("https://api.telegram.org/bot{$this->token}/sendMessage");
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data, JSON_UNESCAPED_UNICODE));

        $response = curl_exec($ch);
        curl_close($ch);
    }



    // Отправка файла
    public function sendFile($chatId, $filePath, $caption = '', $keyboard = []): void
    {
        $url = "https://api.telegram.org/bot$this->token/sendDocument";

        // Формируем данные для multipart/form-data
        $postFields = [
            'chat_id' => $chatId,
            'caption' => $caption,
            'document' => new CURLFile(realpath($filePath)),
        ];

        // Добавляем клавиатуру, если она есть
        if (!empty($keyboard)) {
            $postFields['reply_markup'] = json_encode([
                'inline_keyboard' => $keyboard,
            ]);
        }

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $postFields,
        ]);

        $response = curl_exec($ch);
        curl_close($ch);
    }

    // Команда /start
    private function startCommand($chatId, $startPayload = null): void
    {
        if ($startPayload) {
            // Пытаемся найти пользователя по токену
            $userId = cache()->get("telegram_auth:{$startPayload}");

            if ($userId) {
                $user = User::find($userId);

                if ($user) {
                    $user->update(['telegram_id' => $chatId]);
                    cache()->forget("telegram_auth:{$startPayload}");

                    $this->sendMessage($chatId, "✅ Вы успешно привязали аккаунт!");
                    return;
                }
            }
        }

        $welcomeMessage = "👋 Добро пожаловать в бот WBDiscount!

Этот бот станет вашим персональным помощником для управления выкупами и отслеживания финансовых операций. Вот что он может делать для вас:

✅ **Уведомления о новых выкупах** — получайте мгновенные оповещения, когда появляется новый выкуп.

✅ **Уведомления о изменении статуса выкупа** — всегда будьте в курсе текущего статуса ваших выкупов: от создания до получения отзыва.

✅ **Финансовые операции** — следите за всеми транзакциями, изменениями баланса и начислениями.

✅ **Сообщения** — получайте уведомления о новых сообщениях и оперативно реагируйте на них.

✅ **И многое другое** — бот поможет вам эффективно управлять вашими выкупами на WBDiscount.";
        $this->sendMessage($chatId, $welcomeMessage);

        // Проверяем пользователя в БД
        $user = User::where('telegram_id', $chatId)->first();
        $webAppUrl = config('app.web_app_url'). '?chat_id=' . $chatId;
        if (!$user) {
            $message = "⚠️ Вы пока не зарегистрированы в системе. Для начала работы пройдите регистрацию на нашем сайте.";
            $keyboard = [
                'inline_keyboard' => [
                    [
                        [
                            'text' => '🚀 Открыть приложение',
                            'web_app' => ['url' => $webAppUrl]
                        ]
                    ]
                ],
            ];
            $this->sendMessage($chatId, $message, $keyboard);
        }
    }

    public function sendNotification(string $user_id, string $text): void
    {
        try{
            $chatId = User::where('id',$user_id)->pluck('telegram_id')->first();
            if($chatId != null){
                $this->sendMessage($chatId, $text);
            }
        }catch (\Exception $exception){
            \Log::error('TelegramService sendNotification error: ' . $exception->getMessage());
            return;
        }
    }

    // Генерация ссылки на телеграм

    public function generateAuthLink(User $user): string
    {
        $botUsername = config('services.telegram.username'); // Получаем username бота из конфига
        $token = $this->generateUserToken($user);

        return "https://t.me/{$botUsername}?start={$token}";
    }

    private function generateUserToken(User $user): string
    {
        // Генерируем уникальный токен для пользователя
        $token = hash_hmac('sha256', $user->id, config('app.key'));

        // Сохраняем токен в кеш на 24 часа
        cache()->put("telegram_auth:{$token}", $user->id, now()->addDay());

        return $token;
    }
}
