<?php

namespace App\Services;

use App\Exceptions\JsonException;

abstract class BaseService
{
    /**
     * Возвращает сообщение в контроллер
     *
     * @param  string  $message
     * @param  string  $code
     * @return string[]
     */
    public function formatResponse(string $status, mixed $message, int $code, string $messageText = 'message'): array
    {
        return [
            'status'     => $status,
            $messageText => $message,
            'code'       => $code,
        ];
    }

    public function sendResponse(array $formattedResponse)
    {
        foreach (['status', 'code'] as $key) {
            if (! isset($formattedResponse[$key])) {
                throw new \InvalidArgumentException("Поле \"{$key}\" является обязательным.");
            }

            // Дополнительно проверяем, что поле не пустое
            if (empty($formattedResponse[$key])) {
                throw new \InvalidArgumentException("Поле \"{$key}\" не может быть пустым.");
            }
        }

        return response()->json($formattedResponse, $formattedResponse['code']);
    }

    /**
     * Возвращает юзеру ошибку
     *
     * @throws JsonException
     */
    public function sendError(string $message, int $code = 500): mixed
    {
        throw new \App\Exceptions\JsonException($message, $code);
    }
}
