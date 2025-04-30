<?php

namespace App\Jobs;

use App\Models\Buyback;
use App\Services\BalanceService;
use App\Services\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;

class ReviewJob implements ShouldQueue
{
    use Queueable;

    private Buyback $buyback;

    /**
     * Create a new job instance.
     */
    public function __construct(Buyback $buyback)
    {
        $this->buyback = $buyback;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        if ($this->buyback->status    != 'cashback_received'
            && $this->buyback->status != 'cancelled'
            && $this->buyback->status != 'completed') {
            try {
                DB::beginTransaction();
                // перечисляем деньги покупателю
                (new BalanceService)->buybackPayment($this->buyback);
                $this->buyback->update(['status' => 'cashback_received']);
                // Уведомление
                (new NotificationService)->send($this->buyback->user_id, $this->buyback->id, 'Выкуп #'.$this->buyback->id.' автоматически подтвержден, прошло 72 часа', true);
                (new NotificationService)->send($this->buyback->ad?->user_id, $this->buyback->id, 'Выкуп #'.$this->buyback->id.' автоматически подтвержден, прошло 72 часа', true);
                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                \Log::error($e);
            }
        }
    }
}
