<?php

namespace App\Http\Controllers\Buyer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Buyer\OrderController\SendRequest;
use App\Models\Buyback;
use App\Models\File;
use App\Models\Message;
use App\Services\Buyer\OrderService;
use App\Services\SocketService;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function store(string $ad_id)
    {
        return (new OrderService())->createOrder($ad_id);
    }

    public function index(Request $request)
    {
        $buybacks = auth('sanctum')->user()->buybacks()
            ->withFilter($request)
            ->with(['messages' => function ($query) {
                $query->latest()->first();
            }])
            ->get();

        return $buybacks;
    }

    public function show(string $id)
    {
        return Buyback::with([
                'messages',
                'ad' => function ($query) {
                        $query->without('reviews');
                    }
                ]
            )->where('user_id', auth('sanctum')->id())->findOrFail($id);
    }

    public function send(SendRequest $request, string $id): \Illuminate\Http\JsonResponse
    {
        $user_id = auth('sanctum')->id();
        $buyback = Buyback::where('user_id', $user_id)->findOrFail($id);

        $data = [];
        $data['text'] = $request->text;
        $data['sender_id'] = $user_id;
        $data['buyback_id'] = $id;

        $message = Message::create($data);

        # todo ТУТ проверяем тип и взамисимости от типа файла отправляем сообщение нужного цвета!

        if($request->hasFile('file'))
        {
            $fileSrc = $request->file('file')->store('files', 'public');
            $imgMsg = Message::create($data);
            File::create([
                'fileable_type' => 'App\Models\Message',
                'fileable_id' => $imgMsg->id,
                'src' => $fileSrc,
                'category' => $request->file_type
            ]);
        }

        (new SocketService())->send($message, $buyback);
        return response()->json([
            'status' => 'true',
            'message' => 'Сообщение отправлено'
        ], 201);
    }

    public function orderStatusList()
    {
        return [
            'cancelled' => 'Отменен',
            'pending' => 'Ожидание заказа',
            'awaiting_receipt' => 'Ожидание получения',
            'on_confirmation' => 'Подтверждение',
            'cashback_received' => 'Кешбек получен',
            'completed' => 'Завершено',
            'archive' => 'Архив'
        ];
    }
}
