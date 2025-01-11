<?php

namespace App\Jobs;

use App\Events\GotMessage;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendMessage implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(array $message)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        GotMessage::dispatch([
            'type' => $this->message['type'],
            'buyback_id' => $this->message['buyback_id'],
            'buyer_id' => $this->message['buyer_id'],
            'seller_id' => $this->message['seller_id'],
            'text' => $this->message['message']['text'],
            'sender_id' => $this->message['message']['sender_id'],
            'message_type' => $this->message['message']['type'],
            'message_color' => $this->message['message']['color']
        ]);
    }
}
