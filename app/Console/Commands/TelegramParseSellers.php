<?php

namespace App\Console\Commands;

use App\Services\Telegram\SellerParserService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class TelegramParseSellers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'telegram:parse-sellers {--pages=1 : Number of pagination pages to fetch} {--start-from= : Message identifier to start before} {--no-export : Do not refresh CSV export after sync}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Parse seller messages from a Telegram group and persist only new ones.';

    public function handle(): int
    {
        /** @var SellerParserService $service */
        $service = app(SellerParserService::class);

        $pages = (int) $this->option('pages');
        $startFrom = $this->option('start-from');
        $startFrom = $startFrom !== null ? (int) $startFrom : null;
        $export = ! $this->option('no-export');

        try {
            $result = $service->sync(max(1, $pages), $startFrom, $export);
        } catch (RuntimeException $exception) {
            $this->error($exception->getMessage());
            Log::error('telegram:parse-sellers failed', [
                'pages' => $pages,
                'start_from' => $startFrom,
                'export' => $export,
                'exception' => $exception,
            ]);

            return self::FAILURE;
        }

        $this->info(sprintf('Fetched %d messages, added %d new sellers.', $result['fetched'], $result['inserted']));

        if ($export) {
            $this->line(sprintf('CSV stored on disk [%s] at path [%s].', config('telegram.seller_export_disk'), config('telegram.seller_export_path')));
        }

        return self::SUCCESS;
    }
}
