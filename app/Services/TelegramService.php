<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Http;

class TelegramService
{
    private string $token;

    public function __construct()
    {
        $this->token = config('services.telegram.token');
    }

    // Метод для обработки входящих сообщений (вебхук)
    public function handleWebhook($update)
    {
        if(isset($update['message'])) {
            $chatId = $update['message']['chat']['id'];
            $text = $update['message']['text'] ?? '';

            if ($text === '/start') {
                $this->startCommand($chatId);
            } else {
                $this->sendMessage($chatId, 'Неизвестная команда');
            }
        }
    }

    // Метод для отправки сообщения
    public function sendMessage($chatId, $text, $keyboard = []): void
    {
        $token = $this->token;
        $url = "https://api.telegram.org/bot$token/sendMessage";

        // Создаем базовый массив данных для отправки
        $data = [
            'chat_id' => $chatId,
            'text' => $text,
        ];

        // Если передан массив $keyboard, добавляем его как InlineKeyboard
        if (!empty($keyboard)) {
            $data['reply_markup'] = json_encode([
                'inline_keyboard' => $keyboard,
            ]);
        }

        // Отправляем запрос к Telegram API
        file_get_contents($url, false, stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => 'Content-Type: application/json',
                'content' => json_encode($data),
            ],
        ]));
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
        if (!$user) {
            $registrationLink = config('app.frontend_url') . '/register'; // Ссылка на страницу регистрации на сайте
            $message = "⚠️ Вы пока не зарегистрированы в системе. Для начала работы пройдите регистрацию на нашем сайте.";
            $keyboard = [
                'inline_keyboard' => [
                    [['text' => '📝 Перейти на сайт', 'url' => $registrationLink]],
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
