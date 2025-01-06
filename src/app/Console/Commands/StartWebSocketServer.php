<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Workerman\Worker;

class StartWebSocketServer extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'websocket:serve';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Запускает вебсокет сервер';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        require base_path('WebSocket/Server.php');
        Worker::runAll();
        $this->alert('WebSocket is running');
    }
}
