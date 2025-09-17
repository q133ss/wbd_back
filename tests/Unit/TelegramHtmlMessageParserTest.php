<?php

namespace Tests\Unit;

use App\Services\Telegram\TelegramHtmlMessageParser;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class TelegramHtmlMessageParserTest extends TestCase
{
    public function test_it_parses_messages_from_html(): void
    {
        $html = <<<'HTML'
        <div class="tgme_widget_message_wrap js-widget_message_wrap">
            <div class="tgme_widget_message js-widget_message" data-post="group/123">
                <div class="tgme_widget_message_author accent_color">
                    <a class="tgme_widget_message_author_name" href="https://t.me/some">John Doe</a>
                </div>
                <div class="tgme_widget_message_text js-message_text">
                    <a href="https://example.com">Product</a>
                    Цена:&nbsp;1000₽
                </div>
                <span class="tgme_widget_message_date">
                    <time datetime="2025-09-16T09:00:00+00:00">09:00</time>
                </span>
            </div>
        </div>
        <div class="tgme_widget_message_wrap">
            <div class="tgme_widget_message" data-post="group/124">
                <div class="tgme_widget_message_owner_name">Alice</div>
                <div class="tgme_widget_message_text js-message_text">
                    Второе сообщение
                </div>
                <span class="tgme_widget_message_date">
                    <time datetime="2025-09-16T10:00:00+00:00">10:00</time>
                    <a class="tgme_widget_message_date" href="https://t.me/group/124">10:00</a>
                </span>
            </div>
        </div>
        <div class="tgme_widget_message_wrap">
            <div class="tgme_widget_message" data-post="group/">
                <div class="tgme_widget_message_text">Пропускаем без id</div>
            </div>
        </div>
        HTML;

        $parser = new TelegramHtmlMessageParser();

        $messages = $parser->parse($html, 'group');

        $this->assertCount(2, $messages);

        $first = $messages[0];
        $this->assertSame('123', $first['message_id']);
        $this->assertSame('John Doe', $first['author']);
        $this->assertSame('Product Цена: 1000₽', $first['message_text']);
        $this->assertInstanceOf(Carbon::class, $first['message_date']);
        $this->assertSame('https://t.me/group/123', $first['message_link']);

        $second = $messages[1];
        $this->assertSame('124', $second['message_id']);
        $this->assertSame('Alice', $second['author']);
        $this->assertSame('Второе сообщение', $second['message_text']);
        $this->assertInstanceOf(Carbon::class, $second['message_date']);
        $this->assertSame('https://t.me/group/124', $second['message_link']);
    }
}
