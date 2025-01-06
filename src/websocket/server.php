<?php
use Workerman\Worker;
require_once __DIR__ . '/../vendor/autoload.php';

// Создаем WebSocket-сервер
$ws_worker = new Worker("websocket://0.0.0.0:8088");

// Хранилище подключений пользователей
$ws_worker->userConnections = [];

// Обработчик нового подключения
$ws_worker->onConnect = function ($connection) use ($ws_worker) {
    $connection->onWebSocketConnect = function ($connection) use ($ws_worker) {
        $user_id = $_GET['user_id'] ?? null; // Передаем user_id через query string

        if ($user_id) {
            $ws_worker->userConnections[$user_id][$connection->id] = $connection;
            echo "User $user_id connected\n";
        }
    };
};

// Обработчик сообщений
$ws_worker->onMessage = function ($connection, $data) use ($ws_worker) {
    $message = json_decode($data, true);

    if (isset($message['recipients'])) {
        foreach ($message['recipients'] as $user_id) {
            if (isset($ws_worker->userConnections[$user_id])) {
                foreach ($ws_worker->userConnections[$user_id] as $client) {
                    $client->send(json_encode($message));
                }
            }
        }
    }
};

// Обработчик закрытия соединения
$ws_worker->onClose = function ($connection) use ($ws_worker) {
    foreach ($ws_worker->userConnections as $user_id => &$connections) {
        if (isset($connections[$connection->id])) {
            unset($connections[$connection->id]);
            echo "User $user_id disconnected\n";
        }
    }
};

// Запуск WebSocket-сервера
Worker::runAll();
