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
use Illuminate\Http\Request;
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
            'sender_id' => auth('sanctum')->user()->id,
            'text' => $request->text
        ]);

        if($request->has('files')){
            foreach ($request->file('files') as $file){
                $fileSrc = $file->store('files', 'public');
                $fileModel = File::create([
                    'fileable_type' => 'App\Models\Message',
                    'fileable_id' => $message->id,
                    'src' => $fileSrc,
                    'category' => 'image'
                ]);
            }
        }

        $msg = (new SocketService())->send($message, $buyback);
        if($msg){
            return response()->json([
                'success' => true,
                'message' => $message->load('files')
            ]);
        }
        return response()->json(['success' => false]);
    }

    public function cancel(string $id)
    {
        $user = auth('sanctum')->user();

        $buyback = Buyback::findOrFail($id);
        $user->checkBuyback($buyback);

        if($buyback->status == 'cancelled'){
            abort(403, 'Заказ уже отменен');
        }
        DB::beginTransaction();
        try {
            $buyback->update(['status' => 'cancelled']);
            $isSeller = $user->isSeller();
            $text = '';
            if ($isSeller) {
                $text = 'Выкуп отменен по инициативе продавца';
            } else {
                $text = 'Выкуп отменен по инициативе покупателя';
            }
            $message = Message::create([
                'buyback_id' => $id,
                'sender_id' => $user->id,
                'text' => $text,
                'type' => 'system',
                'system_type' => 'cancel'
            ]);
            DB::commit();
            return response()->json([
                'message' => $message
            ], 201);
        }catch (\Exception $e){
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
        try{
            $imgMsg = Message::create([
                'sender_id' => $user->id,
                'buyback_id' => $buyback_id,
                'type' => 'image',
                'system_type' => $request->file_type
            ]);
            $files = [];
            foreach ($request->file('files') as $file){
                $fileSrc = $file->store('files', 'public');
                # todo там подтверждать надо КАЖДУЮ ФОТКУ, так, что делаем 2 разных сообщения!!!
                $fileModel = File::create([
                    'fileable_type' => 'App\Models\Message',
                    'fileable_id' => $imgMsg->id,
                    'src' => $fileSrc,
                    'category' => 'image'
                ]);
                $files[] = $fileModel;
            }
            (new SocketService())->send($imgMsg, $buyback);
             // todo ДЕЛАЕМ СРАЗУ ПОДТВЕРЖДЕНИЕ ЗАКАЗА ПРОДАВЦОМ!!! ЭТО 5 минут!
            switch ($request->file_type){
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
               'files' => $files,
                'system_type' => $request->file_type
            ], 201);
        }catch (\Exception $e){
            DB::rollBack();
            return response()->json([
                'status'  => 'false',
                'message' => 'Произошла ошибка, попробуйте еще раз',
            ], 500);
        }
    }
}
