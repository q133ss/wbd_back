<?php

namespace App\Services;

use App\Models\ReferralStat;
use App\Models\Role;
use App\Models\Tariff;
use App\Models\Template;
use App\Models\User;
use CURLFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

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
        return $this->handleWebhookCommon($update, true);
    }

    public function handleWebhookClient($update)
    {
        return $this->handleWebhookCommon($update, false);
    }

    private function handleWebhookCommon(array $update, bool $forSeller)
    {
        try {
            if (isset($update['message'])) {
                $chatId = $update['message']['chat']['id'];
                $text = $update['message']['text'] ?? '';

                if (strpos($text, '/start') === 0) {
                    $startPayload = trim(str_replace('/start', '', $text));

                    // Обработка реферального клика
                    if (str_starts_with($startPayload, 'ref')) {
                        $refUserId = (int) str_replace('ref', '', $startPayload);

                        if (User::where('id', $refUserId)->exists()) {
                            // Тип статистики по роли

                            ReferralStat::updateOrCreate(
                                ['user_id' => $refUserId, 'type' => 'telegram'],
                                ['clicks_count' => DB::raw('clicks_count + 1')]
                            );

                            // Сохраняем в кеш, чтобы потом учесть регистрацию/пополнение
                            Cache::put("ref_tg_{$chatId}", $refUserId, now()->addDays(10));
                        }
                    }

                    // регистрация через ТГ
                    if (str_starts_with($startPayload, 'register')) {
                        // Отправляем клавиатуру для запроса контакта
                        $keyboard = [
                            'keyboard' => [
                                [
                                    [
                                        'text' => '📱 Поделиться контактом',
                                        'request_contact' => true
                                    ]
                                ]
                            ],
                            'resize_keyboard' => true,
                            'one_time_keyboard' => true
                        ];

                        $this->sendMessage(
                            $chatId,
                            "⚡ *Мгновенная регистрация*\n\nНажмите на кнопку *«Отправить телефон»* внизу экрана и получите логин и пароль.\n\nРегистрируясь, вы соглашаетесь с [политикой конфиденциальности](https://wbdiscount.pro/privacy) и [пользовательским соглашением](https://wbdiscount.pro/terms).",
                            $keyboard,
                            $forSeller
                        );
                    }


                    $this->startCommand($chatId, $startPayload, $forSeller);

                } else {
                    if (isset($update['message']['contact'])) {
                        $phone = $update['message']['contact']['phone_number'];
                        $tgId  = $update['message']['from']['id'];

                        $firstName = $update['message']['from']['first_name'] ?? '';
                        $lastName  = $update['message']['from']['last_name'] ?? '';
                        $username  = $update['message']['from']['username'] ?? null;

                        $fullName = trim($firstName . ' ' . $lastName);

                        // проверим, есть ли уже пользователь с таким telegram_id
                        $user = User::where('telegram_id', $tgId)->first();

                        if (!$user) {
                            // Определяем роль
                            $role = $forSeller
                                ? Role::where('slug', 'seller')->first()
                                : Role::where('slug', 'buyer')->first();

                            // Достаём реферала из кеша (если был переход по ref)
                            $refUserId = Cache::pull("ref_tg_{$chatId}");

                            // Генерируем пароль
                            $passwordPlain = Str::random(8);

                            // Создаём юзера
                            $user = User::create([
                                'name'         => $fullName ?: ($username ? $username : 'tg_' . $tgId),
                                'password'     => bcrypt($passwordPlain),
                                'phone'        => $phone,
                                'role_id'      => $role->id,
                                'is_configured'=> true,
                                'telegram_id'  => $tgId,
                                'referral_id'  => $refUserId,
                            ]);

                            if($forSeller) {
                                $tariff = Tariff::where('name', 'Пробный')->first();
                                DB::table('user_tariff')->insert([
                                    'user_id' => $user->id,
                                    'tariff_id' => $tariff->id,
                                    'end_date' => now()->addDays(3),
                                    'products_count' => 10,
                                    'variant_name' => '3 дня',
                                    'duration_days' => 3,
                                    'price_paid' => 0
                                ]);

                                $template = new Template();
                                $template->createDefault($user->id);
                            }
                            // отправляем пользователю данные для входа
                            $this->sendMessage(
                                $chatId,
                                "🎉 *Поздравляем с регистрацией!*\n\n*Ваши данные:*\nЛогин: `{$phone}`\nПароль: `{$passwordPlain}`\n\n🔗 [Войти в кабинет](" . ($forSeller ? "https://wbdiscount.pro/seller/login" : "https://wbdiscount.pro/buyer/login") . ")\n\nЕсли возникнут проблемы, [напишите нам](https://wbdiscount.pro/dashboard/support).",
                                [],
                                $forSeller
                            );
                            $this->sendMessage(
                                $chatId,
                                "🎁 *Подарок!*\n\nВам доступен *тестовый тариф* на 3 дня и *10 выкупов*. 🚀\n\nРазместите первый товар прямо сейчас, чтобы воспользоваться предложением.",
                                [],
                                $forSeller
                            );
                        } else {
                            $this->sendMessage(
                                $chatId,
                                "⚠️ Вы уже зарегистрированы!",
                                [],
                                $forSeller
                            );
                        }

                        //$this->sendMessage($chatId, '✅Вы успешно поделились контактом!', [], $forSeller);
                    } else {
                        $this->sendMessage($chatId, 'Неизвестная команда', [], $forSeller);
                    }
                }
            }

            return response()->json(['message' => true], 200);
        } catch (\Exception $exception) {
            \Log::channel('tg')->error($exception->getMessage());
            return response()->json(['message' => true], 200);
        }
    }

    // Метод для отправки сообщения
    public function sendMessage($chatId, $text, array $keyboard = [], $forSeller = true): void {
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

//        if (!empty($keyboard)) {
//            $data['reply_markup'] = $keyboard; // Уже передаем готовую структуру клавиатуры
//        }

        // Скрытие web_app кнопок! Что бы верунть все назад - нужно убрать этот блок и раскоменить верхний
        if (!empty($keyboard)) {
            // Если это inline-клава с web_app → временно скрываем
            if (isset($keyboard['inline_keyboard'])) {
                $hasWebApp = false;
                foreach ($keyboard['inline_keyboard'] as $row) {
                    foreach ($row as $btn) {
                        if (isset($btn['web_app'])) {
                            $hasWebApp = true;
                            break 2;
                        }
                    }
                }

                if ($hasWebApp) {
                    // заменяем на удаление клавиатуры
                    $data['reply_markup'] = ['remove_keyboard' => true];
                } else {
                    $data['reply_markup'] = $keyboard;
                }
            } else {
                $data['reply_markup'] = $keyboard;
            }
        }

        $token = $forSeller ? $this->token : $this->clientToken;

        $ch = curl_init("https://api.telegram.org/bot{$token}/sendMessage");
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
    private function startCommand($chatId, $startPayload = null, $forSeller = true): void
    {
        if ($startPayload) {
            // Пытаемся найти пользователя по токену
            if($forSeller){
                $user = User::where('tg_token', $startPayload)->where('role_id', function ($query){
                    return $query->select('id')->from('roles')->where('slug', 'seller');
                })->first();
            }else{
                $user = User::where('tg_token', $startPayload)->where('role_id', function ($query){
                    return $query->select('id')->from('roles')->where('slug', 'buyer');
                })->first();
            }


            if ($user) {
                $user->update(['telegram_id' => $chatId, 'tg_token' => null]);

                $webAppUrl = config('app.web_app_url'). '?chat_id=' . $chatId;
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

                $this->sendMessage($chatId, "✅ Вы успешно привязали аккаунт!", $keyboard, $forSeller);
                return;
            }
        }

        $welcomeMessage = "👋 Добро пожаловать в бот WBDiscount!

Этот бот станет вашим персональным помощником для управления выкупами и отслеживания финансовых операций. Вот что он может делать для вас:

✅ **Уведомления о новых выкупах** — получайте мгновенные оповещения, когда появляется новый выкуп.

✅ **Уведомления о изменении статуса выкупа** — всегда будьте в курсе текущего статуса ваших выкупов: от создания до получения отзыва.

✅ **Финансовые операции** — следите за всеми транзакциями, изменениями баланса и начислениями.

✅ **Сообщения** — получайте уведомления о новых сообщениях и оперативно реагируйте на них.

✅ **И многое другое** — бот поможет вам эффективно управлять вашими выкупами на WBDiscount.";
        $this->sendMessage($chatId, $welcomeMessage,[], $forSeller);

        // Проверяем пользователя в БД
        $user = User::where('telegram_id', $chatId)->first();
        if (!$user) {
            // Создаем Sanctum токен для пользователя
            $webAppUrl = config('app.frontend_url'). '?chat_id=' . $chatId;

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
            $this->sendMessage($chatId, $message, $keyboard, $forSeller);
        }
    }

    public function sendNotification(string $user_id, string $text, array $keyword): void
    {
        try{
            $user = User::where('id',$user_id)->select('telegram_id', 'role_id')->first();
            $isSeller = $user->role_id === Role::where('slug', 'seller')->pluck('id')->first();
            if($user != null){
                $this->sendMessage($user->telegram_id, $text, $keyword, $isSeller);
            }
        }catch (\Exception $exception){
            \Log::error('TelegramService sendNotification error: ' . $exception->getMessage());
            return;
        }
    }

    // Генерация ссылки на телеграм

    public function generateAuthLink(User $user): string
    {
        if($user->role?->slug !== 'seller') {
            $botUsername = config('services.telegram.client_username');
        }else{
            $botUsername = config('services.telegram.username');
        }
        $token = $this->generateUserToken($user);

        return "https://t.me/{$botUsername}?start={$token}";
    }

    public function generateRefLink(User $user): string
    {
        if($user->role?->slug !== 'seller') {
            $botUsername = config('services.telegram.client_username');
        }else{
            $botUsername = config('services.telegram.username');
        }

        return "https://t.me/{$botUsername}?start=ref{$user->id}";
    }

    private function generateUserToken(User $user): string
    {
        // Генерируем уникальный токен для пользователя
        $token = Str::random(32);
        $update = $user->update([
            'tg_token' => $token
        ]);

        return $token;
    }
}
