<?php

namespace App\Jobs;

use App\Models\Buyback;
use App\Services\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class DeliveryJob implements ShouldQueue
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
        // если он не подтвержден!
        if ($this->buyback->status    != 'on_confirmation'
            && $this->buyback->status != 'cancelled'
            && $this->buyback->status != 'completed') {
            $this->buyback->update(['status' => 'cancelled']);
            // Уведомление

            $webAppUrl = config('app.web_app_url').'/dashboard/orders?chatId='.$this->buyback->id;
            $keyboard = [
                'inline_keyboard' => [
                    [
                        [
                            'text' => '🚀 Открыть приложение',
                            'web_app' => ['url' => $webAppUrl]
                        ]
                    ]
                ],
            ];
            (new NotificationService)->send($this->buyback->user_id, $this->buyback->id, 'Выкуп #'.$this->buyback->id.' автоматически отменен. Вы не выполнили условия', true, $keyboard);
            (new NotificationService)->send($this->buyback->ad?->user_id, $this->buyback->id, 'Выкуп #'.$this->buyback->id.' автоматически отменен. Покупатель не выполнили условия', true, $keyboard);
        }
    }
}
