<?php

namespace App\Console\Commands;

use App\Models\Category;
use Illuminate\Console\Command;
use App\Models\File;
use Illuminate\Support\Facades\Http;

class ImportCategories extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'categories:import';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Импорт категорий из API';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $url      = 'https://static-basket-01.wbbasket.ru/vol0/data/main-menu-ru-ru-v3.json';
        $response = Http::get($url);

        if ($response->successful()) {
            $categories = $response->json();
            $this->importCategories($categories);
            $this->info('Категории успешно импортированы.');
        } else {
            $this->error('Не удалось загрузить данные из API.');
        }
    }

    private function importCategories(array $categories, $parentId = null)
    {
        $imgCategories = [
            'Автотовары',
            'Аксессуары',
            'Акции',
            'Бытовая техника',
            'Детям',
            'Для ремонта',
            'Дом',
            'Женщинам',
            'Здоровье',
            'Зоотовары',
            'Игрушки',
            'Канцтовары',
            'Книги',
            'Красота',
            'Культурный код',
            'Мебель',
            'Мужчинам',
            'Обувь',
            'Продукты',
            'Сад и дача',
            'Спорт',
            'Товары для взрослых',
            'Цветы',
            'Школа',
            'Электроника',
            'Ювелирные изделия'
        ];
        foreach ($categories as $category) {
            $newCategory = Category::updateOrCreate(
                ['id' => $category['id']],
                [
                    'name'      => $category['name'],
                    'parent_id' => $parentId,
                ]
            );

            if($parentId == null){
                if(in_array($category['name'], $imgCategories)){
                    File::create([
                        'src' => 'images/categories/'.$category['name'].'.jpg',
                        'fileable_type' => 'App\Models\Category',
                        'fileable_id' => $newCategory->id,
                        'category' => 'img'
                    ]);
                }
            }

            if (isset($category['childs'])) {
                $this->importCategories($category['childs'], $newCategory->id);
            }
        }
    }
}
