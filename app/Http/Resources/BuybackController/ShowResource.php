<?php

namespace App\Http\Resources\BuybackController;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Pagination\LengthAwarePaginator;

class ShowResource extends JsonResource
{
    public static $wrap = null;
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $userId = auth('sanctum')->id();

        $messages = $this->messages instanceof LengthAwarePaginator
            ? $this->messages->getCollection()
            : collect($this->messages);

        // Добавляем проверку, что элемент является объектом и имеет свойство sender_id
        $messages = $messages->map(function($message) use ($userId) {
            if (is_object($message)) {  // Добавлена закрывающая скобка для is_object
                $message->whoSend = $message->sender_id == $userId ? 'buyer' : 'seller';
            }
            return $message;
        });

        $result = [
            'price' => $this->price,
            'id' => $this->id,
            'has_review_by_buyer' => $this->has_review_by_buyer,
            'has_review_by_seller' => $this->has_review_by_seller,
            'is_order_photo_sent' => $this->is_order_photo_sent,
            'is_review_photo_sent' => $this->is_review_photo_sent,
            'is_archived' => $this->is_archived,
            'status' => $this->status,
            'ad' => $this->ad,
            'user' => $this->user,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];

        if ($this->messages instanceof LengthAwarePaginator) {
            $this->messages->setCollection($messages);
            $result['messages'] = $this->messages;
        } else {
            $result['messages'] = $messages;
        }

        return $result;
    }
}
