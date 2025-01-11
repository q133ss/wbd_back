# Используем официальный образ PHP с FPM
FROM php:8.2-fpm

# Устанавливаем необходимые зависимости для libevent
RUN apt-get update && apt-get install -y \
    libevent-dev \
    && rm -rf /var/lib/apt/lists/*

# Устанавливаем и активируем расширение pcntl
RUN docker-php-ext-configure pcntl --enable-pcntl \
    && docker-php-ext-install pcntl

# Копируем проект в контейнер
WORKDIR /var/www/html
COPY ./src /var/www/html

# Устанавливаем Composer для управления зависимостями
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Устанавливаем зависимости с помощью Composer
RUN composer install --no-dev --prefer-dist

# Устанавливаем права доступа для правильной работы
RUN chown -R www-data:www-data /var/www/html

# Открываем порт для WebSocket
EXPOSE 8088

# Запуск WebSocket сервера
CMD ["php", "/var/www/html/websocket/server.php", "start"]
