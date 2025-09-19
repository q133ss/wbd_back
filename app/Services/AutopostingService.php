<?php

namespace App\Services;

use App\Models\Ad;
use App\Models\AutopostLog;
use App\Models\AutopostSetting;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AutopostingService
{
    public function publishAd(Ad $ad): void
    {
        $settings = AutopostSetting::query()->first();

        if (! $settings || ! $settings->is_enabled || ! $ad->status) {
            return;
        }

        $ad->loadMissing('product');

        $chatId = config('services.telegram.autopost_chat_id', '-3026543670');
        $botToken = config('services.telegram.autopost_token') ?: config('services.telegram.token');

        if (! $botToken) {
            AutopostLog::create([
                'ad_id' => $ad->id,
                'chat_id' => $chatId,
                'is_success' => false,
                'message' => null,
                'error_message' => 'Telegram bot token is not configured.',
            ]);

            return;
        }

        $message = $this->buildMessage($ad, $settings);
        $keyboard = $this->buildKeyboard($ad, $settings);
        $photoUrl = $settings->show_photo ? $this->getPhotoUrl($ad) : null;

        $payload = [
            'chat_id' => $chatId,
            'parse_mode' => 'HTML',
        ];

        if (! empty($keyboard)) {
            $payload['reply_markup'] = json_encode($keyboard, JSON_UNESCAPED_UNICODE);
        }

        try {
            if ($photoUrl) {
                $payload['photo'] = $photoUrl;
                $payload['caption'] = $message;
                $response = Http::asForm()->timeout(10)->post("https://api.telegram.org/bot{$botToken}/sendPhoto", $payload);
            } else {
                $payload['text'] = $message;
                $response = Http::asForm()->timeout(10)->post("https://api.telegram.org/bot{$botToken}/sendMessage", $payload);
            }

            $responseData = $response->json();
            $isOk = $response->successful() && Arr::get($responseData, 'ok') === true;

            AutopostLog::create([
                'ad_id' => $ad->id,
                'chat_id' => $chatId,
                'is_success' => $isOk,
                'message' => $message,
                'error_message' => $isOk ? null : ($responseData['description'] ?? $response->body()),
                'response_payload' => $responseData,
            ]);

            if (! $isOk) {
                Log::error('Autoposting failed', [
                    'ad_id' => $ad->id,
                    'response' => $responseData,
                ]);
            }
        } catch (\Throwable $exception) {
            AutopostLog::create([
                'ad_id' => $ad->id,
                'chat_id' => $chatId,
                'is_success' => false,
                'message' => $message,
                'error_message' => $exception->getMessage(),
            ]);

            Log::error('Autoposting exception', [
                'ad_id' => $ad->id,
                'message' => $exception->getMessage(),
            ]);
        }
    }

    protected function buildMessage(Ad $ad, AutopostSetting $settings): string
    {
        $lines = [];
        $lines[] = '<b>'.e($ad->product?->name).'</b>';

        if ($settings->show_price) {
            $lines[] = 'Цена: '.number_format((float) $ad->price_with_cashback, 2, '.', ' ').' ₽';
        }

        if ($settings->show_cashback) {
            $lines[] = 'Кэшбек: '.$this->formatPercentage($ad->cashback_percentage);
        }

        if ($settings->show_conditions) {
            $conditions = $this->prepareConditions($ad->order_conditions);
            if ($conditions !== '') {
                $lines[] = '';
                $lines[] = $conditions;
            }
        }

        if (! $settings->show_link) {
            $lines[] = '';
            $lines[] = 'Перейти к товару: '.$this->buildProductUrl($ad);
        }

        return trim(implode("\n", array_filter($lines, static fn ($line) => $line !== null)));
    }

    protected function buildKeyboard(Ad $ad, AutopostSetting $settings): array
    {
        if (! $settings->show_link) {
            return [];
        }

        return [
            'inline_keyboard' => [
                [
                    [
                        'text' => 'Перейти к товару',
                        'url' => $this->buildProductUrl($ad),
                    ],
                ],
            ],
        ];
    }

    protected function buildProductUrl(Ad $ad): string
    {
        $base = rtrim(config('app.frontend_url') ?? config('app.url'), '/');
        $productId = $ad->product?->id ?? $ad->product_id;

        return $base.'/products/'.$productId;
    }

    protected function getPhotoUrl(Ad $ad): ?string
    {
        $images = $ad->product?->images ?? [];

        if (is_array($images) && count($images) > 0) {
            return $images[0];
        }

        return null;
    }

    protected function prepareConditions(?string $conditions): string
    {
        if (! $conditions) {
            return '';
        }

        $text = str_ireplace(['<br />', '<br/>', '<br>'], "\n", $conditions);
        $text = strip_tags($text);
        $text = html_entity_decode($text);

        return e(trim($text));
    }

    protected function formatPercentage($value): string
    {
        $formatted = number_format((float) $value, 2, '.', ' ');
        $formatted = Str::of($formatted)->rtrim('0')->rtrim('.');

        return $formatted.'%';
    }
}
