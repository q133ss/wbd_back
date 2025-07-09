<?php

namespace App\Jobs;

use App\Models\Buyback;
use App\Models\Message;
use App\Services\BalanceService;
use App\Services\NotificationService;
use App\Services\SocketService;
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
        // Если статус заказа не поменялся, создаем еще 1 сообщение!
        $status = $this->buyback?->status;

        $text = 'Продавец не отправил кэшбек в установленный срок, мы уже направили СМС уведомление, письмо на почту и уведомление в ТГ бот. Наш менеджер связывается с продавцом, чтобы уточнить в чем дело. На аккаунт продавца наложены ограничения. Свяжитесь с нашей поддержкой, укажите ID заказа __ и получите актуальную информацию по сделке.';
        if($status == 'on_confirmation'){
            $msg = Message::create([
                'sender_id'   => $this->buyback->ad?->user?->id,
                'buyback_id'  => $this->buyback->id,
                'text'        => $text,
                'type'        => 'system',
                'system_type' => 'info',
                'created_at' => now(),
            ]);
            (new SocketService)->send($msg, $this->buyback, false);
        }
    }
}
