<?php

namespace App\Http\Controllers;

use App\Http\Requests\ChatController\PhotoRequest;
use App\Http\Requests\ChatController\SendRequest;
use App\Jobs\DeliveryJob;
use App\Jobs\ReviewJob;
use App\Models\Buyback;
use App\Models\File;
use App\Models\Message;
use App\Services\SocketService;
use Illuminate\Support\Facades\DB;

class ChatController extends Controller
{
    public function messages(string $buyback_id)
    {
        $buyback = Buyback::findOrFail($buyback_id);

        $user = auth('sanctum')->user();
        $user->checkBuyback($buyback);

        return Message::with('file')->where('buyback_id', $buyback_id)->get();
    }

    public function send(string $buyback_id, SendRequest $request)
    {
        $buyback = Buyback::findOrFail($buyback_id);
        auth('sanctum')->user()->checkBuyback($buyback);

        $message = Message::create([
            'buyback_id' => $buyback_id,
            'sender_id'  => auth('sanctum')->user()->id,
            'text'       => $request->text,
        ]);

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

        $msg = (new SocketService)->send($message, $buyback);
        if ($msg) {
            return response()->json([
                'success' => true,
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

                switch ($file->fileable?->system_type){
                    case 'send_photo':
                        $text = 'Продавец подтвердил ваш заказ';
                        $system_type = 'send_photo';
                        break;
                    case 'review':
                        $text = 'Продавец подтвердил ваш отзыв';
                        $system_type = 'review';
                        break;
                    default:
                        $text = 'Продавец подтвердил ваши файлы';
                        $system_type = 'review';
                        break;
                }

                $message = Message::create([
                    'buyback_id'  => $file->fileable_id,
                    'sender_id'   => auth('sanctum')->user()->id,
                    'text'        => $text,
                    'type'        => 'system',
                    'system_type' => $system_type,
                ]);
                (new SocketService)->send($message, $buyback);

                return response()->json([
                    'success' => true,
                    'message' => $message,
                ], 201);
            }

            return response()->json([
                'success' => true,
                'message' => 'Файл одобрен',
            ], 200);
        }else{
            abort(404, 'Файл не найден');
        }
    }
}
