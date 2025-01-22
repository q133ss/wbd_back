<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

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
        $command = 'php '.base_path('websocket/server.php').' start';
        $output  = shell_exec($command);
        $this->info("WebSocket server started successfully: \n".$output);
    }
}
