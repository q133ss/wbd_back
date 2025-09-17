<?php

namespace Tests\Feature;

use App\Services\Telegram\SellerParserService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TelegramSellerParserServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_persists_new_sellers_and_exports_csv(): void
    {
        Storage::fake('local');

        config([
            'telegram.seller_group_slug' => 'group',
            'telegram.seller_group_fetch_endpoint' => 'https://example.test/%s',
            'telegram.seller_export_disk' => 'local',
            'telegram.seller_export_path' => 'telegram/sellers.csv',
        ]);

        $html = <<<'HTML'
        <div class="tgme_widget_message_wrap">
            <div class="tgme_widget_message" data-post="group/201">
                <div class="tgme_widget_message_author_name">Seller One</div>
                <div class="tgme_widget_message_text">Первое предложение</div>
                <span class="tgme_widget_message_date">
                    <time datetime="2025-09-16T11:00:00+00:00">11:00</time>
                </span>
            </div>
        </div>
        <div class="tgme_widget_message_wrap">
            <div class="tgme_widget_message" data-post="group/200">
                <div class="tgme_widget_message_author_name">Seller Two</div>
                <div class="tgme_widget_message_text">Второе предложение</div>
                <span class="tgme_widget_message_date">
                    <time datetime="2025-09-16T10:30:00+00:00">10:30</time>
                    <a class="tgme_widget_message_date" href="https://t.me/group/200">10:30</a>
                </span>
            </div>
        </div>
        HTML;

        Http::fake([
            'https://example.test/group' => Http::response($html, 200),
        ]);

        $service = app(SellerParserService::class);

        $result = $service->sync(1, null, true);

        $this->assertSame(2, $result['fetched']);
        $this->assertSame(2, $result['inserted']);
        $this->assertDatabaseCount('telegram_sellers', 2);
        $this->assertDatabaseHas('telegram_sellers', [
            'group_slug' => 'group',
            'message_id' => '201',
            'author' => 'Seller One',
        ]);

        Storage::disk('local')->assertExists('telegram/sellers.csv');
        $csv = Storage::disk('local')->get('telegram/sellers.csv');
        $this->assertStringContainsString('Seller One', $csv);
        $this->assertStringContainsString('Seller Two', $csv);

        Http::fake([
            'https://example.test/group' => Http::response($html, 200),
        ]);

        $result = $service->sync(1, null, true);

        $this->assertSame(2, $result['fetched']);
        $this->assertSame(0, $result['inserted']);
    }
}
