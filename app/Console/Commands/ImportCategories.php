<?php

namespace App\Console\Commands;

use App\Models\Category;
use Illuminate\Console\Command;
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
        $url = 'https://catalog.wb.ru/menu/v12/api?appType=1&lang=ru&locale=ru';
        $response = Http::withHeaders([
            'Accept' => 'application/json',
            'User-Agent' => 'Mozilla/5.0 (compatible; WBD-Category-Importer/1.0)',
        ])->get($url);

        if (! $response->successful()) {
            $this->error('Не удалось загрузить данные из API.');

            return Command::FAILURE;
        }

        $categories = $response->json('data');

        if (! is_array($categories)) {
            $this->error('Неверный формат данных, полученных из API.');

            return Command::FAILURE;
        }

        Category::truncate();

        $this->importCategories($categories);
        $this->info('Категории успешно импортированы.');

        return Command::SUCCESS;
    }

    private function importCategories(array $categories, $parentId = null)
    {
        foreach ($categories as $category) {
            $nodes = $category['nodes'] ?? ($category['childs'] ?? []);
            $nodes = is_array($nodes) ? $nodes : [];

            $newCategory = Category::create([
                'id' => $category['id'],
                'name' => $category['name'] ?? null,
                'parent_id' => $parentId,
                'url' => $category['url'] ?? null,
                'shard_key' => $category['shardKey'] ?? null,
                'raw_query' => $category['rawQuery'] ?? null,
                'query' => $category['query'] ?? null,
                'children_only' => $category['childrenOnly'] ?? false,
                'nodes' => $nodes,
            ]);

            if (! empty($nodes)) {
                $this->importCategories($nodes, $newCategory->id);
            }
        }
    }
}
