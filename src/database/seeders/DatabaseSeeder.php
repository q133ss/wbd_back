<?php

namespace Database\Seeders;

use App\Models\Ad;
use App\Models\Product;
use App\Models\Promocode;
use App\Models\Review;
use App\Models\Role;
use App\Models\Shop;
use App\Models\Tariff;
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

        User::factory()->create([
            'name'             => 'Алексей',
            'email'            => 'alexey@email.net',
            'phone'            => '+7(951)867-70-86',
            'redemption_count' => 100,
            'balance'          => 10000,
            'password'         => bcrypt('password'),
            'role_id'          => Role::where('slug', 'seller')->pluck('id')->first(),
        ]);

        Shop::create([
            'user_id'     => User::where('email', 'alexey@email.net')->pluck('id')->first(),
            'supplier_id' => '23274',
            'inn'         => '7724409915',
            'legal_name'  => 'УЗКОТТОН ООО',
            'wb_name'     => 'UZcotton',
        ]);

        Product::create([
            'wb_id'              => 15096631,
            'name'               => 'Футболка базовая на рост 164',
            'price'              => 402.00,
            'brand'              => 'UZcotton',
            'discount'           => 60.00,
            'rating'             => 5.00,
            'quantity_available' => 100,
            'supplier_id'        => '23274',
            'category_id'        => '131290',
            'description'        => 'Женская базовая белая футболка от бренда UzCotton станет незаменимой частью гардероба. Эта футболка прямого кроя из мягкого 100% хлопка плотностью 180г/м2, обладает потрясающей универсальностью. Мягкая ткань из гребенной пряжи позволяет телу дышать, что особенно важно во время физической активности. Это подходящий вариант как для занятия спортом, так и для повседневной носки. Ткань пенье добавляет изделию долговечности и устойчивости к износу.  Однотонная женская футболка станет отличным базовым элементом в любом гардеробе. Она имеет округлый вырез и короткий рукав. Плечевой шов и горловина которой усилены бейкой по всей длине. Длинная модель позволяет создать уникальные образы на каждый день, подчеркивая индивидуальность владельца. Независимо от того, носите ли вы ее на работу или для повседневных дел, вы всегда будете чувствовать себя свежо и удобно.   Женская удлиненная футболка классического прямого кроя подходит для любых типов фигур и доступна в широком диапазоне размеров, от маленьких до больших.   Однотонная расцветка делают ее любимым выбором в любое время года. Стильная футболка подойдет для женщин всех возрастов. Футболка также подойдет для девочки подростка и доступна в различных цветах, что позволяет создавать образы в разных стилях. Она может быть использована как домашняя, так и как спортивная.   Модная футболка уместна как офисная, школьная или просто для прогулок по городу или похода на пляж. Благодаря своему универсальному стилю и высокому качеству она станет любимой вещью в вашем гардеробе.   Также в нашем ассортименте есть футболки стиля унисекс (арт. 12801485, 12801489), что делает ее отличным выбором для пар, друзей или семей. Для подростков невысокого роста модель будет удлиненная.   У наших футболок широкая палитра цветов, из которых вы можете выбрать наиболее соответствующий вашему вкусу. Ещё больший выбор цветов - арт. 15096655.   Модель идет на рост 164 см.  Для более свободной посадки рекомендуем брать на 1-2 размера больше.',
            'supplier_rating'    => 4.80,
            'shop_id'            => 1,
            'images'             => '["https:\/\/basket-1.wbbasket.ru\/vol150\/part15096\/15096631\/images\/big\/1.webp","https:\/\/basket-1.wbbasket.ru\/vol150\/part15096\/15096631\/images\/big\/2.webp","https:\/\/basket-1.wbbasket.ru\/vol150\/part15096\/15096631\/images\/big\/3.webp","https:\/\/basket-1.wbbasket.ru\/vol150\/part15096\/15096631\/images\/big\/4.webp","https:\/\/basket-1.wbbasket.ru\/vol150\/part15096\/15096631\/images\/big\/5.webp","https:\/\/basket-1.wbbasket.ru\/vol150\/part15096\/15096631\/images\/big\/6.webp","https:\/\/basket-1.wbbasket.ru\/vol150\/part15096\/15096631\/images\/big\/7.webp","https:\/\/basket-1.wbbasket.ru\/vol150\/part15096\/15096631\/images\/big\/8.webp","https:\/\/basket-1.wbbasket.ru\/vol150\/part15096\/15096631\/images\/big\/9.webp","https:\/\/basket-1.wbbasket.ru\/vol150\/part15096\/15096631\/images\/big\/10.webp","https:\/\/basket-1.wbbasket.ru\/vol150\/part15096\/15096631\/images\/big\/11.webp","https:\/\/basket-1.wbbasket.ru\/vol150\/part15096\/15096631\/images\/big\/12.webp","https:\/\/basket-1.wbbasket.ru\/vol150\/part15096\/15096631\/images\/big\/13.webp","https:\/\/basket-1.wbbasket.ru\/vol150\/part15096\/15096631\/images\/big\/14.webp","https:\/\/basket-1.wbbasket.ru\/vol150\/part15096\/15096631\/images\/big\/15.webp"]',
        ]);

        // ads
        $this->call(AdvSeed::class);

        $this->command->info('Импорт категорий..');
        //Artisan::call('categories:import');

        User::create([
            'name'             => 'admin',
            'email'            => 'admin@email.net',
            'redemption_count' => 100,
            'balance'          => 10000,
            'phone'            => '+7(999)999-99-99',
            'password'         => bcrypt('password'),
            'role_id'          => Role::where('slug', 'admin')->pluck('id')->first(),
        ]);

        $this->command->info('Создаем тарифы');

        Tariff::create([
            'name'           => 'Тариф 1',
            'price'          => 900,
            'buybacks_count' => 10,
        ]);

        Tariff::create([
            'name'           => 'Тариф 2',
            'price'          => 1600,
            'buybacks_count' => 20,
        ]);

        Tariff::create([
            'name'           => 'Тариф 3',
            'price'          => 2000,
            'buybacks_count' => 30,
        ]);

        Promocode::create([
            'name' => 'Тестовый промокод',
            'promocode' => 'test2025',
            'start_date' => now(),
            'end_date' => now()->addDays(30),
            'buybacks_count' => 10,
            'max_usage' => 5
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

        Review::create([
            'user_id' => $buyer->id,
            'ads_id'  => Ad::pluck('id')->first(),
            'rating'  => 5,
            'text'    => 'Отличный продукт!',
            'reviewable_id' => Ad::pluck('id')->first(),
            'reviewable_type' => 'App\Models\Ad'
        ]);

        Review::create([
            'user_id' => $buyer->id,
            'ads_id'  => Ad::pluck('id')->first(),
            'rating'  => 1,
            'text'    => 'Плохой продукт!',
            'reviewable_id' => Ad::pluck('id')->first(),
            'reviewable_type' => 'App\Models\Ad'
        ]);

        Review::create([
            'user_id' => $buyer->id,
            'ads_id'  => Ad::pluck('id')->first(),
            'rating'  => 5,
            'text'    => 'Средний продукт!!!!',
            'reviewable_id' => Ad::pluck('id')->first(),
            'reviewable_type' => 'App\Models\Ad'
        ]);
    }
}
