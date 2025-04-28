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
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class StressSeeder extends Seeder
{
    /**
     * Seed the application's database under stress conditions.
     * 1) migrate:fresh
     * 2) php artisan db:seed --class=StressSeeder
     */
    public function run(): void
    {
        // Отключаем логирование для ускорения
        DB::disableQueryLog();

        // Увеличиваем лимиты памяти и времени выполнения
        ini_set('memory_limit', '2048M');
        set_time_limit(3600);

        $this->command->info('Starting stress test seeding...');

        // 1. Создаем роли
        $roles = [
            ['slug' => 'admin', 'name' => 'Админ'],
            ['slug' => 'buyer', 'name' => 'Покупатель'],
            ['slug' => 'seller', 'name' => 'Продавец'],
        ];
        Role::insert($roles);

        // 2. Создаем массово пользователей
        $this->command->info('Creating 10,000 users...');

        $progressBar = $this->command->getOutput()->createProgressBar(100);
        $progressBar->setFormat('debug');
        $progressBar->start();

        $users = User::factory()->count(10000)->make()
            ->each(function ($user, $index) use ($progressBar) {
                do {
                    $phone = 'u_' . Str::random(16) . mt_rand(0, 99);
                } while (User::where('phone', $phone)->exists());

                $user->forceFill([
                    'phone' => $phone,
                    'role_id' => Role::where('slug', 'buyer')->first()->id,
                    'password' => bcrypt('password')
                ])->save();

                if (($index + 1) % 100 === 0) {
                    $progressBar->advance();
                    $this->command->info(" Создано " . ($index + 1) . " покупателей");
                }
            });

        $progressBar->finish();

        // 3. Создаем 1000 продавцов с магазинами
        $this->command->info('Creating 1,000 sellers with shops...');
        $sellerProgress = $this->command->getOutput()->createProgressBar(10);
        $sellerProgress->setFormat('debug');
        $sellerProgress->start();

        $sellers = User::factory()->count(1000)->make()
            ->each(function ($user, $index) use ($sellerProgress) {
                do {
                    $phone = 's_' . Str::random(16) . mt_rand(0, 99);
                } while (User::where('phone', $phone)->exists());

                $user->forceFill([
                    'phone' => $phone,
                    'role_id' => Role::where('slug', 'seller')->first()->id,
                    'password' => bcrypt('password')
                ])->save();

                if (($index + 1) % 100 === 0) {
                    $sellerProgress->advance();
                    $this->command->info(" Created " . ($index + 1) . " sellers");
                }
            });

        $sellerProgress->finish();

        $sellers->each(function ($seller) {
            Shop::create([
                'user_id' => $seller->id,
                'supplier_id' => rand(10000, 99999),
                'inn' => rand(1000000000, 9999999999),
                'legal_name' => 'Shop ' . Str::random(10),
                'wb_name' => 'WB' . Str::random(8),
            ]);

            // Создаем шаблон для продавца
            (new Template())->createDefault($seller->id);
        });

        // 4. Создаем 50,000 товаров
        $this->command->info('Creating 50,000 products...');
        $shopIds = Shop::pluck('id')->toArray();

        for ($i = 0; $i < 50000; $i++) {
            Product::create([
                'wb_id' => rand(10000000, 99999999),
                'name' => 'Product ' . Str::random(10),
                'price' => rand(100, 10000),
                'brand' => 'Brand ' . Str::random(5),
                'discount' => rand(0, 70),
                'rating' => rand(1, 5),
                'quantity_available' => rand(0, 1000),
                'supplier_id' => rand(10000, 99999),
                'category_id' => rand(100000, 999999),
                'description' => Str::random(200),
                'supplier_rating' => rand(30, 50) / 10,
                'shop_id' => $shopIds[array_rand($shopIds)],
                'images' => $this->generateRandomImages(),
            ]);

            // Выводим прогресс каждые 1000 товаров
            if ($i % 1000 === 0) {
                $this->command->info("Created {$i} products...");
            }
        }

        // 5. Создаем 100,000 объявлений
        $this->command->info('Creating 100,000 ads...');
        $productIds = Product::pluck('id')->toArray();
        $sellerIds = User::where('role_id', Role::where('slug', 'seller')->first()->id)
            ->pluck('id')->toArray();

        for ($i = 0; $i < 100000; $i++) {
            Ad::create([
                'product_id' => $productIds[array_rand($productIds)],
                'user_id' => $sellerIds[array_rand($sellerIds)],
                'status' => rand(0, 1),
                'price' => rand(100, 10000),
                'description' => Str::random(100),
            ]);

            if ($i % 10000 === 0) {
                $this->command->info("Created {$i} ads...");
            }
        }

        // 6. Создаем 500,000 отзывов
        $this->command->info('Creating 500,000 reviews...');
        $buyerIds = User::where('role_id', Role::where('slug', 'buyer')->first()->id)
            ->pluck('id')->toArray();
        $adIds = Ad::pluck('id')->toArray();

        for ($i = 0; $i < 500000; $i++) {
            Review::create([
                'user_id' => $buyerIds[array_rand($buyerIds)],
                'ads_id' => $adIds[array_rand($adIds)],
                'rating' => rand(1, 5),
                'text' => Str::random(50),
                'reviewable_id' => $adIds[array_rand($adIds)],
                'reviewable_type' => 'App\Models\Ad',
            ]);

            if ($i % 50000 === 0) {
                $this->command->info("Created {$i} reviews...");
            }
        }

        // 7. Создаем тарифы и промокоды
        $this->command->info('Creating tariffs and promocodes...');
        Tariff::insert([
            [
                'name' => 'Start',
                'price' => 100,
                'buybacks_count' => 1,
                'advantages' => json_encode(['Выкуп живыми пользователями', 'Фото/видео отзыв']),
                'redemption_price' => 100
            ],
            [
                'name' => 'Optimal',
                'price' => 1900,
                'buybacks_count' => 20,
                'advantages' => json_encode(['Выкуп живыми пользователями', 'Фото/видео отзыв']),
                'redemption_price' => 95
            ],
            [
                'name' => 'Premium',
                'price' => 9000,
                'buybacks_count' => 100,
                'advantages' => json_encode(['Выкуп живыми пользователями', 'Фото/видео отзыв']),
                'redemption_price' => 90
            ],
            [
                'name' => 'Ultima',
                'price' => 40000,
                'buybacks_count' => 500,
                'advantages' => json_encode(['Выкуп живыми пользователями', 'Фото/видео отзыв']),
                'redemption_price' => 80
            ]
        ]);

        Promocode::create([
            'name' => 'StressTest2023',
            'promocode' => 'STRESSTEST',
            'start_date' => now(),
            'end_date' => now()->addYear(),
            'buybacks_count' => 100,
            'max_usage' => 1000,
        ]);

        $this->command->info('Stress test seeding completed!');
    }

    /**
     * Генерирует случайные изображения для товаров
     */
    private function generateRandomImages(): array
    {
        $images = [];
        $count = rand(1, 15);

        for ($i = 1; $i <= $count; $i++) {
            $images[] = 'https://basket-' . rand(1, 12) . '.wbbasket.ru/vol' . rand(100, 200) .
                '/part' . rand(10000, 99999) . '/' . rand(10000000, 99999999) .
                '/images/big/' . $i . '.webp';
        }

        return $images;
    }
}
