<?php

namespace App\Services\Telegram;

use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use DOMDocument;
use DOMXPath;
use Throwable;

class TelegramHtmlMessageParser
{
    /**
     * Parse Telegram web preview HTML and extract message data.
     *
     * @param  string  $html
     * @param  string  $groupSlug
     * @return array<int, array<string, mixed>>
     */
    public function parse(string $html, string $groupSlug): array
    {
        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
        libxml_clear_errors();

        $xpath = new DOMXPath($dom);
        $wrappers = $xpath->query("//div[contains(concat(' ', normalize-space(@class), ' '), ' tgme_widget_message_wrap ')]");

        if ($wrappers === false) {
            return [];
        }

        $messages = [];

        foreach ($wrappers as $wrapper) {
            $messageNode = $xpath->query(".//div[contains(concat(' ', normalize-space(@class), ' '), ' tgme_widget_message ')]", $wrapper)->item(0);

            if (!$messageNode) {
                continue;
            }

            $dataPost = $messageNode->attributes?->getNamedItem('data-post')?->nodeValue;
            if (!$dataPost) {
                continue;
            }

            $messageId = (string) Str::afterLast($dataPost, '/');
            if ($messageId === '') {
                continue;
            }

            $authorNode = $xpath->query(
                ".//*[contains(concat(' ', normalize-space(@class), ' '), ' tgme_widget_message_author_name ') or contains(concat(' ', normalize-space(@class), ' '), ' tgme_widget_message_owner_name ')]",
                $messageNode
            )->item(0);

            $author = $this->normalizeText($authorNode?->textContent);

            $textNode = $xpath->query(
                ".//div[contains(concat(' ', normalize-space(@class), ' '), ' tgme_widget_message_text ')]",
                $messageNode
            )->item(0);

            $messageText = $this->normalizeText($textNode?->textContent);

            $dateNode = $xpath->query('.//time', $messageNode)->item(0);
            $dateValue = $dateNode?->attributes?->getNamedItem('datetime')?->nodeValue;
            $messageDate = $this->parseDate($dateValue);

            $linkNode = $xpath->query(
                ".//a[contains(concat(' ', normalize-space(@class), ' '), ' tgme_widget_message_date ')]",
                $messageNode
            )->item(0);
            $link = $linkNode?->attributes?->getNamedItem('href')?->nodeValue ?: sprintf('https://t.me/%s/%s', $groupSlug, $messageId);

            $messages[] = [
                'group_slug' => $groupSlug,
                'message_id' => $messageId,
                'author' => $author,
                'message_text' => $messageText,
                'message_date' => $messageDate,
                'message_link' => $link,
            ];
        }

        return $messages;
    }

    private function normalizeText(?string $text): ?string
    {
        if ($text === null) {
            return null;
        }

        $decoded = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $decoded = mb_convert_encoding($decoded, 'UTF-8', 'UTF-8');
        $decoded = str_replace("\xc2\xa0", ' ', $decoded);
        $normalized = trim(preg_replace('/\s+/u', ' ', $decoded));

        return $normalized === '' ? null : $normalized;
    }

    private function parseDate(?string $value): ?Carbon
    {
        if (!$value) {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (Throwable) {
            return null;
        }
    }
}
