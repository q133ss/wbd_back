<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ProductCategorySync extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'product:category-sync';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Синхронизация категорий продуктов с ВБ';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Начинаем синхронизацию категорий продуктов с ВБ...');
        $service = new \App\Services\WBService();
        $service->syncProductCategories();
        $this->info('Синхронизация завершена.');
    }
}
