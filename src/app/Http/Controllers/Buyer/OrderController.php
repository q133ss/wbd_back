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
        $buybacks = auth('sanctum')->user()->buybacks;

        $statuses = [
            [
                'title' => 'Все',
                'not_read' => $buybacks->flatMap->messages->where('is_read', false)->count(),
                'slug' => 'all',
            ],
            [
                'title' => 'Отменен',
                'not_read' => $buybacks->where('status', 'cancelled')->flatMap->messages->where('is_read', false)->count(),
                'slug' => 'cancelled',
            ],
            [
                'title' => 'Ожидание заказа',
                'not_read' => $buybacks->where('status', 'pending')->flatMap->messages->where('is_read', false)->count(),
                'slug' => 'pending',
            ],
            [
                'title' => 'Ожидание получения',
                'not_read' => $buybacks->where('status', 'awaiting_receipt')->flatMap->messages->where('is_read', false)->count(),
                'slug' => 'awaiting_receipt',
            ],
            [
                'title' => 'Подтверждение',
                'not_read' => $buybacks->where('status', 'on_confirmation')->flatMap->messages->where('is_read', false)->count(),
                'slug' => 'on_confirmation',
            ],
            [
                'title' => 'Кешбек получен',
                'not_read' => $buybacks->where('status', 'cashback_received')->flatMap->messages->where('is_read', false)->count(),
                'slug' => 'cashback_received',
            ],
            [
                'title' => 'Завершено',
                'not_read' => $buybacks->where('status', 'completed')->flatMap->messages->where('is_read', false)->count(),
                'slug' => 'completed',
            ],
            [
                'title' => 'Архив',
                'not_read' => $buybacks->where('status', 'archive')->flatMap->messages->where('is_read', false)->count(),
                'slug' => 'archive',
            ]
        ];

        return $statuses;
    }
}
