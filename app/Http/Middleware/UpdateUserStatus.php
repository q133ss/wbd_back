<?php

namespace App\Http\Middleware;

use App\Jobs\UpdateUserOnlineStatus;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class UpdateUserStatus
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        # TODO сделать полностью на REDIS без использования БД
        if (auth('sanctum')->check()) {
            $userId = auth('sanctum')->id();
            $cacheKey = "user:online:{$userId}";

            // Обновляем кеш каждую минуту (без immediate DB query)
            if (!Cache::has($cacheKey)) {
                Cache::put($cacheKey, true, now()->addMinute());

                // Отправляем в очередь с дебаунсом
                UpdateUserOnlineStatus::dispatch($userId)
                    ->delay(now()->addSeconds(5));
            }
        }

        return $next($request);
    }
}
