<?php

namespace Database\Seeders;

use App\Models\Ad;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;

class AdvSeed extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Найдем товар по его ID
        $product = Product::where('wb_id', 15096631)->first(); // Получаем товар с wb_id 15096631

        // Если товар найден, создаем объявления
        if ($product) {
            $advertisements = [
                [
                    'product_id'              => $product->id,
                    'name'                    => 'Футболка базовая на рост 164 - Скидка 20%',
                    'cashback_percentage'     => 10.00,
                    'price_with_cashback'     => 362.00, // Цена с учетом кешбека
                    'order_conditions'        => 'Минимальная сумма заказа — 500 руб.',
                    'redemption_instructions' => 'Для получения кешбека, совершите покупку на сайте.',
                    'review_criteria'         => 'Оценка товара от 1 до 5 звезд.',
                    'redemption_count'        => 5,
                    'views_count'             => 20,
                    'one_per_user'            => true,
                    'is_archived'             => false,
                    'status'                  => true,
                ],
                [
                    'product_id'              => $product->id,
                    'name'                    => 'Футболка базовая на рост 164 - Специальное предложение',
                    'cashback_percentage'     => 15.00,
                    'price_with_cashback'     => 342.00, // Цена с учетом кешбека
                    'order_conditions'        => 'Товар доступен только в определенные дни.',
                    'redemption_instructions' => 'Для получения скидки используйте промокод в корзине.',
                    'review_criteria'         => 'Оставьте отзыв и получите скидку на следующий заказ.',
                    'redemption_count'        => 10,
                    'views_count'             => 50,
                    'one_per_user'            => true,
                    'is_archived'             => false,
                    'status'                  => false,
                ],
                [
                    'product_id'              => $product->id,
                    'name'                    => 'Футболка базовая на рост 164 - Новинка сезона',
                    'cashback_percentage'     => 12.00,
                    'price_with_cashback'     => 355.00, // Цена с учетом кешбека
                    'order_conditions'        => 'Акция действует до конца месяца.',
                    'redemption_instructions' => 'Кешбек будет возвращен на ваш счет через 7 дней.',
                    'review_criteria'         => 'Оставьте 3 звезды и получите бонус в 5% на следующую покупку.',
                    'redemption_count'        => 7,
                    'views_count'             => 30,
                    'one_per_user'            => false,
                    'is_archived'             => false,
                    'status'                  => false,
                ],
            ];

            // Создаем объявления
            foreach ($advertisements as $ad) {
                $ad['user_id'] = User::where('email', 'alexey@email.net')
                    ->pluck('id')
                    ->first();
                $ad['balance'] = 1000;
                Ad::create($ad);
            }
        } else {
            // Если товар не найден, выведем сообщение
            $this->command->error('Товар с wb_id 15096631 не найден!');
        }
    }
}
