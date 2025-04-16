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
        $buyback = Buyback::findOrFail($buyback_id);

        $user = auth('sanctum')->user();
        $user->checkBuyback($buyback);

        // Обновляем все сообщения, устанавливая флаг прочитанности
        Message::where('buyback_id', $buyback_id)->update(['is_read' => true]);

        return Message::with('file')->where('buyback_id', $buyback_id)->get();
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
                $fileSrc   = $file->store('files', 'public');
                $fileModel = File::create([
                    'fileable_type' => 'App\Models\Message',
                    'fileable_id'   => $message->id,
                    'src'           => $fileSrc,
                    'category'      => 'image',
                ]);
            }
        }

        $whoSend = $buyback->user_id == auth('sanctum')->id() ? 'buyer' : 'seller';

        $msg = (new SocketService)->send($message, $buyback);
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
            $isSeller = $user->isSeller();
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

        DB::beginTransaction();
        try {
            $files = [];
            foreach ($request->file('files') as $file) {
                $imgMsg = Message::create([
                    'sender_id'   => $user->id,
                    'buyback_id'  => $buyback_id,
                    'type'        => 'image',
                    'system_type' => $request->file_type,
                ]);
                $fileSrc = $file->store('files', 'public');
                $fileModel = File::create([
                    'fileable_type' => 'App\Models\Message',
                    'fileable_id'   => $imgMsg->id,
                    'src'           => $fileSrc,
                    'category'      => 'image',
                ]);
                $files[] = $fileModel;
            }
            (new SocketService)->send($imgMsg, $buyback);

            switch ($request->file_type) {
                case 'send_photo':
                    // ждем 10 дней и отменяем
                    DeliveryJob::dispatch($buyback)->delay(now()->addDays(10));
                    break;
                case 'review':
                    // 72 часа ждем и принимаем!
                    ReviewJob::dispatch($buyback)->delay(now()->addHours(72));
                    break;
            }

            DB::commit();

            return response()->json([
                'files'       => $files,
                'system_type' => $request->file_type,
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status'  => 'false',
                'message' => 'Произошла ошибка, попробуйте еще раз',
            ], 500);
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
                $system_type = 'review';
                $status = 'cashback_received';
                break;
            default:
                abort(403, 'Неверный тип файла');
                break;
        }
        return ['text' => $text,'system_type' => $system_type,'status' => $status];
    }

    public function fileApprove(string $buyback_id,string $file_id)
    {
        $buyback = Buyback::findOrFail($buyback_id);
        auth('sanctum')->user()->checkBuyback($buyback);

        $check = File::where('fileable_type', 'App\Models\Message')
        ->where('fileable_id', $file_id)
        ->where('category', 'image');

        if($check->exists()){
            $file = $check->first();
            $file->update(['status' => true]);

            if($file->fileable?->buyback_id != $buyback_id){
                abort(403, 'Нельзя одобрить файл из другого заказа');
            }

            // Ищем все файлы с этим же типом!
            $allStatuses = File::leftJoin('messages', 'messages.id', '=', 'files.fileable_id')
                    ->where('files.fileable_type', 'App\Models\Message')
                    ->where('messages.buyback_id', $buyback_id)
                    ->pluck('files.status', 'files.id')
                    ->all();

            // Проверяем, все-ли фото одобренны
            $allValuesAreTrueOrOne = count($allStatuses) === count(array_filter($allStatuses, function ($value) {
                    return $value === true || $value === 1;
                }));

            if($allValuesAreTrueOrOne) {
                // Если все фото одобрены, отправляем сообщение в чат
                $checkFile = $this->checkFileType($file->fileable?->system_type);

                $text = $checkFile['text'];
                $system_type = $checkFile['system_type'];
                $status = $checkFile['status'];

                $buyback->update(['status' => $status]);

                $message = Message::create([
                    'buyback_id'  => $file->fileable_id,
                    'sender_id'   => auth('sanctum')->id(),
                    'text'        => $text,
                    'type'        => 'system',
                    'system_type' => $system_type,
                ]);
                (new SocketService)->send($message, $buyback);

                return response()->json([
                    'success' => true,
                    'message' => $message,
                    'buyback' => $buyback
                ], 201);
            }

            return response()->json([
                'success' => true,
                'buyback' => $buyback
            ], 200);
        }else{
            abort(404, 'Файл не найден');
        }
    }

    public function fileReject(FileRejectRequest $request, string $buyback_id,string $file_id)
    {
        $buyback = Buyback::findOrFail($buyback_id);
        auth('sanctum')->user()->checkBuyback($buyback);

        $file = File::findOrFail($file_id);

        if($file->fileable?->buyback_id != $buyback_id){
            abort(403, 'Нельзя отклонить файл из другого заказа');
        }

        $file->update(['status' => false]);
        $checkFile = $this->checkFileType($file->fileable?->system_type);

        $system_type = $checkFile['system_type'];
        $status = $checkFile['status'];

        $buyback->update(['status' => $status]);

        // отправляем сообщение
        $message = Message::create([
            'buyback_id'  => $file->fileable_id,
            'sender_id'   => auth('sanctum')->id(),
            'text'        => $request->comment,
            'type'        => 'text',
            'system_type' => $system_type,
        ]);

        (new SocketService)->send($message, $buyback);

        return response()->json([
            'success' => true,
            'message' => $message,
            'buyback' => $buyback
        ], 200);
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
            if($buyback->has_review_by_seller){
                abort(403, 'Отзыв уже оставлен');
            }
            $buyback->update(['has_review_by_seller' => true]);
            $user_id = $buyback->ad?->user?->id;
            $notification = 'Покупатель оставил отзыв о выкупе #'.$id;
        }else{
            // для покупателя
            if($buyback->has_review_by_buyer){
                abort(403, 'Отзыв уже оставлен');
            }
            $user_id = $buyback->user_id;
            $buyback->update(['has_review_by_buyer' => true]);
            $notification = 'Продавец оставил отзыв о выкупе #'.$id;
        }

        // Уведомление и создание отзыва
        $notification = (new NotificationService)->send($user_id, $id, $notification, true);
        $review = (new ReviewService())->create($ads_id, $rating, $text, $type, $user_id);

        return response()->json([
            'notification' => $notification,
            'review' => $review,
            'buyback' => $buyback
        ], 201);
    }

    public function list(Request $request)
    {
        $userId = auth('sanctum')->id();

        $chats = auth('sanctum')->user()
            ->buybacks()
            ->where(function ($query) use ($request) {
                (new \App\Models\Buyback)->scopeWithFilter($query, $request);
            })
            ->with(['messages', 'ad']) // Добавляем загрузку объявления, если нужно
            ->get()
            ->map(function($buyback) use ($userId) {
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
