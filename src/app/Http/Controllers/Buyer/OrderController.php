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
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function store(string $ad_id)
    {
        return (new OrderService)->createOrder($ad_id);
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
        $userId = auth('sanctum')->id();

        // Загружаем buyback без сообщений сначала
        $buyback = Buyback::with('ad')->where('buybacks.user_id', $userId)->findOrFail($id);

        // Загружаем сообщения с пагинацией
        $messages = $buyback->messages()->paginate(15);

        // Обрабатываем сообщения
        $adUserId = $buyback->ad?->user_id;
        $isBuyer = $buyback->user_id == $adUserId;

        $messages->getCollection()->transform(function ($message) use ($isBuyer, $buyback) {
            $message->whoSend = ($message->sender_id == $buyback->user_id) == $isBuyer ? 'buyer' : 'seller';
            return $message;
        });

        // Возвращаем buyback с пагинированными сообщениями
        $buyback->setRelation('messages', $messages);

        return $buyback;
    }

    public function send(SendRequest $request, string $id): \Illuminate\Http\JsonResponse
    {
        DB::beginTransaction();

        try {
            $user_id = auth('sanctum')->id();
            $buyback = Buyback::where('user_id', $user_id)->findOrFail($id);

            $data = [
                'text' => $request->text,
                'sender_id' => $user_id,
                'buyback_id' => $id
            ];

            // Создаем основное сообщение
            $message = Message::create($data);

            // Обрабатываем файл, если есть
            if ($request->hasFile('file')) {
                $file = $request->file('file');
                $fileSrc = '/storage/' . $file->store('files', 'public');

                File::create([
                    'fileable_type' => Message::class,
                    'fileable_id' => $message->id, // Привязываем к основному сообщению
                    'src' => $fileSrc,
                    'category' => $request->file_type ?? 'image', // Дефолтное значение
                ]);
            }

            // Явно подгружаем файлы перед отправкой
            $message->load('files');

            DB::commit();

            // Отправляем через WebSocket
            (new SocketService)->send($message, $buyback);

            return response()->json([
                'status' => true,
                'message' => $message,
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Message send error: ' . $e->getMessage());

            return response()->json([
                'status' => false,
                'error' => 'Failed to send message',
                'details' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    public function orderStatusList()
    {
        $buybacks = auth('sanctum')->user()->buybacks;

        $statuses = [
            [
                'title'    => 'Все',
                'not_read' => $buybacks->flatMap->messages->where('is_read', false)->count(),
                'slug'     => 'all',
            ],
            [
                'title'    => 'Отменен',
                'not_read' => $buybacks->where('status', 'cancelled')->flatMap->messages->where('is_read', false)->count(),
                'slug'     => 'cancelled',
            ],
            [
                'title'    => 'Ожидание заказа',
                'not_read' => $buybacks->where('status', 'pending')->flatMap->messages->where('is_read', false)->count(),
                'slug'     => 'pending',
            ],
            [
                'title'    => 'Ожидание получения',
                'not_read' => $buybacks->where('status', 'awaiting_receipt')->flatMap->messages->where('is_read', false)->count(),
                'slug'     => 'awaiting_receipt',
            ],
            [
                'title'    => 'Подтверждение',
                'not_read' => $buybacks->where('status', 'on_confirmation')->flatMap->messages->where('is_read', false)->count(),
                'slug'     => 'on_confirmation',
            ],
            [
                'title'    => 'Кешбек получен',
                'not_read' => $buybacks->where('status', 'cashback_received')->flatMap->messages->where('is_read', false)->count(),
                'slug'     => 'cashback_received',
            ],
            [
                'title'    => 'Завершено',
                'not_read' => $buybacks->where('status', 'completed')->flatMap->messages->where('is_read', false)->count(),
                'slug'     => 'completed',
            ],
            [
                'title'    => 'Архив',
                'not_read' => $buybacks->where('status', 'archive')->flatMap->messages->where('is_read', false)->count(),
                'slug'     => 'archive',
            ],
        ];

        return $statuses;
    }
}
