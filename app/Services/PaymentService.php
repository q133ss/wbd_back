<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request;

class PaymentService
{
    protected string $baseUrl;
    protected array $headers;

    public function __construct()
    {
        $this->baseUrl = config('services.cloudpayments.base_uri', 'https://api.cloudpayments.ru');
        $this->headers = [
            'Content-Type' => 'application/json',
            'Accept'       => 'application/json',
            'Authorization'=> 'Basic ' . base64_encode(config('services.cloudpayments.public_key') . ':' . config('services.cloudpayments.secret_key')),
        ];
    }

    /**
     * Создание ссылки на оплату
     *
     * @param float $amount
     * @param string $currency
     * @param string $description
     * @param array $options
     * @return array
     */
    public function createInvoice(
        float $amount,
        string $currency,
        string $description,
        array $options = []
    ): array {
        $payload = array_merge([
            'Amount'       => $amount,
            'Currency'     => $currency,
            'Description'  => $description,
            'RequireConfirmation' => false,
            'SendEmail'    => false,
        ], $options);

        $response = Http::withHeaders($this->headers)
            ->post("{$this->baseUrl}/orders/create", $payload)
            ->throw()
            ->json();

        return $response['Model'] ?? $response;
    }

    /**
     * Получение статуса заказа по OrderId
     *
     * @param string $orderId
     * @return string|null
     */
    public function getPaymentStatus(string $invoiceId)
    {
        $response = Http::withHeaders($this->headers)
            ->post("{$this->baseUrl}/v2/payments/find", ['InvoiceId' => $invoiceId])
            ->throw()
            ->json();

        return $response;

        return $response['Success'] ? $response['Model']['Status'] : null;
    }

    /**
     * Обработка webhook запроса от CloudPayments
     *
     * @param Request $request
     * @return bool
     */
    public function handleWebhook(Request $request): bool
    {
        $data = $request->all();
        $event = $data['Event'] ?? null;

        switch ($event) {
            case 'PaymentSucceeded':
                // Обрабатываем успешный платёж
                break;

            case 'PaymentWaitingForCapture':
                // Оплата требует подтверждения (DMS)
                break;

            case 'RefundSucceeded':
                // Возврат проведён
                break;

            default:
                // Другие события
                break;
        }

        return true;
    }
}
