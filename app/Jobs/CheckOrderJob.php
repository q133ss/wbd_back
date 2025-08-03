<?php

namespace App\Jobs;

use App\Models\Buyback;
use App\Models\Message;
use App\Services\SocketService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class CheckOrderJob implements ShouldQueue
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
        if(!$this->buyback->is_payment_photo_sent){
            // Если не обработал, то морозим профиль! и шлем сообщение!
            $this->buyback->ad?->user->update(['is_frozen' => true]);

            $msg = Message::create([
                'sender_id'   => $this->buyback->ad?->user?->id,
                'buyback_id'  => $this->buyback->id,
                'text'        => '24 часа вышло. Мы заморозили профиль продавца до выплаты кэшбека. Свяжитесь с поддержкой и укажите ID выкупа',
                'type'        => 'system',
                'created_at' => now()
            ]);
            (new SocketService)->send($msg, $this->buyback, false);
        }
    }
}
