<?php

namespace App\Jobs;

use App\Services\ProductService;
use App\Services\WBService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LoadProductVariationsJob implements ShouldQueue
{
    use Queueable;

    private array $variationIds;
    private string $shopId;


    /**
     * Create a new job instance.
     */
    public function __construct(array $variationIds, string $shopId)
    {
        $this->variationIds = $variationIds;
        $this->shopId = $shopId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $service = new WBService();
        // Вызываем create() для каждой вариации
        foreach ($this->variationIds as $variationId) {
            $service->create($variationId, $this->shopId);
            sleep(1);
        }
    }
}
