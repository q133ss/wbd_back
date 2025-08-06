<?php

namespace App\Http\Controllers;

use App\Http\Requests\ChatController\CancelRequest;
use App\Http\Requests\ChatController\FileRejectRequest;
use App\Http\Requests\ChatController\PaymentScreenRequest;
use App\Http\Requests\ChatController\PhotoRequest;
use App\Http\Requests\ChatController\ReviewRequest;
use App\Http\Requests\ChatController\SendRequest;
use App\Jobs\CheckOrderJob;
use App\Jobs\DeliveryJob;
use App\Jobs\ReviewJob;
use App\Models\Admin\Settings;
use App\Models\Buyback;
use App\Models\File;
use App\Models\Message;
use App\Services\BalanceService;
use App\Services\NotificationService;
use App\Services\ReviewService;
use App\Services\SocketService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ChatController extends Controller
{
    public function messages(string $buyback_id)
    {
        $buyback = Buyback::with('ad')->findOrFail($buyback_id);

        $user = auth('sanctum')->user();
        $user->checkBuyback($buyback);

        // Определяем ID второй стороны в сделке
        $adUserId = $buyback->ad?->user_id;
        $counterpartyId = ($user->id == $buyback->user_id) ? $adUserId : $buyback->user_id;

        // Помечаем только сообщения от второй стороны как прочитанные
        Message::where('buyback_id', $buyback_id)
            ->where('sender_id', $counterpartyId) // Сообщения от противоположной стороны
            ->where('is_read', false)             // Только непрочитанные
            ->update(['is_read' => true]);

        return Message::with('files')
            ->where('buyback_id', $buyback_id)
            ->orderBy('created_at', 'asc')      // Сортировка по дате (новые внизу)
            ->paginate(30);
    }

    public function send(string $buyback_id, SendRequest $request)
    {
        $buyback = Buyback::findOrFail($buyback_id);
        auth('sanctum')->user()->checkBuyback($buyback);

        $message = Message::create([
            'buyback_id' => $buyback_id,
            'sender_id'  => auth('sanctum')->id(),
            'text'       => $request->text,
        ]);

        # TODO добавить возможность отправлять фото без текста!
        if ($request->has('files')) {
            foreach ($request->file('files') as $file) {
                $fileSrc   = '/storage/'.$file->store('files', 'public');
                $fileModel = File::create([
                    'fileable_type' => 'App\Models\Message',
                    'fileable_id'   => $message->id(),
                    'src'           => $fileSrc,
                    'category'      => 'image',
                ]);
            }
        }

        $whoSend = $buyback->user_id == auth('sanctum')->id() ? 'buyer' : 'seller';

        $msg = (new SocketService)->send($message, $buyback, false);
        if ($msg) {
            return response()->json([
                'success' => true,
                'whoSend' => $whoSend,
                'message' => $message->load('files'),
            ]);
        }

        return response()->json(['success' => false]);
    }

    public function cancel(CancelRequest $request, string $id)
    {
        $user = auth('sanctum')->user();

        $buyback = Buyback::findOrFail($id);
        $user->checkBuyback($buyback);

        if ($buyback->status == 'cancelled') {
            abort(403, 'Заказ уже отменен');
        }
        DB::beginTransaction();
        try {
            $buyback->update(['status' => 'cancelled']);
            $isSeller = $buyback->user_id != auth('sanctum')->id();
            $text     = '';
            if ($isSeller) {
                $text = 'Выкуп отменен по инициативе продавца. Причина: '.$request->comment;
            } else {
                $text = 'Выкуп отменен по инициативе покупателя';
            }
            $message = Message::create([
                'buyback_id'  => $id,
                'sender_id'   => $user->id,
                'text'        => $text,
                'type'        => 'system',
                'system_type' => 'cancel',
                'created_at' => now(),
            ]);
            DB::commit();

            return response()->json([
                'message' => $message,
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status'  => 'false',
                'message' => 'Произошла ошибка, попробуйте еще раз',
            ], 500);
        }
    }

    // TODO
    //Покупатель:
    //1) Заказ сделан, покупатель отправил фото
    //2) INFO Продавец получил подтверждение вашего заказа.
    //Он проверит фотографию - если заказ сделан корректно, то все в порядке и сделка продолжится автоматически.
    //Если вы загрузили некорректную фотографию или заказали не тот товар, то Продавец вправе отменить вашу заявку. Вы получите соответствующее уведомление об этом
    //3) Спасибо из админки
    //4) Критерии отзыва
    //
    //Продавец:
    //1) Спасибо из админки
    //2) Критерии отзыва
    //3) INFO У покупателя есть 14 дней с момента заказа, чтобы получить товар и оставить отзыв. Вы получите подтверждение оставленного отзыва в этом чате от покупателя как только он получит товар.
    public function photo(PhotoRequest $request, string $buyback_id)
    {
        $buyback = Buyback::findOrFail($buyback_id);

        $user = auth('sanctum')->user();
        $user->checkBuyback($buyback);

        if($buyback->user_id != $user->id){
            abort(403, 'У вас нет прав на это действие');
        }

        DB::beginTransaction();
        try {
            $data = [];
            $cashback = round($buyback->product_price - $buyback->price_with_cashback);

            //Продавец получил подтверждение вашего заказа.
            //Он проверит фотографию - если заказ сделан корректно, то все в порядке и сделка продолжится автоматически.  Если вы загрузили некорректную фотографию или заказали не тот товар, то Продавец вправе отменить вашу заявку. Вы получите соответствующее уведомление об этом 

            $reviewCriteriaText = null;
            switch ($request->file_type) {
                case 'send_photo':
                    if($buyback->is_order_photo_sent){
                        abort(403, 'Вы уже отправили фото');
                    }

                    if($buyback->status != 'pending'){
                        abort(403, 'На данном этапе отправить фото невозможно');
                    }

                    $successText = 'Заказ сделан, покупатель отправил фото';
                    $buyerInfoText = 'Продавец получил подтверждение вашего заказа.<br>
Он проверит фотографию - если заказ сделан корректно, то все в порядке и сделка продолжится автоматически. Если вы загрузили некорректную фотографию или заказали не тот товар, то Продавец вправе отменить вашу заявку. Вы получите соответствующее уведомление об этом';


                    $sellerInfoText = 'У покупателя есть 14 дней с момента заказа, чтобы получить товар и оставить отзыв. Вы получите подтверждение оставленного отзыва в этом чате от покупателя как только он получит товар. ';

                    // ждем 10 дней и отменяем
                    $data = ['is_order_photo_sent' => true, 'status' => 'awaiting_receipt'];

                    // Переменные!
                    $thxText = str_replace(['{cashback}'], [$cashback], \App\Models\Admin\Settings::where('key','review_cashback_instructions')->pluck('value')->first());

                    $reviewCriteriaText = $ad->review_criteria ?? null;

                    DeliveryJob::dispatch($buyback)->delay(now()->addDays(10));
                    break;
                case 'review':
                    // 72 часа ждем и принимаем!
                    if($buyback->is_review_photo_sent){
                        abort(403, 'Вы уже отправили фото');
                    }

                    if($buyback->status != 'awaiting_receipt'){
                        abort(403, 'На данном этапе отправить фото невозможно');
                    }

//                    $successText = 'Покупатель оставил отзыв и порезал штрихкод';
//                    $sellerSuccessText = 'Покупатель оставил отзыв и порезал штрихкод. Внимательно посмотрите фотографии и переведите кэшбек в размере 300 руб на счет пкупателя по реквизитам в чате.';

                    $buyerInfoText = 'У продавца есть 24 часа чтобы проверить ваши материалы и подтвердить получение кэшбека. Если по истечению времени перевод не будет получен, свяжитесь с продавцом в этом чате, а так же с поддержкой через три точки в верхнем меню чата';

                    CheckOrderJob::dispatch($buyback)->delay(now()->addHours(24));

                    // Тут формируем реквизиты!
                    $bankMap = [
                        'sber' => 'Сбербанк',
                        'tbank' => 'Тинькофф',
                        'ozon' => 'Ozon',
                        'alfa' => 'Альфа-Банк',
                        'vtb' => 'ВТБ',
                        'raiffeisen' => 'Райффайзен',
                        'gazprombank' => 'Газпромбанк',
                        'sbp' => 'СБП',
                    ];

                    $lines = [];

                    $methods = $buyback->user?->paymentMethod;
                    foreach ($bankMap as $key => $name) {
                        $card = $methods->$key ?? null;

                        if (!$card) {
                            continue;
                        }

                        if ($key === 'sbp') {
                            $comment = $methods->sbp_comment ?? '';
                            $lines[] = "По СБП: $card" . ($comment ? " ({$comment})" : '');
                        } else {
                            // Форматируем номер карты по 4 цифры
                            $formattedCard = trim(chunk_split($card, 4, ' '));
                            $lines[] = "Карта {$name}: {$formattedCard}";
                        }
                    }

                    $paymentMethodText = implode("<br>", $lines);

                    $cashbackAmount = number_format($cashback, 0, '.', ' ') . ' ₽'; // Форматируем сумму с пробелом между тысячами
                    $paymentText = "Прошу проверить материалы и перевести кэшбек <strong>{$cashbackAmount}</strong> по реквизитам: <br><br>{$paymentMethodText}";

                    $data = ['is_review_photo_sent' => true, 'status' => 'on_confirmation'];

                    // Подставляем переменные
                    $thxText = str_replace(['{cashback}'], [$cashback], Settings::where('key','cashback_review_message')->pluck('value')->first());

                    ReviewJob::dispatch($buyback)->delay(now()->addHours(24));
                    break;
            }

            $files = [];
            // Сначала фото!
            $imgMsg = Message::create([
                'sender_id'   => $user->id,
                'buyback_id'  => $buyback_id,
                'type'        => 'image',
                'system_type' => $request->file_type,
                'created_at' => now(),
            ]);
            foreach ($request->file('files') as $file) {
                $fileSrc = $file->store('files', 'public');
                $fileModel = File::create([
                    'fileable_type' => 'App\Models\Message',
                    'fileable_id'   => $imgMsg->id,
                    'src'           => $fileSrc,
                    'category'      => 'image',
                ]);
                $files[] = $fileModel;
            }
            (new SocketService)->send($imgMsg, $buyback, false);


            // Текст
            if(isset($paymentText)){
                $paymentMessage = Message::create([
                    'buyback_id'  => $buyback->id,
                    'sender_id'   => $buyback->user_id,
                    'text'        => $paymentText,
                    'type'        => 'text'
                ]);
            }

            $buyback->update($data);

            // 1) Заказ сделан, покупатель отправил фото
//            $successMsg = Message::create([
//                'sender_id'   => $buyback->ad?->user?->id,
//                'buyback_id'  => $buyback_id,
//                'text'        => $successText,
//                'type'        => 'system',
//                'system_type' => 'success',
//                'created_at' => now(),
//            ]);
//            (new SocketService)->send($successMsg, $buyback, false);

//            if(isset($sellerSuccessText)) {
//                $sellerSuccessMsg = Message::create([
//                    'sender_id' => $buyback->ad?->user?->id,
//                    'buyback_id' => $buyback_id,
//                    'text' => $sellerSuccessText,
//                    'type' => 'system',
//                    'system_type' => 'success',
//                    'hide_for' => 'user',
//                    'created_at' => now(),
//                ]);
//                (new SocketService)->send($sellerSuccessMsg, $buyback, false);
//            }

            //2) info

            $buyerInfoMsg = Message::create([
                'sender_id'   => $buyback->ad?->user?->id,
                'buyback_id'  => $buyback_id,
                'text'        => $buyerInfoText,
                'type'        => 'system',
                'system_type' => 'info',
                'hide_for'    => 'seller',
                'created_at' => now(),
            ]);

            if(isset($paymentMessage)){
                (new SocketService)->send($paymentMessage, $buyback, false);
            }

            if(isset($sellerInfoText)) {
                $sellerInfoMsg = Message::create([
                    'sender_id' => $buyback->ad?->user?->id,
                    'buyback_id' => $buyback_id,
                    'text' => $sellerInfoText,
                    'type' => 'system',
                    'system_type' => 'info',
                    'hide_for' => 'user',
                    'created_at' => now(),
                ]);
                (new SocketService)->send($sellerInfoMsg, $buyback, false);
            }

            (new SocketService)->send($buyerInfoMsg, $buyback, false);

            // 3) Спасибо за заказ!
            $thxMsg = Message::create([
                'sender_id'   => $buyback->ad?->user?->id,
                'buyback_id'  => $buyback_id,
                'text'        => $thxText,
                'type'        => 'text',
                'created_at' => now()
            ]);
            (new SocketService)->send($thxMsg, $buyback, false);

            // 4) Критерии отзыва!
            if($reviewCriteriaText != null) {
                $reviewCriteria = Message::create([
                    'sender_id' => $buyback->ad?->user?->id,
                    'buyback_id' => $buyback_id,
                    'text' => $reviewCriteriaText,
                    'type' => 'text',
                    'created_at' => now()
                ]);
                (new SocketService)->send($reviewCriteria, $buyback, false);
            }


            DB::commit();

            return response()->json([
                'files'       => $files,
                'system_type' => $request->file_type,
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error($e->getMessage());
            return response()->json([
                'status'  => 'false',
                'message' => $e->getMessage() ? $e->getMessage() : 'Произошла ошибка, попробуйте еще раз',
            ], $e->getStatusCode() ? $e->getStatusCode() : 500);
        }
    }

    private function checkFileType($system_type): array
    {
        switch ($system_type){
            case 'send_photo':
                $text = 'Продавец подтвердил ваш заказ';
                $system_type = 'send_photo';
                $status = 'awaiting_receipt';
                break;
            case 'review':
                $text = 'Продавец подтвердил ваш отзыв';
                $system_type = null;
                $status = 'cashback_received';
                break;
            default:
                abort(403, 'Неверный тип файла');
                break;
        }
        return ['text' => $text,'system_type' => $system_type,'status' => $status];
    }

    private function isSeller(Buyback $buyback): bool
    {
        if($buyback->user_id == auth('sanctum')->id()){
            abort(403, 'У вас нет прав на это действие');
        }
        return true;
    }

    public function fileApprove(string $buyback_id,string $file_id)
    {
        $buyback = Buyback::findOrFail($buyback_id);
        auth('sanctum')->user()->checkBuyback($buyback);
        $this->isSeller($buyback);

        $file = File::findOrFail($file_id);

        if($file->status !== null){
            abort(403, 'Файл уже обработан');
        }

        if($file->fileable?->buyback_id != $buyback_id){
            abort(403, 'Нельзя одобрить файл из другого заказа');
        }

        DB::beginTransaction();
        try {
            $file->update(['status' => true]);

            $checkFile = $this->checkFileType($file->fileable?->system_type);

            $text = $checkFile['text'];
            $system_type = $checkFile['system_type'];
            $status = $checkFile['status'];

            if($file->fileable?->system_type == 'review'){
                // Ищем 2 файла!
                $fileCount = File::leftJoin('messages', 'messages.id', '=', 'files.fileable_id')
                    ->where('files.fileable_id', $file->fileable_id)
                    ->where('files.fileable_type', 'App\Models\Message')
                    ->where('messages.system_type', $file->fileable?->system_type)
                    ->where('messages.buyback_id', $buyback_id)
                    ->where('messages.sender_id', $buyback->user_id)
                    ->count();

                // Если все фото одобрены, отправляем сообщение в чат
                $checkFile = $this->checkFileType($file->fileable?->system_type);

                $text = $checkFile['text'];
                $system_type = $checkFile['system_type'];
                $status = $checkFile['status'];

                // Проверяем, все-ли фото одобренны, у нас их 2
                if($fileCount == 2){
                    $buyback->update(['status' => $status]);

                    $message = Message::create([
                        'buyback_id'  => $file->fileable?->buyback_id,
                        'sender_id'   => auth('sanctum')->id(),
                        'text'        => $text,
                        'type'        => 'system',
                        'system_type' => $system_type,
                    ]);

                    $cashback = $buyback->product_price - $buyback->price_with_cashback;

                    $bankMap = [
                        'sber' => 'Сбербанк',
                        'tbank' => 'Тинькофф',
                        'ozon' => 'Ozon',
                        'alfa' => 'Альфа-Банк',
                        'vtb' => 'ВТБ',
                        'raiffeisen' => 'Райффайзен',
                        'gazprombank' => 'Газпромбанк',
                        'sbp' => 'СБП',
                    ];

                    $lines = [];

                    $methods = $buyback->user?->paymentMethod;
                    foreach ($bankMap as $key => $name) {
                        $card = $methods->$key ?? null;

                        if (!$card) {
                            continue;
                        }

                        if ($key === 'sbp') {
                            $comment = $methods->sbp_comment ?? '';
                            $lines[] = "По СБП: $card" . ($comment ? " ({$comment})" : '');
                        } else {
                            // Форматируем номер карты по 4 цифры
                            $formattedCard = trim(chunk_split($card, 4, ' '));
                            $lines[] = "Карта {$name}: {$formattedCard}";
                        }
                    }

                    $paymentMethodText = implode("<br>", $lines);

                    $cashbackAmount = number_format($cashback, 0, '.', ' ') . ' ₽'; // Форматируем сумму с пробелом между тысячами
                    $paymentText = "Переведите кэшбек в размере {$cashbackAmount}<br>{$paymentMethodText}";

                    $paymentMessage = Message::create([
                        'buyback_id'  => $file->fileable?->buyback_id,
                        'sender_id'   => $buyback->user_id,
                        'text'        => $paymentText,
                        'type'        => 'text'
                    ]);

                    (new SocketService)->send($message, $buyback);
                    (new SocketService)->send($paymentMessage, $buyback);

                    $webAppUrl = config('app.web_app_url').'/dashboard/orders?chatId='.$buyback_id;
                    (new NotificationService())->send($buyback->user_id, $buyback->id, 'Продавец подтвердил ваш отзыв', true, [
                        'inline_keyboard' => [
                            [
                                [
                                    'text' => '🚀 Открыть приложение',
                                    'web_app' => ['url' => $webAppUrl]
                                ]
                            ]
                        ],
                    ]);

                    $ad = $buyback->ad;
                    // Удаляем выкуп со слова, которое использовалось
                    if(!empty($ad->keywords)){
                        $usedKeyword = $buyback->keyword ?? null;

                        $ad->keywords = collect($ad->keywords)
                            // 1. Уменьшаем счётчик, если слово совпадает
                            ->map(function ($item) use ($usedKeyword) {
                                if ($item['word'] === $usedKeyword && $item['redemption_count'] > 0) {
                                    $item['redemption_count']--;
                                }
                                return $item;
                            })
                            // 2. Удаляем слова, у которых выкупов не осталось
                            ->filter(function ($item) {
                                return $item['redemption_count'] > 0;
                            })
                            ->values() // сбрасываем ключи, чтобы остался нормальный JSON-массив
                            ->toArray();

                        $ad->save();
                    }

                    // Это не надо уже!
                    //(new BalanceService())->buybackPayment($buyback);

                    DB::commit();

                    return response()->json([
                        'success' => true,
                        'message' => $message,
                        'buyback' => $buyback
                    ], 201);
                }
            }

            $message = Message::create([
                'buyback_id'  => $file->fileable?->buyback_id,
                'sender_id'   => auth('sanctum')->id(),
                'text'        => $text,
                'type'        => 'system',
                'system_type' => $system_type,
            ]);
            (new SocketService)->send($message, $buyback);

            $webAppUrl = config('app.web_app_url').'/dashboard/orders?chatId='.$buyback_id;
            (new NotificationService())->send($buyback->user_id, $buyback->id, 'Продавец подтвердил скриншот вашего заказа', true, [
                'inline_keyboard' => [
                    [
                        [
                            'text' => '🚀 Открыть приложение',
                            'web_app' => ['url' => $webAppUrl]
                        ]
                    ]
                ],
            ]);

            $buyback->update(['status' => $status]);
            DB::commit();

            return response()->json([
                'success' => true,
                'buyback' => $buyback
            ], 200);
        }catch (\Exception $e){
            DB::rollBack();
            \Log::error($e);
            return response()->json([
                'status'  => 'false',
                'message' => 'Произошла ошибка, попробуйте еще раз',
            ], 500);
        }
    }

    public function fileReject(FileRejectRequest $request, string $buyback_id,string $file_id)
    {
        $buyback = Buyback::findOrFail($buyback_id);
        auth('sanctum')->user()->checkBuyback($buyback);
        $this->isSeller($buyback);

        $file = File::findOrFail($file_id);

        if($file->status !== null){
            abort(403, 'Файл уже обработан');
        }

        DB::beginTransaction();
        try {
            if($file->fileable?->buyback_id != $buyback_id){
                abort(403, 'Нельзя отклонить файл из другого заказа');
            }

            // TODO соседний тоже отклонить, если это отзыв!
            $file->update(['status' => false, 'status_comment' => $request->comment]);
            File::where('fileable_id', $file->fileable_id)
                ->where('fileable_type', $file->fileable_type)
                ->update(['status' => false, 'status_comment' => $request->comment]);

            $data = [];
            if($file->fileable?->system_type == 'send_photo'){
                $buyback->update(['is_order_photo_sent' => false]);
            }elseif($file->fileable?->system_type == 'review'){
                $buyback->update(['is_review_photo_sent' => false]);
            }

            // отправляем сообщение
            $message = Message::create([
                'buyback_id'  => $file->fileable?->buyback_id,
                'sender_id'   => auth('sanctum')->id(),
                'text'        => 'Продавец отклонил ваш скриншот: '.$request->comment,
                'type'        => 'text',
                'system_type' => 'send_photo',
            ]);

            // Прикрепляем файл к сообщению
            $attachFile = $file->replicate();
            $attachFile->status = null;
            $attachFile->status_comment = null;
            $attachFile->fileable_id = $message->id;
            $attachFile->save();

            (new SocketService)->send($message, $buyback);

            $webAppUrl = config('app.web_app_url').'/dashboard/orders?chatId='.$buyback_id;
            (new NotificationService())->send($buyback->user_id, $buyback->id, 'Продавец отклонил скриншот', true, [
                'inline_keyboard' => [
                    [
                        [
                            'text' => '🚀 Открыть приложение',
                            'web_app' => ['url' => $webAppUrl]
                        ]
                    ]
                ],
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => $message,
                'buyback' => $buyback
            ], 200);
        }catch (\Exception $e){
            DB::rollBack();
            return response()->json([
                'status'  => 'false',
                'message' => 'Произошла ошибка, попробуйте еще раз',
            ], 500);
        }
    }

    public function complete(string $id)
    {
        $buyback = Buyback::findOrFail($id);
        auth('sanctum')->user()->checkBuyback($buyback);
        DB::beginTransaction();
        try {
            $buyback->update(['status' => 'completed']);

            $message = Message::create([
                'buyback_id'  => $id,
                'sender_id'   => auth('sanctum')->id(),
                'text'        => 'Продавец подтвердил выполнение заказа',
                'type'        => 'text',
                'system_type' => 'completed'
            ]);

            (new SocketService)->send($message, $buyback);
            DB::commit();
            return response()->json([
                'buyback' => $buyback,
                'message' => $message
            ], 200);
        }catch (\Exception $e){
            DB::rollBack();

            return response()->json([
                'status'  => 'false',
                'message' => 'Произошла ошибка, попробуйте еще раз',
            ], 500);
        }
    }

    public function review(ReviewRequest $request, string $id)
    {
        $buyback = Buyback::findOrFail($id);
        $user = auth('sanctum')->user();
        $user->checkBuyback($buyback);

        $rating = $request->rating;
        $text = $request->text;
        $type = 'App\Models\User';
        $ads_id = $buyback->ads_id;

        if($buyback->user_id == $user->id){
            // оставляем продавцу
            if($buyback->has_review_by_buyer){
                abort(403, 'Отзыв уже оставлен');
            }
            $user_id = $buyback->user_id;
            $buyback->update(['has_review_by_buyer' => true]);
            $notification = 'Покупатель оставил отзыв о выкупе #'.$id;

            $webAppUrl = config('app.web_app_url').'/dashboard/orders?chatId='.$buyback->id;
            $notificationModel = (new NotificationService)->send($buyback->ad?->user?->id, $id, $notification, true, [
                'inline_keyboard' => [
                    [
                        [
                            'text' => '🚀 Открыть приложение',
                            'web_app' => ['url' => $webAppUrl]
                        ]
                    ]
                ],
            ]);

            $message = Message::create([
                'buyback_id'  => $id,
                'sender_id'   => auth('sanctum')->id(),
                'text'        => $text,
                'type'        => 'comment',
                'color'       => $rating // TODO ИСРАВИТЬ, НУЖНО ОТДЕЛЬНОЕ ПОЛЕ СДЕЛАТЬ!
            ]);
        }else{
            // для покупателя
            if($buyback->has_review_by_seller){
                abort(403, 'Отзыв уже оставлен');
            }
            $buyback->update(['has_review_by_seller' => true]);
            $user_id = $buyback->ad?->user?->id;
            $notification = 'Продавец оставил отзыв о выкупе #'.$id;

            $webAppUrl = config('app.web_app_url').'/dashboard/orders?chatId='.$buyback->id;
            $notificationModel = (new NotificationService)->send($buyback->user_id, $id, $notification, true, [
                'inline_keyboard' => [
                    [
                        [
                            'text' => '🚀 Открыть приложение',
                            'web_app' => ['url' => $webAppUrl]
                        ]
                    ]
                ],
            ]);

            $message = Message::create([
                'buyback_id'  => $id,
                'sender_id'   => auth('sanctum')->id(),
                'text'        => $text,
                'type'        => 'comment',
                'color'       => $rating // TODO ИСРАВИТЬ, НУЖНО ОТДЕЛЬНОЕ ПОЛЕ СДЕЛАТЬ!
            ]);
        }

        // Сообщение и создание отзыва
        $review = (new ReviewService())->create($ads_id, $rating, $text, $type, $user_id);
        (new SocketService)->send($message, $buyback, false);

        return response()->json([
            'notification' => $notificationModel,
            'review' => $review,
            'buyback' => $buyback
        ], 201);
    }

    public function list(Request $request)
    {
        $chats = auth('sanctum')->user()
            ->buybacks()
            ->withFilter($request)
            ->where(function ($query) use ($request) {
                (new \App\Models\Buyback)->scopeWithFilter($query, $request);
            })
            ->with(['ad'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function($buyback) {
                $currentUserId = auth('sanctum')->id();
                $userId = $buyback->ad?->user_id;
                $isBuyer = $buyback->user_id == $userId;

                // Добавляем whoSend для каждого сообщения
                $unreadCount = 0;
                $buyback->messages->each(function($message) use ($isBuyer, $buyback, &$unreadCount, $currentUserId) {
                    $message->whoSend = ($message->sender_id == $buyback->user_id) == $isBuyer
                        ? 'buyer'
                        : 'seller';

                    if (!$message->is_read && $message->sender_id !== $currentUserId) {
                        $unreadCount++;
                    }
                });

                $buyback->unread_messages_count = $unreadCount;
                return $buyback;
            });

        return response()->json($chats);
    }

    public function paymentScreen(PaymentScreenRequest $request, string $buyback_id)
    {
        // TODO Transaction
        $buyback = Buyback::findOrFail($buyback_id);
        $user = auth('sanctum')->user();
        $user->checkBuyback($buyback);

        if($buyback->status != 'on_confirmation'){
            abort(403, 'У вас нет прав на это действие');
        }

        $message = Message::create([
            'buyback_id'  => $buyback_id,
            'sender_id'   => auth('sanctum')->id(),
            'text'        => 'Кэшбек переведен, прошу подтвердить поступление!',
            'type'        => 'image',
        ]);

        $fileSrc = $request->file('file')->store('files', 'public');
        $fileModel = File::create([
            'fileable_type' => 'App\Models\Message',
            'fileable_id'   => $message->id,
            'src'           => $fileSrc,
            'category'      => 'image'
        ]);

        $userMsg = Message::create([
            'buyback_id'  => $buyback_id,
            'sender_id'   => auth('sanctum')->id(),
            'text'        => 'Подтвердите получение кэшбека. Если вы не получили кэшбек, свяжитесь с продавцом в этом чате. Если возник спор или продавец не отвечает, напишите в поддержку и мы решим вопрос',
            'type'        => 'system',
            'system_type' => 'info',
            'hide_for'    => 'seller'
        ]);

        $sellerMsg = Message::create([
            'buyback_id' => $buyback_id,
            'sender_id' => auth('sanctum')->id(),
            'text' => 'Чек был отправлен покупателю, дождитесь подтверждения получения кэшбека в течение 24 часов или сделка будет принята автоматически',
            'type'        => 'system',
            'system_type' => 'success',
            'hide_for'    => 'user'
        ]);

        (new SocketService)->send($message, $buyback, false);
        (new SocketService)->send($sellerMsg, $buyback, false);

        $update = $buyback->update([
            'is_payment_photo_sent' => true,
            'status' => 'awaiting_payment_confirmation'
        ]);

        return response()->json([
            'message' => 'true'
        ]);
    }

    // Подтверидть оплату (юзером)
    public function acceptPayment(string $buyback_id)
    {
        $buyback = Buyback::findOrFail($buyback_id);
        $user = auth('sanctum')->user();
        $user->checkBuyback($buyback);

        $isSeller = $buyback->user_id != auth('sanctum')->id();
        if($isSeller){
            abort(403, 'Вы не можете выполнить это действие');
        }

        $updated = $buyback->update([
            'status' => 'cashback_received'
        ]);

        $messageSeller = Message::create([
            'buyback_id' => $buyback_id,
            'sender_id' => auth('sanctum')->id(),
            'text' => 'Покупатель подтвердил получение кэшбека. Оставьте отзыв о покупателе, чтобы увидеть отзыв покупателя о вас.',
            'type'        => 'system',
            'system_type' => 'success',
            'hide_for'    => 'user'
        ]);

        $messageUser = Message::create([
            'buyback_id' => $buyback_id,
            'sender_id' => auth('sanctum')->id(),
            'text' => 'Вы подтвердили получение кэшбека. Оставьте отзыв о продавце, чтобы увидеть отзыв продавца о вас.',
            'type'        => 'system',
            'system_type' => 'success',
            'hide_for'    => 'seller'
        ]);

        if($buyback->has_review_by_buyer == false){
            $msgReviewSeller = Message::create([
                'buyback_id' => $buyback_id,
                'sender_id' => auth('sanctum')->id(),
                'text' => 'Покупатель еще не оставил отзыв о вас. Мы сообщим вам сразу же как покупатель напишет отзыв.',
                'type'        => 'system',
                'system_type' => 'success',
                'hide_for'    => 'user'
            ]);
            (new SocketService)->send($msgReviewSeller, $buyback, false);
        }

        if($buyback->has_review_by_seller == false){
            $msgReviewUser = Message::create([
                'buyback_id' => $buyback_id,
                'sender_id' => auth('sanctum')->id(),
                'text' => 'Продавец еще не оставил отзыв о вас. Мы сообщим вам сразу же как продавец напишет отзыв.',
                'type'        => 'system',
                'system_type' => 'success',
                'hide_for'    => 'seller'
            ]);
            (new SocketService)->send($msgReviewUser, $buyback, false);
        }

        (new SocketService)->send($messageSeller, $buyback, false);
        (new SocketService)->send($messageUser, $buyback, false);
    }

    public function rejectPayment()
    {
        //
    }

    public function lastSeen(string $id)
    {
        $buyback = Buyback::findOrFail($id);

        return response()->json([
            'buyer' => $buyback->user?->last_seen_at,
            'seller' => $buyback->ad?->user?->last_seen_at
        ]);

    }
}
