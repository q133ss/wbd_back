<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Services\WBService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProductSyncPrice extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'product:sync-price';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Синхронизация цен товаров с WB';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Начинаю синхронизацию цен...');
        $service = new WBService();

        // Чанками, чтобы не грузить память
        Product::chunk(100, function ($products) use ($service) {
            $updates = [];

            foreach ($products as $product) {
                try {
                    $actualPrice = $service->getProductPrice($product->wb_id);

                    if ($actualPrice !== null && $product->price != $actualPrice) {
                        $updates[] = [
                            'id' => $product->id,
                            'price' => $actualPrice,
                            'updated_at' => now(),
                        ];
                    }
                    sleep(1);
                } catch (\Throwable $e) {
                    Log::error("Ошибка получения цены для товара {$product->id}: {$e->getMessage()}");
                }
            }

            // Массовое обновление вместо N save()
            if (!empty($updates)) {
                $this->bulkUpdate($updates);
                $this->info('Обновлено: ' . count($updates));
            }
        });

        $this->info('Синхронизация завершена');
    }

    /**
     * Массовое обновление (MySQL)
     */
    private function bulkUpdate(array $rows): void
    {
        $table = (new Product())->getTable();

        $ids = array_column($rows, 'id');
        $casePrice = "CASE id";
        $caseUpdated = "CASE id";

        foreach ($rows as $row) {
            $casePrice   .= " WHEN {$row['id']} THEN {$row['price']}";
            $caseUpdated .= " WHEN {$row['id']} THEN '{$row['updated_at']}'";
        }

        $casePrice   .= " END";
        $caseUpdated .= " END";

        $idsString = implode(',', $ids);

        DB::update("UPDATE {$table}
            SET price = {$casePrice}, updated_at = {$caseUpdated}
            WHERE id IN ({$idsString})");
    }
}
