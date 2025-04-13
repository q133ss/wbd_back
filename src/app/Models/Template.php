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
            "text" => "Здравствуйте. Для участия в акции просим Вас:\n✅ Заказать кофе. Для поиска обязательно использовать следующие слова: Lacofe кофе зерновой. Далее выбираем кофе LACOFE GOLD (белая пачка на зеленом фоне). \n✅ Добавить магазин и товар в избранное\n✅ При получении товара выслать его фото с разрезанным (разорванным) штрих-кодом\n✅ Написать отзыв 5 звезд тогда, когда мы Вас попросим. Обычно, сразу или в течении нескольких дней после выкупа."
        ]);

        $this->create([
            'user_id' => $userId,
            "type" => "review_criteria",
            "text" => "3-7 слов + 1 фото + ВИДЕО (10-15 секунд) + 5 звезд. Перед отправкой согласуйте со мной в сообщениях."
        ]);
    }
}
