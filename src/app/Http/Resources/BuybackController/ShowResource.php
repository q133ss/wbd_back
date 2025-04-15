<?php

namespace App\Http\Resources\BuybackController;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

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
        return [
            'price' => $this->price,
            'id' => $this->id,
            'has_review_by_buyer' => $this->has_review_by_buyer,
            'has_review_by_seller' => $this->has_review_by_seller,
            'is_archived' => $this->is_archived,
            'status' => $this->status,
            'messages' => $this->messages,
            'ad' => $this->ad
        ];
    }
}
