<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notification Stream</title>
</head>
<body>
<h1>Notification Stream</h1>
<div id="notifications"></div>

<!-- Подключение библиотеки через CDN -->
<script src="https://cdn.jsdelivr.net/npm/event-source-polyfill@1.0.31/src/eventsource.min.js"></script>
<script>
    // Ваш токен аутентификации
    const token = '2|974KLVJW1Un7cza7dgHyQAvuy92q5m6VX8SICb4Qda3f5498';

    // Создаем новый EventSourcePolyfill с заголовком авторизации
    const eventSource = new EventSourcePolyfill('/api/notifications/sse', {
        headers: {
            'Authorization': 'Bearer ' + token
        }
    });

    // Обработчик для получения сообщений
    eventSource.onmessage = function(event) {
        const notification = JSON.parse(event.data);
        const notificationElement = document.createElement('div');
        notificationElement.textContent = notification.text;
        document.getElementById('notifications').appendChild(notificationElement);
    };

    // Обработчик ошибок
    eventSource.onerror = function(event) {
        console.error("EventSource failed:", event);
    };
</script>
</body>
</html>
