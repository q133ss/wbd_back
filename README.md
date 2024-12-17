# WB Discount

Сервис для продавцов и покупателей на Wildberies

## Содержание

- [Установка](#установка)
- [Использование](#использование)
- [Доступы по умолчанию](#доступы-по-умолчанию)
- [Выполнение команд Artisan](#выполнение-команд-artisan)

## Установка
```bash
docker exec -it wbd_php composer install
```
### Предварительные требования

- Docker
- Docker Compose

### Сборка и запуск Docker контейнеров

```bash
docker-compose up -d
```

## Использование

### Доступ к приложению

Адрес `http://localhost`.

### Доступ к базе данных

- Хост: `localhost`
- Порт: `3306`
- База данных: `laravel`
- Имя пользователя: `laravel`
- Пароль: `laravel`

### Redis

- Хост: `localhost`
- Порт: `6379`

## Выполнение команд Artisan

Выполните команды Artisan внутри контейнера PHP:

- **Запуск миграций:**
  ```bash
  docker exec -it wbd_php php artisan migrate
  ```

- **Очистка кэша конфигурации:**
  ```bash
  docker exec -it wbd_php php artisan config:cache
  ```

- **CodeFix:**
  ```bash
  docker exec -it wbd_php ./vendor/bin/pint
  ```

- **Создание новой миграции:**
  ```bash
  docker exec -it wbd_php php artisan make:migration create_users_table
  ```

- **Заполнение базы данных данными:**
  ```bash
  docker exec -it wbd_php php artisan db:seed
  ```

### Алиас для команд Artisan

Для удобства создайте алиас в вашей оболочке (`~/.bashrc`, `~/.zshrc` и т. д.):

```bash
alias art="docker exec -it wbd_php php artisan"
```

Перезагрузите терминал и используйте `art` для выполнения команд Artisan:

```bash
art migrate
art config:cache
art route:cache
```
