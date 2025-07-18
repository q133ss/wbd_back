<?php

namespace Database\Seeders;

use App\Models\Ad;
use App\Models\Shop;
use App\Models\User;
use App\Services\WBService;
use http\Env\Request;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ReviewSeed extends Seeder
{
    private $articles = [
        '206342089',
        '128033464',
        '35778729',
        '141090751',
        '175570960',
        '293853820',
        '140851046',
        '123977427',
        '362387767',
        '222462692',
        '9259475',
        '154532597',
        '318032344',
        '150755426',
        '264803790',
        '128089284',
        '251773651',
        '18273763',
        '305599975',
        '232195241',
        '217824605',
        '221994647',
        '177580147',
        '90409964',
        '250986633',
        '217353421',
        '226631487',
        '252979725',
        '227193794',
        '200262346',
        '231253340',
        '216726404',
        '183761213',
        '264651515',
        '160759640',
        '185967293',
        '193714925',
        '235096768',
        '233355893',
        '282355919',
        '55193392',
        '93378992',
        '240099618',
        '328103404',
        '196225754',
        '279285869',
        '83363563',
        '399552254'
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $wbService = app(WBService::class);

        foreach ($this->articles as $article) {
            // 1. Создаем пользователя
            $user = User::create([
                'name' => 'User_' . $article,
                'email' => 'user_' . $article . '@example.com',
                'email_verified_at' => now(),
                'password' => Hash::make('password'),
                'remember_token' => Str::random(10),
                'phone' => '+7' . rand(9000000000, 9999999999),
                'role_id' => 3,
                'is_configured' => true,
                'balance' => 0,
                'redemption_count' => 0,
                'telegram_id' => null
            ]);


            // 2 Авторизуемся под пользователем
            Auth::login($user);

            $request = new \Illuminate\Http\Request();
            try {
                // 3. Создаем товар через WBService
                $response = $wbService->addProduct($request, $article);

                if ($response->getData()->status !== 'true') {
                    $this->command->error("Failed to create product for article {$article}: " . $response->getData()->message);
                    continue;
                }

                $product = $response->getData()->product;

                // 4. Создаем объявление
                $ad = Ad::create([
                    'product_id' => $product->id,
                    'name' => $product->name,
                    'cashback_percentage' => rand(5, 20),
                    'price_with_cashback' => $product->price * (1 - rand(5, 20)/100),
                    'order_conditions' => 'Кэшбек идет за отзыв 1 товар в 1 руки. Начисление кэшбека 3 дня после отзыва. Если отзыв не опубликован, то 50% кэшбека',
                    'redemption_instructions' => 'Здравствуйте. Для участия в акции просим Вас:\n✅ Заказать кофе. Для поиска обязательно использовать следующие слова: Lacofe кофе зерновой. Далее выбираем кофе LACOFE GOLD (белая пачка на зеленом фоне). \n✅ Добавить магазин и товар в избранное\n✅ При получении товара выслать его фото с разрезанным (разорванным) штрих-кодом\n✅ Написать отзыв 5 звезд тогда, когда мы Вас попросим. Обычно, сразу или в течении нескольких дней после выкупа.',
                    'review_criteria' => '3-7 слов + 1 фото + ВИДЕО (10-15 секунд) + 5 звезд. Перед отправкой согласуйте со мной в сообщениях',
                    'redemption_count' => rand(3,40),
                    'views_count' => 0,
                    'one_per_user' => true,
                    'is_archived' => false,
                    'status' => true,
                    'balance' => rand(1000,20000),
                    'in_favorite' => false,
                    'user_id' => $user->id
                ]);

                $this->command->info("Successfully created product and ad for article {$article}");
            } catch (\Exception $e) {
                $this->command->error("Error processing article {$article}: " . $e->getMessage());
            }

            // Выходим из аккаунта
            Auth::logout();
        }

        $users = User::skip(3)->take(9999)->get();
        $shops = Shop::skip(3)->take(9999)->get();

        $index = 0;
        foreach ($users as $user){
            try {
                $shops[$index]->update(['user_id' => $user->id]);
            }catch (\Exception $e){

            }
            $index++;
        }
    }
}
