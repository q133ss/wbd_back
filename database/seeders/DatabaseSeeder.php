<?php

namespace Database\Seeders;

use App\Models\Ad;
use App\Models\Admin\Settings;
use App\Models\Category;
use App\Models\File;
use App\Models\Product;
use App\Models\Promocode;
use App\Models\Review;
use App\Models\Role;
use App\Models\Shop;
use App\Models\Tariff;
use App\Models\Template;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->command->info('Создание ролей');
        $roles = [
            ['slug' => 'admin', 'name' => 'Админ'],
            ['slug' => 'buyer', 'name' => 'Покупатель'],
            ['slug' => 'seller', 'name' => 'Продавец'],
        ];
        Role::insert($roles);


        $this->command->info('Создаем тарифы');

        Tariff::create([
            'name'           => 'Start',
            'price'          => 100,
            'buybacks_count' => 1,
            'advantages' => [
                'Выкуп живыми пользователями по ключевому запросу',
                'Фото/видео отзыв'
            ],
            'redemption_price' => 100
        ]);

        Tariff::create([
            'name'           => 'Optimal',
            'advantages' => [
                'Выкуп живыми пользователями по ключевому запросу',
                'Фото/видео отзыв'
            ],
            'price'          => 1900,
            'buybacks_count' => 20,
            'redemption_price' => 95
        ]);

        Tariff::create([
            'name'           => 'Premium',
            'advantages' => [
                'Выкуп живыми пользователями по ключевому запросу',
                'Фото/видео отзыв'
            ],
            'price'          => 9000,
            'buybacks_count' => 100,
            'redemption_price' => 90
        ]);

        Tariff::create([
            'name'           => 'Ultima',
            'advantages' => [
                'Выкуп живыми пользователями',
                'Фото/видео отзыв'
            ],
            'price'          => 40000,
            'buybacks_count' => 500,
            'redemption_price' => 80
        ]);


        $this->command->info('Создаем настройки');
        $settings = [
            [
                'key' => 'review_cashback_instructions ',  // Инструкция покупателю (текст сообщения после отправки скрина)
                'value' => 'Спасибо за заказ!<br><br>Чтобы получить кэшбек в размере {cashback}Р, вам нужно получить товар и написать отзыв.<br><br>Затем вам нужно загрузить 2 медиафайла:<br>1) Фото с порезаным штрихкодом (чтобы не было возможности сдать товар обратно).<br><br>2) Скрин из кабинета Вб, где виден текст оставленного вами отзыва на наш товар'
            ],
            [
                'key' => 'cashback_review_message', // После отправки 2х скринов!
                'value' => 'Спасибо за материалы! Мы проверим их, и если все корректно, то переведем кэшбека в размере {cashback} Рублей на ваши реквизиты!'
            ]
        ];

        foreach ($settings as $setting) {
            Settings::updateOrCreate(
                ['key' => $setting['key']],
                ['value' => $setting['value']]
            );
        }

        $this->command->info('Создаем пользователей. Продавец, админ, покупатель');
        $alexey = User::factory()->create([
            'name'             => 'Алексей',
            'email'            => 'alexey@email.net',
            'phone'            => '+7(951)867-70-86',
            'redemption_count' => 100,
            'balance'          => 10000,
            'password'         => bcrypt('password'),
            'role_id'          => Role::where('slug', 'seller')->pluck('id')->first(),
            'telegram_id'      => '461612832'
        ]);

        $template = new Template();
        $template->createDefault($alexey->id);

        $admin = User::create([
            'name'             => 'admin',
            'email'            => 'admin@email.net',
            'redemption_count' => 100,
            'balance'          => 10000,
            'phone'            => '+7(999)999-99-99',
            'password'         => bcrypt('password'),
            'role_id'          => Role::where('slug', 'admin')->pluck('id')->first(),
        ]);

        $template->createDefault($admin->id);

        User::create([
            'name'             => 'Покупатель',
            'email'            => 'buyer@email.net',
            'redemption_count' => 0,
            'balance'          => 0,
            'phone'            => '+7(222)222-22-22',
            'password'         => bcrypt('password'),
            'role_id'          => Role::where('slug', 'buyer')->pluck('id')->first(),
        ]);

        // Все, что ниже можно смело удалять, если не нужно

        $this->command->info('Создаем тестовый промокод');
        Promocode::create([
            'name'           => 'Тестовый промокод',
            'promocode'      => 'test2025',
            'start_date'     => now(),
            'end_date'       => now()->addDays(30),
            'buybacks_count' => 10,
            'max_usage'      => 5,
        ]);

        $this->command->info('Импортируем категории');
        $this->command->call('categories:import');

        $this->command->info('Создаем тестовые товары и объявления');
        $this->call(ReviewSeed::class);
    }
}
