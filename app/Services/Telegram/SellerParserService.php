<?php

namespace App\Services\Telegram;

use App\Models\TelegramSeller;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class SellerParserService
{
    private string $groupSlug;

    public function __construct(private readonly TelegramHtmlMessageParser $htmlParser)
    {
        $this->groupSlug = (string) config('telegram.seller_group_slug');

        if ($this->groupSlug === '') {
            throw new RuntimeException('Telegram seller group slug is not configured. Set TELEGRAM_SELLER_GROUP_SLUG in the environment.');
        }
    }

    /**
     * Synchronise sellers from the Telegram group.
     *
     * @param  int       $pages      Number of pages to fetch.
     * @param  int|null  $startFrom  Message identifier to start with (Telegram before parameter).
     * @param  bool      $export     Whether to export CSV after syncing.
     * @return array{fetched:int, inserted:int}
     */
    public function sync(int $pages = 1, ?int $startFrom = null, bool $export = true): array
    {
        $pages = max(1, $pages);
        $before = $startFrom;
        $totalFetched = 0;
        $totalInserted = 0;

        for ($page = 0; $page < $pages; $page++) {
            $messages = $this->collectMessages($before);
            if ($messages === []) {
                break;
            }

            $totalFetched += count($messages);

            foreach ($messages as $message) {
                $attributes = [
                    'author' => $message['author'] ?? null,
                    'message_text' => $message['message_text'] ?? null,
                    'message_link' => $message['message_link'] ?? null,
                    'posted_at' => $message['message_date'] ?? null,
                ];

                $record = TelegramSeller::firstOrCreate(
                    [
                        'group_slug' => $this->groupSlug,
                        'message_id' => (string) $message['message_id'],
                    ],
                    $attributes
                );

                if ($record->wasRecentlyCreated) {
                    $totalInserted++;
                }
            }

            $before = $this->determineNextBefore($messages, $before);
            if ($before === null) {
                break;
            }
        }

        if ($totalInserted > 0 && $export) {
            $this->exportToCsv();
        }

        return [
            'fetched' => $totalFetched,
            'inserted' => $totalInserted,
        ];
    }

    /**
     * Fetch Telegram page HTML and parse messages.
     */
    private function collectMessages(?int $before = null): array
    {
        try {
            $html = $this->downloadPage($before);
        } catch (RuntimeException $exception) {
            Log::error('Failed to download Telegram group page', [
                'group_slug' => $this->groupSlug,
                'before' => $before,
                'exception' => $exception->getMessage(),
            ]);

            return [];
        }

        return $this->htmlParser->parse($html, $this->groupSlug);
    }

    private function downloadPage(?int $before = null): string
    {
        $url = $this->buildFetchUrl($before);

        $response = Http::withHeaders([
            'User-Agent' => 'Mozilla/5.0 (compatible; TelegramSellerParser/1.0; +https://wbdiscount.pro)',
            'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'Accept-Language' => 'ru,en;q=0.9',
            'Referer' => 'https://t.me/',
        ])
            ->timeout((int) config('telegram.request_timeout', 10))
            ->retry((int) config('telegram.request_retries', 2), 500)
            ->get($url);

        if (!$response->successful()) {
            throw new RuntimeException(sprintf('Unable to fetch Telegram group page (status %s) for %s', $response->status(), $url));
        }

        return $response->body();
    }

    private function buildFetchUrl(?int $before = null): string
    {
        $configuredUrl = (string) config('telegram.seller_group_url');
        if ($configuredUrl !== '') {
            $url = rtrim($configuredUrl, '/');
        } else {
            $endpoint = (string) config('telegram.seller_group_fetch_endpoint', 'https://t.me/s/%s');
            $url = sprintf($endpoint, $this->groupSlug);
        }

        if ($before !== null) {
            $url .= (str_contains($url, '?') ? '&' : '?') . 'before=' . $before;
        }

        return $url;
    }

    private function determineNextBefore(array $messages, ?int $currentBefore): ?int
    {
        $ids = array_filter(array_map(static function ($message) {
            $id = $message['message_id'] ?? null;

            return is_numeric($id) ? (int) $id : null;
        }, $messages));

        if ($ids === []) {
            return null;
        }

        $min = min($ids);

        if ($min <= 1) {
            return null;
        }

        return $min - 1;
    }

    public function exportToCsv(): void
    {
        $diskName = (string) config('telegram.seller_export_disk', 'local');
        $relativePath = (string) config('telegram.seller_export_path', 'telegram/sellers.csv');
        $disk = Storage::disk($diskName);

        $directory = trim(dirname($relativePath), '.');
        if ($directory !== '' && $directory !== '/') {
            $disk->makeDirectory($directory);
        }

        $rows = [
            ['group_slug', 'message_id', 'author', 'message_text', 'posted_at', 'message_link', 'created_at'],
        ];

        TelegramSeller::query()
            ->where('group_slug', $this->groupSlug)
            ->orderBy('posted_at')
            ->orderBy('id')
            ->each(function (TelegramSeller $seller) use (&$rows) {
                $rows[] = [
                    $seller->group_slug,
                    $seller->message_id,
                    $seller->author,
                    $seller->message_text,
                    optional($seller->posted_at)->toIso8601String(),
                    $seller->message_link,
                    optional($seller->created_at)->toIso8601String(),
                ];
            });

        $csv = '';
        foreach ($rows as $row) {
            $csv .= $this->toCsvLine($row);
        }

        $disk->put($relativePath, $csv);
    }

    private function toCsvLine(array $row): string
    {
        $handle = fopen('php://temp', 'r+');
        fputcsv($handle, $row);
        rewind($handle);
        $line = stream_get_contents($handle) ?: '';
        fclose($handle);

        return $line;
    }
}
