<?php

namespace App\Services;

use App\Models\Review;

class ReviewService
{
    /**
     * Создает отзыв от лица текущего юзера
     *
     * @param string $ads_id
     * @param string $rating
     * @param string $text
     * @param string $type
     * @param string $id
     * @return mixed
     */
    public function create(string $ads_id, string $rating, string $text, string $type, string $id)
    {
        return Review::create([
            'user_id' => auth('sanctum')->id(),
            'ads_id' => $ads_id,
            'rating' => $rating,
            'text'   => $text,
            'reviewable_type'   => $type,
            'reviewable_id'   => $id
        ]);
    }
}
