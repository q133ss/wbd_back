<?php

namespace App\Http\Middleware;

use App\Jobs\UpdateUserOnlineStatus;
use Closure;
use Illuminate\Http\Request;
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
        if (auth('sanctum')->check()) {
            $user = auth('sanctum')->user();
            // Отправляем задачу в очередь вместо прямого обновления
            UpdateUserOnlineStatus::dispatch($user)
                ->delay(now()->addSeconds(10)); // Небольшая задержка для дебаунса
        }
        return $next($request);
    }
}
