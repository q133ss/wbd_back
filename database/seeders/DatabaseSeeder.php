<?php

namespace Database\Seeders;

use App\Models\Ad;
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

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $roles = [
            ['slug' => 'admin', 'name' => 'Админ'],
            ['slug' => 'buyer', 'name' => 'Покупатель'],
            ['slug' => 'seller', 'name' => 'Продавец'],
        ];
        Role::insert($roles);

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

        Shop::create([
            'user_id'     => User::where('email', 'alexey@email.net')->pluck('id')->first(),
            'supplier_id' => '23274',
            'inn'         => '7724409915',
            'legal_name'  => 'УЗКОТТОН ООО',
            'wb_name'     => 'UZcotton',
        ]);

        if (env('APP_ENV') === 'production') {
            $this->command->info('Импорт категорий..');
            Artisan::call('categories:import');

            Category::create([
                'name' => 'Школа',
            ]);

            Category::create([
                'name' => 'Без категории',
            ]);

            $this->command->info('Фото для категорий');

            // Список категорий
//            $categories_list = [
//                'Женщинам',
//                'Обувь',
//                'Детям',
//                'Мужчинам',
//                'Дом',
//                'Красота',
//                'Аксессуары',
//                'Электроника',
//                'Игрушки',
//                'Мебель',
//                'Товары для взрослых',
//                'Продукты',
//                'Цветы',
//                'Бытовая техника',
//                'Зоотовары',
//                'Спорт',
//                'Автотовары',
//                'Школа',
//                'Книги',
//                'Ювелирные изделия',
//                'Для ремонта',
//                'Сад и дача',
//                'Здоровье',
//                'Канцтовары',
//                'Акции',
//                'Культурный код',
//            ];
//
//            $categories = Category::whereIn('name', $categories_list)->get();
//            foreach ($categories as $category) {
//                File::create([
//                    'fileable_type' => 'App\Models\Category',
//                    'fileable_id'   => $category->id,
//                    'category'      => 'img',
//                    'src'           => 'images/categories/'.$category->name.'.jpg',
//                ]);
//                $this->command->info("Фото для категории: {$category->name}");
//            }
        }

        $admin = User::create([
            'name'             => 'admin',
            'email'            => 'admin@email.net',
            'redemption_count' => 100,
            'balance'          => 10000,
            'phone'            => '+7(999)999-99-99',
            'password'         => bcrypt('password'),
            'role_id'          => Role::where('slug', 'admin')->pluck('id')->first(),
        ]);

        Shop::create([
            'user_id'     => User::where('email', 'admin@email.net')->pluck('id')->first(),
            'supplier_id' => '83274',
            'inn'         => '772440935',
            'legal_name'  => 'Магазин 123',
            'wb_name'     => 'shop123',
        ]);

        $template->createDefault($admin->id);

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

        // Промокод

        Promocode::create([
            'name'           => 'Тестовый промокод',
            'promocode'      => 'test2025',
            'start_date'     => now(),
            'end_date'       => now()->addDays(30),
            'buybacks_count' => 10,
            'max_usage'      => 5,
        ]);

        $buyer = User::create([
            'name'             => 'Покупатель',
            'email'            => 'buyer@email.net',
            'redemption_count' => 0,
            'balance'          => 0,
            'phone'            => '+7(222)222-22-22',
            'password'         => bcrypt('password'),
            'role_id'          => Role::where('slug', 'buyer')->pluck('id')->first(),
        ]);

        Shop::create([
            'user_id'     => User::where('email', 'buyer@email.net')->pluck('id')->first(),
            'supplier_id' => '83224',
            'inn'         => '732440935',
            'legal_name'  => 'Магазин 2',
            'wb_name'     => 'shop2',
        ]);

        $template->createDefault($buyer->id);

        $this->command->call('categories:import');

        $this->call(ReviewSeed::class);
    }
}
