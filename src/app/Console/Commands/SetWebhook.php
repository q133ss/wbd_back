<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class SetWebhook extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tg:webhook';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Создает вебхук с ТГ ботом';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $token = config('services.telegram.token');
        $url = "https://api.telegram.org/bot$token/setWebhook";

        $webhookUrl = env('APP_URL') . '/api/telegram/webhook';
        $data = [
            'url' => $webhookUrl,
        ];

        file_get_contents($url, false, stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => 'Content-Type: application/json',
                'content' => json_encode($data),
            ],
        ]));
    }
}
