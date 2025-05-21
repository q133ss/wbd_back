<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Template extends Model
{
    protected $guarded = [];

    public function createDefault(string $userId)
    {
        $this->create([
            'user_id' => $userId,
            "type" => "order_conditions",
            "text" => "Кэшбек идет за отзыв 1 товар в 1 руки. Начисление кэшбека 3 дня после отзыва. Если отзыв не опубликован, то 50% кэшбека"
        ]);

        $this->create([
            'user_id' => $userId,
            "type" => "redemption_instructions",
            "text" => "Здравствуйте. Для участия в акции просим Вас:\n
1) Заказать *товар*. Для поиска обязательно использовать\n
следующие слова: *ключевые слова*. Далее выбираем\n
*товар*. (можете дать описание товара).\n
2) Добавить магазин и товар в избранное\n
3) При получении товара выслать его фото с разрезанным\n
(разорванным) штрих-кодом\n
4) Написать отзыв согласно условиям которые отправим вам"
        ]);

        $this->create([
            'user_id' => $userId,
            "type" => "review_criteria",
            "text" => "3-7 слов + 1 фото + ВИДЕО (10-15 секунд) + 5 звезд. Перед отправкой согласуйте со мной в сообщениях."
        ]);
    }
}
