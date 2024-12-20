<?php

namespace App\Services;

abstract class BaseService
{
    /**
     * Возвращает сообщение в контроллер
     *
     * @param string $status
     * @param string $message
     * @param string $code
     * @return string[]
     */
    public function formatResponse(string $status, mixed $message, int $code): array
    {
        return [
            'status' => $status,
            'message' => $message,
            'code' => $code
        ];
    }

    public function sendResponse(array $formattedResponse)
    {
        foreach (['status', 'message', 'code'] as $key) {
            if (!isset($formattedResponse[$key])) {
                throw new \InvalidArgumentException("Поле \"{$key}\" является обязательным.");
            }

            // Дополнительно проверяем, что поле не пустое
            if (empty($formattedResponse[$key])) {
                throw new \InvalidArgumentException("Поле \"{$key}\" не может быть пустым.");
            }
        }
        return response()->json($formattedResponse, $formattedResponse['code']);
    }
}
