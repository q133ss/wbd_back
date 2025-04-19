<?php

namespace App\Http\Controllers;

use App\Models\Ad;
use App\Models\User;
use Illuminate\Http\Request;
use Spatie\Sitemap\Sitemap;
use Spatie\Sitemap\Tags\Url;

class SitemapController extends Controller
{
    public function generate()
    {
        $sitemap = Sitemap::create()
            ->add(Url::create('/')
                ->setPriority(1.0)
                ->setChangeFrequency(Url::CHANGE_FREQUENCY_DAILY))

            ->add(Url::create('/user-conditions')
                ->setPriority(0.8)
                ->setChangeFrequency(Url::CHANGE_FREQUENCY_MONTHLY))

            ->add(Url::create('/policy')
                ->setPriority(0.8)
                ->setChangeFrequency(Url::CHANGE_FREQUENCY_MONTHLY))

            ->add(Url::create('/buyer/category')
                ->setPriority(0.9)
                ->setChangeFrequency(Url::CHANGE_FREQUENCY_WEEKLY));

        // Добавляем динамические страницы товаров
        Ad::where('status', true)->where('is_archived', false)->chunk(100, function($products) use (&$sitemap) {
            foreach ($products as $product) {
                $sitemap->add(Url::create("/buyer/products/{$product->id}")
                    ->setPriority(0.7)
                    ->setChangeFrequency(Url::CHANGE_FREQUENCY_WEEKLY));
            }
        });

        // Добавляем страницы продавцов
        User::whereHas('shop')->chunk(100, function($users) use (&$sitemap) {
            foreach ($users as $user) {
                $sitemap->add(Url::create("/buyer/salesman/{$user->id}")
                    ->setPriority(0.6)
                    ->setChangeFrequency(Url::CHANGE_FREQUENCY_MONTHLY));
            }
        });

        return $sitemap->writeToFile(public_path('sitemap.xml'));
    }

    public function show()
    {
        return response()->file(public_path('sitemap.xml'));
    }
}
