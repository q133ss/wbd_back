<?php

namespace App\Http\Middleware;

use App\Models\Role;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class IsAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth()->user();

        // Кешируем роль "admin" на 1 час
        $adminRoleId = Cache::remember('admin_role_id', 3600, function () {
            return Role::where('slug', 'admin')->pluck('id')->first();
        });

        if (!$user || $user->role_id != $adminRoleId) {
            abort(403, 'У вас нет прав');
        }

        return $next($request);
    }
}
