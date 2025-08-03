<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IsFrozen
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $check = auth('sanctum')->check();
        if($check){
            $user = auth('sanctum')->user();
            if($user->is_frozen){
                abort(403, 'Ваш аккаунт заморожен');
            }
        }
        return $next($request);
    }
}
