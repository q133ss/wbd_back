<?php

namespace App\Jobs;

use App\Models\Buyback;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class OrderPendingCheck implements ShouldQueue
{
    use Queueable;

    private Buyback $buyback;

    /**
     * Create a new job instance.
     */
    public function __construct(string $buyback_id)
    {
        $buyback       = Buyback::find($buyback_id);
        $this->buyback = $buyback;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        if ($this->buyback?->status == 'pending') {
            $this->buyback?->update(['status' => 'order_expired']);
            $id = $this->buyback?->id;
            Log::build([
                'driver' => 'single',
                'path'   => storage_path('logs/buyback.log'),
            ])->info("Выкуп ID: $id отменен. Прошло 30 минут");
        }
    }
}
