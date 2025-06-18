<?php

namespace App\Http\Controllers;

use App\Http\Requests\ChatController\FileRejectRequest;
use App\Http\Requests\ChatController\PhotoRequest;
use App\Http\Requests\ChatController\ReviewRequest;
use App\Http\Requests\ChatController\SendRequest;
use App\Jobs\DeliveryJob;
use App\Jobs\ReviewJob;
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
                    'fileable_id'   => $message->id,
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

    public function cancel(string $id)
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
                $text = 'Выкуп отменен по инициативе продавца';
            } else {
                $text = 'Выкуп отменен по инициативе покупателя';
            }
            $message = Message::create([
                'buyback_id'  => $id,
                'sender_id'   => $user->id,
                'text'        => $text,
                'type'        => 'system',
                'system_type' => 'cancel',
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

            switch ($request->file_type) {
                case 'send_photo':
                    if($buyback->is_order_photo_sent){
                        abort(403, 'Вы уже отправили фото');
                    }

                    if($buyback->status != 'pending'){
                        abort(403, 'На данном этапе отправить фото невозможно');
                    }

                    // ждем 10 дней и отменяем
                    $data = ['is_order_photo_sent' => true];
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

                    $data = ['is_review_photo_sent' => true];
                    ReviewJob::dispatch($buyback)->delay(now()->addHours(72));
                    break;
            }

            $files = [];
            $imgMsg = Message::create([
                'sender_id'   => $user->id,
                'buyback_id'  => $buyback_id,
                'type'        => 'image',
                'system_type' => $request->file_type,
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

            $buyback->update($data);

            DB::commit();

            return response()->json([
                'files'       => $files,
                'system_type' => $request->file_type,
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
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

                    (new SocketService)->send($message, $buyback);
                    (new NotificationService())->send($buyback->user_id, $buyback->id, 'Продавец подтвердил ваш отзыв', true);

                    (new BalanceService())->buybackPayment($buyback);

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
            (new NotificationService())->send($buyback->user_id, $buyback->id, 'Продавец подтвердил скриншот вашего заказа', true);

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

            (new NotificationService())->send($buyback->user_id, $buyback->id, 'Продавец отклонил скриншот', true);

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

            (new BalanceService())->buybackPayment($buyback);

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

            $notificationModel = (new NotificationService)->send($buyback->ad?->user?->id, $id, $notification, true);

            $message = Message::create([
                'buyback_id'  => $id,
                'sender_id'   => auth('sanctum')->id(),
                'text'        => 'Покупатель оставил отзыв о выкупе: '.$text,
                'type'        => 'system',
                'system_type' => 'review',
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
            $notificationModel = (new NotificationService)->send($buyback->user_id, $id, $notification, true);

            $message = Message::create([
                'buyback_id'  => $id,
                'sender_id'   => auth('sanctum')->id(),
                'text'        => 'Продавец оставил отзыв о выкупе: '.$text,
                'type'        => 'system',
                'system_type' => 'review',
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
                $userId = $buyback->ad?->user_id;
                $isBuyer = $buyback->user_id == $userId;

                // Добавляем whoSend для каждого сообщения
                $buyback->messages->each(function($message) use ($isBuyer, $buyback) {
                    $message->whoSend = ($message->sender_id == $buyback->user_id) == $isBuyer
                        ? 'buyer'
                        : 'seller';
                });

                return $buyback;
            });

        return response()->json($chats);
    }
}
